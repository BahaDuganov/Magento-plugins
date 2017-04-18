<?php

class Simi_Simimigrate_Adminhtml_Simimigrate_AppController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('simimigrate/app')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Apps'), Mage::helper('adminhtml')->__('Apps')
        );
        return $this;
    }

    
    
     public function editAction()
    {
        $entityId     = $this->getRequest()->getParam('entity_id');
        $model  = Mage::getModel('simimigrate/app')->load($entityId);
        if ($model->getId() || $entityId == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }
            Mage::register('app_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('simimigrate/app');

            $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('App Manager'),
                Mage::helper('adminhtml')->__('App Manager')
            );
            
            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('simimigrate/adminhtml_app_edit'))
                ->_addLeft($this->getLayout()->createBlock('simimigrate/adminhtml_app_edit_tabs'));
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('simimigrate')->__('App does not exist')
            );
            $this->_redirect('*/*/');
        }
    }
 
    public function newAction()
    {
        $this->_forward('edit');
    }
 
    /**
     * save item action
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $model = Mage::getModel('simimigrate/app');  
            $model->setData($data)
                ->setId($this->getRequest()->getParam('entity_id'));          
            try {
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('simimigrate')->__('App was successfully saved')
                );
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('entity_id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('entity_id' => $this->getRequest()->getParam('entity_id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('simimigrate')->__('Unable to find item to save')
        );
        $this->_redirect('*/*/');
    }
 
    /**
     * delete item action
     */
    public function deleteAction()
    {
        if ($this->getRequest()->getParam('entity_id') > 0) {
            try {
                $model = Mage::getModel('simimigrate/app');
                $model->setId($this->getRequest()->getParam('entity_id'))
                    ->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('App was successfully deleted')
                );
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('entity_id' => $this->getRequest()->getParam('entity_id')));
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * mass delete item(s) action
     */
    public function massDeleteAction()
    {
        $entityIds = $this->getRequest()->getParam('app_id');
        if (!is_array($entityIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($entityIds as $entityId) {
                    $model = Mage::getModel('simimigrate/app')->load($entityId);
                    $model->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted',
                    count($entityIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * index action
     */
    public function indexAction() {
        $this->_initAction()
            ->renderLayout();
    }

    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('connector');
    }
    
    public function chooserMainProductsAction() {        
        $request = $this->getRequest();
        $block = $this->getLayout()->createBlock(
                'simimigrate/adminhtml_app_edit_tab_products', 'app_widget_chooser_sku', array('js_form_object' => $request->getParam('form'),
                ));
        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }

}

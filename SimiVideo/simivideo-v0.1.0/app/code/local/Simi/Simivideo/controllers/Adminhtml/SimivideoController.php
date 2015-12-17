<?php

/**
 * Magestore
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category    Magestore
 * @package     Simi_Simivideo
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

class Simi_Simivideo_Adminhtml_SimivideoController extends Mage_Adminhtml_Controller_Action {

    /**
     * init layout and set active for current menu
     *
     * @return Simi_Simivideo_Adminhtml_SimivideoController
     */
    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('simivideo/simivideo')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Videos'), Mage::helper('adminhtml')->__('Videos')
        );
        return $this;
    }

    
    
     public function editAction()
    {
        $videoId     = $this->getRequest()->getParam('video_id');
        $model  = Mage::getModel('simivideo/simivideo')->load($videoId);
        if ($model->getId() || $videoId == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }
            Mage::register('simivideo_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('simivideo/simivideo');

            $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Video Manager'),
                Mage::helper('adminhtml')->__('Video Manager')
            );
            
            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('simivideo/adminhtml_simivideo_edit'))
                ->_addLeft($this->getLayout()->createBlock('simivideo/adminhtml_simivideo_edit_tabs'));
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('simivideo')->__('Video does not exist')
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
            //Zend_debug::dump($data);die();
            $model = Mage::getModel('simivideo/simivideo');  
            $model->setData($data)
                ->setId($this->getRequest()->getParam('video_id'));
                $url = $model->getData('video_url');
                parse_str( parse_url($url, PHP_URL_QUERY ), $my_array_of_vars);
                if ($my_array_of_vars['v'])
                    $model->setData('video_key',$my_array_of_vars['v']);
                else {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('simivideo')->__('Please use a full and valid Youtube video url'));
                    Mage::getSingleton('adminhtml/session')->setFormData($data);
                    $this->_redirect('*/*/edit', array('video_id' => $this->getRequest()->getParam('video_id')));
                    return;
                }
             
            try {
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('simivideo')->__('Video was successfully saved')
                );
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('video_id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('video_id' => $this->getRequest()->getParam('video_id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('simivideo')->__('Unable to find item to save')
        );
        $this->_redirect('*/*/');
    }
 
    /**
     * delete item action
     */
    public function deleteAction()
    {
        if ($this->getRequest()->getParam('video_id') > 0) {
            try {
                $model = Mage::getModel('simivideo/simivideo');
                $model->setId($this->getRequest()->getParam('video_id'))
                    ->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Video was successfully deleted')
                );
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('video_id' => $this->getRequest()->getParam('video_id')));
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * mass delete item(s) action
     */
    public function massDeleteAction()
    {
        $videoIds = $this->getRequest()->getParam('video_id');
        if (!is_array($videoIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($videoIds as $videoId) {
                    $video = Mage::getModel('simivideo/simivideo')->load($videoId);
                    $video->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted',
                    count($videoIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
    
    /**
     * mass change status for item(s) action
     */
    public function massStatusAction()
    {
        $videoIds = $this->getRequest()->getParam('video_id');
        if (!is_array($videoIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
        } else {
            try {
                foreach ($videoIds as $videoId) {
                    Mage::getSingleton('simivideo/simivideo')
                        ->load($videoId)
                        ->setStatus($this->getRequest()->getParam('status'))
                        ->setIsMassupdate(true)
                        ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', count($videoIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
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
        return Mage::getSingleton('admin/session')->isAllowed('simivideo');
    }
    
    public function chooserMainProductsAction() {        
        $request = $this->getRequest();
        $block = $this->getLayout()->createBlock(
                'simivideo/adminhtml_simivideo_edit_tab_products', 'simivideo_widget_chooser_sku', array('js_form_object' => $request->getParam('form'),
                ));
        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }

}

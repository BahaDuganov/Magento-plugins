<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 11/22/17
 * Time: 11:11 AM
 */
class Simi_Simipwa_Adminhtml_Simipwa_NotificationController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('simipwa');
    }

    public function indexAction(){
        $this->loadLayout()->renderLayout();
    }

    public function newAction() {
        $this->_forward('edit');
    }

    public function editAction() {
        $id	 = $this->getRequest()->getParam('id');
        $model  = Mage::getModel('simipwa/message')->load($id);

        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data))
                $model->setData($data);

            Mage::register('message_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('simipwa/agent');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('PWA Manager'), Mage::helper('adminhtml')->__('Notification Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('PWA News'), Mage::helper('adminhtml')->__('Notification News'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('simipwa/adminhtml_notification_edit'))
                ->_addLeft($this->getLayout()->createBlock('simipwa/adminhtml_notification_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('simipwa')->__('Item does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function sendMessageAction(){
        if ($data = $this->getRequest()->getPost()) {
            $id = $this->getRequest()->getParam('id');
            $message = Mage::getModel('simipwa/message');

            /*upload img*/
            if (isset($_FILES['img_url']['name']) && $_FILES['img_url']['name'] != ''){
                try {
                    $uploader = new Varien_File_Uploader($_FILES['img_url']);
                    $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png'));
                    $uploader->setAllowRenameFiles(false);
                    $uploader->setFilesDispersion(false);

                    $path = Mage::getBaseDir('media') . DS . 'simipwa' . DS . 'img' . DS;

                    $result = $uploader->save($path, $_FILES['img_url']['name']);

                    $data['image_url'] =  'simipwa/img/'.$result['file'];
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
            } else {
                if (isset($data['img_url']['delete']) && $data['img_url']['delete'] == 1) {
                    $pathImg = 'media/'.$data['img_url']['value'];
                    if (file_exists($pathImg)) {
                        unlink($pathImg);
                    }
                    $data['image_url'] = '';
                }
            }
            if(array_key_exists(0,$data['device_id'])){
                $data['notice_type'] = 2;
            }
            else {
                $data['notice_type'] = 1;
            }
            $device_ids = $data['device_id'];
            //zend_debug::dump($device_ids);die;
            $data['device_id'] = implode(',',$data['device_id']);
            $message->setData($data);
            //zend_debug::dump($data);die;
            try {
                if (!$data['type'] && $data['product_id']){
                    $data['type'] = 1;
                }
                $message->setData($data);
                $mess = Mage::getModel('simipwa/message')->getCollection()
                    ->addFieldToFilter('status',1);
                foreach ($mess as $item){
                    $item['status'] = 2;
                    $item->save();
                }

                $message->setCreatedTime(now())->setStatus(1);
                $message->setId($id)->save();

                if($data['notice_type'] == 1){
                    foreach ($device_ids as $id){
                        Mage::getModel('simipwa/agent')->send($id);
                    }
                }
                elseif ($data['notice_type'] == 2){
                    $devices = Mage::getModel('simipwa/agent')->getCollection();
                    foreach ($devices as $item){
                        $send = Mage::getModel('simipwa/agent')->send($item->getId());
                        if (!$send) $item->delete();
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('simipwa')->__('Notification was successfully sent'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                $this->_redirect('*/*/index');
                return;
            }
            catch (Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
    }

    public function chooserMainCategoriesAction(){
        $request = $this->getRequest();
        $id = $request->getParam('selected', array());
        $block = $this->getLayout()->createBlock('simipwa/adminhtml_pwa_edit_tab_categories','maincontent_category', array('js_form_object' => $request->getParam('form')))
            ->setCategoryIds($id)
        ;

        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }

    /**
     * Get tree node (Ajax version)
     */
    public function categoriesJsonAction() {
        if ($categoryId = (int) $this->getRequest()->getPost('id')) {
            $this->getRequest()->setParam('id', $categoryId);

            if (!$category = $this->_initCategory()) {
                return;
            }
            $this->getResponse()->setBody(
                $this->getLayout()->createBlock('adminhtml/catalog_category_tree')
                    ->getTreeJson($category)
            );
        }
    }

    /**
     * Initialize category object in registry
     *
     * @return Mage_Catalog_Model_Category
     */
    protected function _initCategory() {
        $categoryId = (int) $this->getRequest()->getParam('id', false);
        $storeId = (int) $this->getRequest()->getParam('store');

        $category = Mage::getModel('catalog/category');
        $category->setStoreId($storeId);

        if ($categoryId) {
            $category->load($categoryId);
            if ($storeId) {
                $rootId = Mage::app()->getStore($storeId)->getRootCategoryId();
                if (!in_array($rootId, $category->getPathIds())) {
                    $this->_redirect('*/*/', array('_current' => true, 'id' => null));
                    return false;
                }
            }
        }

        Mage::register('category', $category);
        Mage::register('current_category', $category);

        return $category;
    }

    public function categoriesJson2Action() {
        $this->_initItem();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('simipwa/adminhtml_pwa_edit_tab_categories')
                ->getCategoryChildrenJson($this->getRequest()->getParam('category'))
        );
    }

    public function chooserMainProductsAction() {
        $request = $this->getRequest();
        $block = $this->getLayout()->createBlock(
            'simipwa/adminhtml_pwa_edit_tab_products', 'promo_widget_chooser_sku', array('js_form_object' => $request->getParam('form'),
        ));
        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }
}
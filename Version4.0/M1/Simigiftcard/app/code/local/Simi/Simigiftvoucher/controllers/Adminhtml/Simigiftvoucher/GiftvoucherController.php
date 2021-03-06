<?php
/**
 * Simi
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Simicart.com license that is
 * available through the world-wide-web at this URL:
 * http://www.Simicart.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Simi
 * @package     Simi_Simigiftvoucher
 * @module     Giftvoucher
 * @author      Simi Developer
 *
 * @copyright   Copyright (c) 2016 Simi (http://www.Simicart.com/)
 * @license     http://www.Simicart.com/license-agreement.html
 *
 */

/**
 * Adminhtml Giftvoucher controller
 *
 * @category Simi
 * @package  Simi_Simigiftvoucher
 * @module   Giftvoucher
 * @author   Simi Developer
 */
class Simi_Simigiftvoucher_Adminhtml_Simigiftvoucher_GiftvoucherController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Export gift codes to the Csv file
     */
    public function exportCsvAction()
    {
        $fileName = 'giftcode.csv';
        $content = $this->getLayout()->createBlock('simigiftvoucher/adminhtml_giftvoucher_grid')->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export gift codes to the Xml file
     */
    public function exportXmlAction()
    {
        $fileName = 'giftcode.xml';
        $content = $this->getLayout()->createBlock('simigiftvoucher/adminhtml_giftvoucher_grid')->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Import gift codes
     */
    public function importAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('simigiftvoucher/giftvoucher')
            ->_addContent($this->getLayout()->createBlock('simigiftvoucher/adminhtml_giftvoucher_import'));
        $this->_title($this->__('Gift Code'))
            ->_title($this->__('Import Gift Codes'));
        $this->renderLayout();
    }

    /**
     * Download the sample file
     */
    public function downloadSampleAction()
    {
        $filename = Mage::getBaseDir('media') . DS . 'simigiftvoucher' . DS . 'import_giftcode_sample.csv';
        $this->_prepareDownloadResponse('import_giftcode_sample.csv', file_get_contents($filename));
    }

    /**
     * Process input data of Gift Card
     */
    public function processImportAction()
    {
        if (isset($_FILES['filecsv'])) {
            try {
                $fileName = $_FILES['filecsv']['tmp_name'];
                $csvObject = new Varien_File_Csv();
                $dataFile = $csvObject->getData($fileName);

                $count = array();
                $fields = array();
                $giftVoucherImport = array();
                foreach ($dataFile as $row => $cols) {
                    if ($row == 0) {
                        $fields = $cols;
                    } else {
                        $giftVoucherImport[] = array_combine($fields, $cols);
                    }
                }
                foreach ($giftVoucherImport as $giftVoucherData) {
                    $giftVoucher = Mage::getModel('simigiftvoucher/giftvoucher');
                    if (isset($giftVoucherData['gift_code']) && $giftVoucherData['gift_code']) {
                        $giftVoucher->loadByCode($giftVoucherData['gift_code']);
                        if ($giftVoucher->getId()) {
                            Mage::getSingleton('adminhtml/session')->addError(
                                $this->__('Gift code %s already existed', $giftVoucher->getGiftCode()));
                            continue;
                        } else {
                            Mage::helper('simigiftvoucher')->createBarcode($giftVoucherData['gift_code']);
                        }
                    }

                    $statuses = array(
                        '1' => 1, 'pending' => 1,
                        '2' => 2, 'active' => 2,
                        '3' => 3, 'disabled' => 3,
                        '4' => 4, 'used' => 4,
                        '5' => 5, 'expired' => 5,
                    );
                    if (isset($giftVoucherData['status']) && $giftVoucherData['status']) {
                        $giftVoucherData['status'] = $statuses[$giftVoucherData['status']];
                    }
//                    unset($giftVoucherData['order_increment_id']);
                    if (isset($giftVoucherData['history_amount']) && $giftVoucherData['history_amount']) {
                        $giftVoucherData['amount'] = $giftVoucherData['history_amount'];
                    }
                    if (isset($giftVoucherData['extra_content']) && $giftVoucherData['extra_content']) {
                        $giftVoucherData['extra_content'] = str_replace('\n', chr(10),
                            $giftVoucherData['extra_content']);
                    } else {
                        $giftVoucherData['extra_content'] = Mage::helper('simigiftvoucher')->__('Imported by %s',
                            Mage::getSingleton('admin/session')->getUser()->getUsername());
                    }
                    $giftVoucherData['recipient_address'] = str_replace('\n', chr(10),
                        $giftVoucherData['recipient_address']);
                    $giftVoucherData['message'] = str_replace('\n', chr(10), $giftVoucherData['message']);
                    if (!isset($giftVoucherData['currency'])) {
                        $giftVoucherData['currency'] = Mage::app()->getStore($giftVoucherData['store_id'])
                            ->getBaseCurrencyCode();
                    }
                    if (!isset($giftVoucherData['giftcard_template_id'])) {
                        $template = Mage::getModel('simigiftvoucher/gifttemplate')->getCollection()->getFirstItem();
                        $images = explode(',', $template->getImages());

                        $giftVoucherData['giftcard_template_image'] = $images[0];
                        $giftVoucherData['giftcard_template_id'] = $template->getId();
                    }
                    try {
                        $giftVoucher->setData($giftVoucherData)
                            ->setIncludeHistory(true)
                            ->setId(null)
                            ->save();
                        $count[] = $giftVoucher->getId();
                    } catch (Exception $e) {
                        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    }
                }
                if (count($count)) {
                    $successMessage = $this->__('Imported total %d Gift Code(s)', count($count));
                    if ($this->getRequest()->getParam('print')) {
                        $url = $this->getUrl('*/*/massPrint', array(
                            'simigiftvoucher' => implode(',', $count)
                        ));
                        $successMessage .= "<script type='text/javascript'>document.observe('dom:loaded',function(){
                        var bob=window.open('','_blank');bob.location='" . $url . "';    
                        });</script>";
                    }
                    Mage::getSingleton('adminhtml/session')->addSuccess($successMessage);
                    $this->_redirect('*/*/index');
                    return $this;
                } else {
                    Mage::getSingleton('adminhtml/session')->addError($this->__('No gift code imported'));
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError($this->__('No uploaded files'));
        }
        $this->_redirect('*/*/import');
    }

    /**
     * Print gift code action
     */
    public function printAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function printFoldAction()
    {
        //Mage::helper('simigiftvoucher/pdf')->printPdf('simigiftvoucher/adminhtml_giftvoucher_view', 'simigiftvoucher/giftcard/fold_print.phtml');
        $this->loadLayout();
        $this->renderLayout();
    }


    /**
     * Print gift code in mass number
     */
    public function massPrintAction()
    {
        $this->loadLayout();
        $printType = $this->getRequest()->getParam('print_code');
        if ($printType == Simi_Simigiftvoucher_Model_Giftvoucher::TYPE_NORMAL) {
            $html = $this->getLayout()->getBlock('simigiftvoucher.massview')->toHtml();
        } else {
            $html = $this->getLayout()->getBlock('simigiftvoucher.massviewfold')->toHtml();
        }
        $this->getResponse()->setBody($html);
        return;
    }

    /**
     * Initialize action
     *
     * @return Simi_Simigiftvoucher_Adminhtml_SimigiftvoucherController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('simigiftvoucher/giftvoucher')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Gift Code Manager'),
                Mage::helper('adminhtml')->__('Gift Code Manager'));

        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__('Gift Code'))
            ->_title($this->__('Manage Gift Code'));
        $this->_initAction()
            ->renderLayout();
    }

    /**
     * Edit Gift code action
     */
    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('simigiftvoucher/giftvoucher')->load($id);
        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }
            $this->_title($this->__('Gift Code'));
            if ($model->getId()) {
                $this->_title($model->getGiftCode());
            } else {
                $this->_title($this->__('New Gift Code'));
            }

            $model->getConditions()->setJsFormObject('giftvoucher_conditions_fieldset');
            $model->getActions()->setJsFormObject('giftvoucher_actions_fieldset');
            Mage::register('giftvoucher_data', $model);
            $this->loadLayout();
            $this->_setActiveMenu('simigiftvoucher/giftvoucher');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Gift Code Manager'),
                Mage::helper('adminhtml')->__('Gift Code Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Gift Code News'),
                Mage::helper('adminhtml')->__('Gift Code News'));

            $this->getLayout()->getBlock('head')
                ->setCanLoadExtJs(true)
                ->setCanLoadRulesJs(true);
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true)
                ->addItem('js', 'tiny_mce/tiny_mce.js')
                ->addItem('js', 'mage/adminhtml/wysiwyg/tiny_mce/setup.js')
                ->addJs('mage/adminhtml/browser.js')
                ->addJs('prototype/window.js')
                ->addJs('lib/flex.js')
                ->addJs('mage/adminhtml/flexuploader.js');
            $this->_addContent($this->getLayout()->createBlock('simigiftvoucher/adminhtml_giftvoucher_edit'))
                ->_addLeft($this->getLayout()->createBlock('simigiftvoucher/adminhtml_giftvoucher_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('simigiftvoucher')->__('Gift Code does not exist'));
            $this->_redirect('*/*/');
        }
    }

    /**
     * Create new Gift code action
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Save Gift code action
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {

            $model = Mage::getModel('simigiftvoucher/giftvoucher');
            $data = $this->_filterDates($data, array('expired_at'));
            if (!$data['expired_at']) {
                $data['expired_at'] = null;
            }
            unset($data['order_increment_id']);
            $data['status'] = $data['giftvoucher_status'];
            $data['comments'] = $data['giftvoucher_comments'];
            $data['amount'] = $data['balance'];
            if (isset($data['rule'])) {
                $rules = $data['rule'];
                if (isset($rules['conditions'])) {
                    $data['conditions'] = $rules['conditions'];
                }
                if (isset($rules['actions'])) {
                    $data['actions'] = $rules['actions'];
                }
                unset($data['rule']);
            }

            if ($this->getRequest()->getParam('id')) {
                $data['action'] = Simi_Simigiftvoucher_Model_Actions::ACTIONS_UPDATE;
                $data['extra_content'] = Mage::helper('simigiftvoucher')->__('Updated by %s',
                    Mage::getSingleton('admin/session')->getUser()->getUsername());
            } else {
                $data['extra_content'] = Mage::helper('simigiftvoucher')->__('Created by %s',
                    Mage::getSingleton('admin/session')->getUser()->getUsername());
            }
            $incrementId = Mage::getModel('simigiftvoucher/giftvoucher')->getCollection()->joinHistory()
                ->addFieldToFilter('history.giftvoucher_id',
                    $this->getRequest()->getParam('id'))->getFirstItem()->getOrderIncrementId();

            if (!$data['giftcard_template_id']) {
                $template = Mage::getModel('simigiftvoucher/gifttemplate')->getCollection()->getFirstItem();
                $templateImages = explode(',', $template->getImages());

                $data['giftcard_template_id'] = $template->getId();
                $data['giftcard_template_image'] = $templateImages[0];
            }

            $model->setData($data)
                ->setIncludeHistory(true)
                ->setId($this->getRequest()->getParam('id'));

            try {
                $model->loadPost($data);
                $model->save();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('simigiftvoucher')->__('Gift Code was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    if ($this->getRequest()->getParam('sendemail')) {
                        $emailSent = (int)$model->sendEmail()->getEmailSent();
                        if ($emailSent) {
                            Mage::getSingleton('adminhtml/session')->addSuccess(
                                Mage::helper('simigiftvoucher')->__('and (%d) email(s) were sent.', $emailSent));
                        } else {
                            $allowStatus = explode(',',
                                Mage::helper('simigiftvoucher')->getEmailConfig('only_complete', $model->getStoreId()));
                            if (!$model->getRecipientEmail()) {
                                Mage::getSingleton('adminhtml/session')->addError(
                                    Mage::helper('simigiftvoucher')->__('There is no email address to send.'));
                            } else {
                                $options = Mage::getModel('simigiftvoucher/status')->getOptionArray();
                                Mage::getSingleton('adminhtml/session')->addError(
                                    Mage::helper('simigiftvoucher')->__('gift card is %s should not send an email, %s',
                                        $options[$model->getStatus()], '<a href="' .
                                        $this->getUrl('adminhtml/system_config/edit/section/simigiftvoucher') . '">' .
                                        Mage::helper('simigiftvoucher')->__(' view config select status of gift card when sending e-mail to friend')
                                        . '</a>'));
                            }
                        }
                    }
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('simigiftvoucher')->__('Unable to find Gift Code to save'));
        $this->_redirect('*/*/');
    }

    /**
     * Delete Gift code action
     */
    public function deleteAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('simigiftvoucher/giftvoucher');

                $model->setId($this->getRequest()->getParam('id'))
                    ->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Gift Code was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Delete Gift code in mass number
     */
    public function massDeleteAction()
    {
        $giftvoucherIds = $this->getRequest()->getParam('simigiftvoucher');
        if (!is_array($giftvoucherIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('adminhtml')->__('Please select Gift Code(s)'));
        } else {
            try {
                foreach ($giftvoucherIds as $giftvoucherId) {
                    $simigiftvoucher = Mage::getModel('simigiftvoucher/giftvoucher')->load($giftvoucherId);
                    $simigiftvoucher->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($giftvoucherIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Reload the images of gift code's template
     */
    public function giftimagesAction()
    {
        $templateId = $this->getRequest()
            ->getParam('gift_template_id');
        $currentImage = $this->getRequest()
            ->getParam('current_image');
        $customerUploadImage = $this->getRequest()
            ->getParam('custom_image');
        if (!$templateId && $customerUploadImage == 'false') {
            $this->getResponse()->setBody('');
            return;
        }
        $template = Mage::getModel('simigiftvoucher/gifttemplate')->load($templateId);
        $images = $template->getImages();
        if ($customerUploadImage == 'true') {
            $images = $currentImage;
        }
        $str = '';
        if ($images) {
            $str .= '<div class="carousel" id="gift-image-carosel">
                            <a href="javascript:" class="carousel-control next" rel="next">???</a>
                            <a href="javascript:" class="carousel-control prev" rel="prev">???</a>
                            <div class="gift-middle" id="carousel-wrapper">
                                <div class="inner" style="width: 3000px;">
                  ';
            $type = '';
            switch ($template->getDesignPattern()) {
                case Simi_Simigiftvoucher_Model_Designpattern::PATTERN_LEFT:
                    $type = 'left/';
                    break;
                case Simi_Simigiftvoucher_Model_Designpattern::PATTERN_TOP:
                    $type = 'top/';
                    break;
                case Simi_Simigiftvoucher_Model_Designpattern::PATTERN_SIMPLE:
                    $type = 'simple/';
                    break;
                case Simi_Simigiftvoucher_Model_Designpattern::PATTERN_CENTER:
                    $type = '';
                    break;
                case Simi_Simigiftvoucher_Model_Designpattern::PATTERN_AMAZON:
                    $type = 'amazon/';
                    break;
            }
            $images = explode(',', $images);
            $count = 0;
            $selectImage = 0;
            foreach ($images as $image) {
                $str .= '<div id="div-image-for-' . $templateId . '-' . $count .
                    '" style="position:relative; float: left;border: 2px solid white;">';
                $str .= '<img src="' . Mage::getBaseUrl("media") . 'simigiftvoucher/template/images/' . $type . $image .
                    '" alt="" style="width:80px;height:80px"
                    onclick="changeSelectImages(' . $count . ',\'' . $image . '\')">';
                $str .= '<div class="egcSwatch-arrow" style="display:none"></div>';
                $str .= '</div>';
                if ($image == $currentImage) {
                    $selectImage = $count;
                }
                $count++;
            }
            if ($currentImage) {
                $str .= '<input type="hidden" id="current_image" value=' . $currentImage . '>';

                $str .= '<input type="hidden" id="selected_image" value=' . $selectImage . '>';
            } else {
                $str .= '<input type="hidden" id="current_image" value=' . $images[0] . '>';

                $str .= '<input type="hidden" id="selected_image" value="0">';
            }
            $str .= '</div>
                </div>
               </div>';
        }
        $this->getResponse()->setBody($str);
        return;
    }

    /**
     * Change the gift code's status in mass number
     */
    public function massStatusAction()
    {
        $giftvoucherIds = $this->getRequest()->getParam('simigiftvoucher');
        if (!is_array($giftvoucherIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select Gift Code(s)'));
        } else {
            try {
                $cnt = 0;
                foreach ($giftvoucherIds as $giftvoucherId) {
                    $simigiftvoucher = Mage::getSingleton('simigiftvoucher/giftvoucher')
                        ->load($giftvoucherId);
                    if ($simigiftvoucher->getStatus() < Simi_Simigiftvoucher_Model_Status::STATUS_EXPIRED) {
                        $simigiftvoucher->setStatus($this->getRequest()->getParam('status'));
                        $simigiftvoucher->setIsMassupdate(true)
                            ->setAction(Simi_Simigiftvoucher_Model_Actions::ACTIONS_MASS_UPDATE)
                            ->setExtraContent(Mage::helper('simigiftvoucher')->__('Mass status updated by %s',
                                Mage::getSingleton('admin/session')->getUser()->getUsername()))
                            ->setIncludeHistory(true)
                            ->save();
                        $cnt++;
                    }
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', $cnt)
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Display Gift code's history grid action
     */
    public function historygridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock('simigiftvoucher/adminhtml_giftvoucher_edit_tab_history')
                ->setGiftvoucher($this->getRequest()->getParam('id'))->toHtml()
        );
    }

    /**
     * Send Gift code's email in mass number
     */
    public function massEmailAction()
    {
        $giftvoucherIds = $this->getRequest()->getParam('simigiftvoucher');
        if (!is_array($giftvoucherIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('adminhtml')->__('Please select Gift Code(s)'));
        } else {
            try {
                $totalEmailSent = 0;
                foreach ($giftvoucherIds as $giftvoucherId) {
                    $simigiftvoucher = Mage::getModel('simigiftvoucher/giftvoucher')->load($giftvoucherId);
                    $simigiftvoucher->setMassEmail(true);
                    $emailSent = (int)$simigiftvoucher->sendEmail()->getEmailSent();
                    if ($emailSent) {
                        $totalEmailSent += $emailSent;
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d Gift Code with %d email(s) were successfully sent.',
                        count($giftvoucherIds), $totalEmailSent
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Display Gift code's history action
     */
    public function historyAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('simigiftvoucher/adminhtml_customer_tab_history')->toHtml()
        );
    }

    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('simigiftvoucher/giftvoucher');
    }

}

<?php
/**
 * Simi
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Simi.com license that is
 * available through the world-wide-web at this URL:
 * http://www.simi.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category    Simi
 * @package     Simi_Simirewardpoints
 * @copyright   Copyright (c) 2012 Simi (http://www.simi.com/)
 * @license     http://www.simi.com/license-agreement.html
 */

/**
 * Simirewardpoints Action Library Helper
 * 
 * @category    Simi
 * @package     Simi_Simirewardpoints
 * @author      Simi Developer
 */
class Simi_Simirewardpoints_Helper_Action extends Mage_Core_Helper_Abstract
{
    const XML_CONFIG_ACTIONS    = 'global/simirewardpoints/actions';
    
    /**
     * reward points actions config
     * 
     * @var array
     */
    protected $_config = array();
    
    /**
     * Actions Array (code => label)
     * 
     * @var array
     */
    protected $_actions = null;
    
    public function __construct()
    {
        $actionConfig = (array)Mage::getConfig()->getNode(self::XML_CONFIG_ACTIONS);
        $websiteId = Mage::helper('simiconnector/cloud')->getWebsiteIdSimiUser();
        if($websiteId){
            $actionCode = 'admin_'.$websiteId;
            $actionConfig[$actionCode] = (string)$actionConfig['admin'];
        }
        foreach ($actionConfig as $code => $model) {
            $this->_config[$code] = (string)$model;
        }

    }
    
    /**
     * Add transaction that change customer reward point balance
     * 
     * @param string $actionCode
     * @param Mage_Customer_Model_Customer $customer
     * @param type $object
     * @param array $extraContent
     * @return Simi_Simirewardpoints_Model_Transaction
     */
    public function addTransaction($actionCode, $customer, $object = null, $extraContent = array())
    {
        Varien_Profiler::start('REWARDPOINTS_HELPER_ACTION::addTransaction');
        if (!$customer->getId()) {
            throw new Exception($this->__('Customer must be existed.'));
        }
        $actionModel = $this->getActionModel($actionCode);
        /** @var $actionModel Simi_Simirewardpoints_Model_Action_Interface */
        $actionModel->setData(array(
            'customer'      => $customer,
            'action_object' => $object,
            'extra_content' => $extraContent
        ))->prepareTransaction();
        
        $transaction = Mage::getModel('simirewardpoints/transaction');
        if (is_array($actionModel->getData('transaction_data'))) {
            $transaction->setData($actionModel->getData('transaction_data'));
        }
        $transaction->setData('point_amount', (int)$actionModel->getPointAmount());
        
        if (!$transaction->hasData('store_id')) {
            $transaction->setData('store_id', Mage::app()->getStore()->getId());
        }
        
        $transaction->createTransaction(array(
            'customer_id'   => $customer->getId(),
            'customer'      => $customer,
            'customer_email'=> $customer->getEmail(),
            'title'         => $actionModel->getTitle(),
            'action'        => $actionCode,
            'action_type'   => $actionModel->getActionType(),
            'created_time'  => now(),
            'updated_time'  => now(),
        ));
        
        Varien_Profiler::stop('REWARDPOINTS_HELPER_ACTION::addTransaction');
        return $transaction;
    }
    
    /**
     * get Class Model for Action by code
     * 
     * @param string $code
     * @return string
     * @throws Exception
     */
    public function getActionModelClass($code) {

        if (isset($this->_config[$code]) && $this->_config[$code]) {
            return $this->_config[$code];
        }
        throw new Exception($this->__('Action code %s not found on config.', $code));
    }
    
    /**
     * get action Model by Code
     * 
     * @param string $code
     * @return Simi_Simirewardpoints_Model_Action_Interface
     * @throws Exception
     */
    public function getActionModel($code) {
        $modelClass = $this->getActionModelClass($code);
        $model = Mage::getSingleton($modelClass);
        if (!($model instanceof Simi_Simirewardpoints_Model_Action_Interface)) {
            throw new Exception($this->__('Action model need implements from %s',
                'Simi_Simirewardpoints_Model_Action_Interface')
            );
        }
        if (!$model->getCode()) {
            $model->setCode($code);
        }
        return $model;
    }
    
    /**
     * get actions hash options
     * 
     * @return array
     */
    public function getActionsHash() {
        if (is_null($this->_actions)) {
            $this->_actions = array();
            foreach ($this->_config as $code => $class) {
                try {
                    $model = $this->getActionModel($code);
                    $this->_actions[$code] = $model->getActionLabel();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
        return $this->_actions;
    }
    
    /**
     * get actions array options
     * 
     * @return array
     */
    public function getActionsArray() {
        $actions = array();
        foreach ($this->getActionsHash() as $value => $label) {
            $actions[] = array(
                'value' => $value,
                'label' => $label
            );
        }
        return $actions;
    }
}

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
 * Giftvoucher Creditmemo Credit Block
 *
 * @category Simi
 * @package  Simi_Simigiftvoucher
 * @module   Giftvoucher
 * @author   Simi Developer
 */

class Simi_Simigiftvoucher_Block_Order_Creditmemo_Credit extends Mage_Core_Block_Template
{

    public function initTotals()
    {
        $orderTotalsBlock = $this->getParentBlock();
        $order = $orderTotalsBlock->getCreditmemo();
        if ($order->getSimiuseGiftCreditAmount() && $order->getSimiuseGiftCreditAmount() > 0) {
            $orderTotalsBlock->addTotal(new Varien_Object(array(
                'code' => 'simigiftcardcredit',
                'label' => $this->__('Gift Card credit'),
                'value' => -$order->getSimiuseGiftCreditAmount(),
                'base_value' => -$order->getSimibaseUseGiftCreditAmount(),
                )), 'subtotal');
        }
    }

}

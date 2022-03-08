<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MyFatoorah\MyFatoorahPaymentGateway\Model\Config\Source;

/**

 * Class GatewayAction

 */
class GatewayAction implements \Magento\Framework\Option\ArrayInterface {

    /**
     * {@inheritdoc}
     */
    public function toOptionArray() {
        return array(
            ['value' => 'myfatoorah', 'label' => 'MyFatoorah Invoice Page (Redirect)'],
            ['value' => 'multigateways', 'label' => 'List All Enabled Gateways in Checkout Page'],
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray() {
        
    }

}

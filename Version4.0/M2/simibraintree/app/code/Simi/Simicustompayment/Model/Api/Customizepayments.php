<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simicustompayment\Model\Api;

class Customizepayments extends \Simi\Simiconnector\Model\Api\Apiabstract
{
    public function setBuilderQuery() {
        $data = $this->getData();
        if ($data['resourceid']) {
            
        } else {
            
        }
    }

    public function index() {
        $simiCustompaymentArray = array();
        $simiCustompaymentArray['all_ids'] = array('1');
        $simiCustompaymentArray['customizepayments'] = $this->getPaymentList();
        $simiCustompaymentArray['total'] = 1;
        $simiCustompaymentArray['page_size'] = 15;
        $simiCustompaymentArray['from'] = 0;
        return $simiCustompaymentArray;
    }

    public function getPaymentList() {
        return array(
            array(
                'paymentmethod' => 'simibraintree',
                'title_url_action' => 'url_action',
                'url_redirect' => $this->simiObjectManager->get('Magento\Framework\UrlInterface')
                    ->getUrl(''),
                'url_success' => 'simibraintree/index/success',
                'url_fail' => 'simibraintree/index/failure',
                'url_cancel' => 'simibraintree/index/cancel/',
                'url_error' => 'simibraintree/index/error/',
                'message_success' => 'Thank you for purchasing',
                'message_fail' => 'Sorry, payment failed',
                'message_cancel' => 'Your order has been canceled',
                'message_error' => 'Sorry, Your order has an error',
                'ischeckurl' => '0', //(bien check truoc khi chuyen sang webview. Co hoac khong) : "0" or "1"
                'url_check' => "checkout/onepage/failure"
            )
        );
    }
}

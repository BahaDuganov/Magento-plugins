<?php

namespace MyFatoorah\Myfatoorah\Model;
class MyfatoorahPaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{    
    protected $_code = 'myfatoorahpaymentmethod';

    protected $_isOffline = false;

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        echo '<pre>';

        print_r($payment);
        print_r($amount);

        exit();
    }
	
	 public function getCheckoutRedirectUrl()
    {
        return $this->_urlBuilder->getUrl('paypal/express/start');
    }

	  public function getConfigData($field, $storeId = null)
    {
        if ('order_place_redirect_url' === $field) {
            return $this->getOrderPlaceRedirectUrl();
        }
        return $this->_pro->getConfig()->getValue($field);
    }


}
<?php

namespace MyFatoorah\MyFatoorahPaymentGateway\Controller\Checkout;

use Magento\Sales\Model\Order;

/**
 * @package MyFatoorah\MyFatoorahPaymentGateway\Controller\Checkout
 */
class Index extends AbstractAction {

    public $orderId = null;

//---------------------------------------------------------------------------------------------------------------------------------------------------
    /* private function shippingAndCurrencyRate($order) {

      if ($order == null) {////???????????????????not working whaT IS THAT USED FOR??
      $this->getLogger()->addError('Unable to get order from last lodged order id. Possibly related to a failed database call');
      $this->_redirect('checkout/onepage/error', array('_secure' => false));
      }

      $shippingAddress      = $order->getShippingAddress();
      $shippingAddressParts = preg_split('/\r\n|\r|\n/', $shippingAddress->getData('street'));

      $magento_version = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ProductMetadataInterface')->getVersion();
      $plugin_version  = $this->getGatewayConfig()->getVersion();

      $data = array(
      // 'x_url_cancel' => $this->getDataHelper()->getCancelledUrl($orderId),
      'x_shop_name'  => $this->getDataHelper()->getStoreCode(),
      //            'x_customer_shipping_address1' => $shippingAddressParts[0],
      //            'x_customer_shipping_address2' => count($shippingAddressParts) > 1 ? $shippingAddressParts[1] : '',
      //            'x_customer_shipping_city' => $shippingAddress->getData('city'),
      //            'x_customer_shipping_state' => $shippingAddress->getData('region'),
      //            'x_customer_shipping_zip' => $shippingAddress->getData('postcode'),
      'version_info' => 'MyFatoorah_' . $plugin_version . '_on_magento' . substr($magento_version, 0, 3),
      //            'x_test'                       => 'false',
      );

      $currencyRate = (double) $this->objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getCurrentCurrencyRate();
      $items        = $order->getAllVisibleItems();
      foreach ($items as $item) {
      $product_name = $item->getName();
      // $itemPrice = $item->getPrice() * $currencyRate;
      // print_r($item->getPriceInclTax() ); die;
      $itemPrice    = $item->getPriceInclTax() * $currencyRate;
      $qty          = $item->getQtyOrdered();

      $invoiceItemsArr[] = array('ItemName' => $product_name, 'Quantity' => intval($qty), 'UnitPrice' => $itemPrice);
      }

      $shipping = $order->getShippingAmount() + $order->getShippingTaxAmount();
      if ($shipping != '0') {
      $invoiceItemsArr[] = array('ItemName' => 'Shipping Amount', 'Quantity' => 1, 'UnitPrice' => $shipping);
      //            $amount = $amount + $shipping;
      }
      $discount = $order->getDiscountAmount();
      if ($discount != '0') {
      $invoiceItemsArr[] = array('ItemName' => 'Discount Amount', 'Quantity' => 1, 'UnitPrice' => $discount);
      //            $amount = $amount + $discount;
      }

      // print_r($data); die;
      foreach ($data as $key => $value) {
      $data[$key] = preg_replace('/\r\n|\r|\n/', ' ', $value);
      }
      } */

    //---------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * @return void
     */
    public function execute() {

        try {
            $order = $this->getOrder();

            $this->order   = $order;
            $this->orderId = $order->getRealOrderId();

            if ($order->getState() === Order::STATE_CANCELED) {
                $errorMessage = $this->getCheckoutSession()->getMyFatoorahErrorMessage(); //set in InitializationRequest
                if ($errorMessage) {
                    $this->getMessageManager()->addWarningMessage($errorMessage);
                    $errorMessage = $this->getCheckoutSession()->unsMyFatoorahErrorMessage();
                }
                $this->getLogger()->addNotice('Order in state: ' . $order->getState());
                $this->getCheckoutHelper()->restoreQuote(); //restore cart

                $this->_redirect('checkout/cart');
            } else {
                if ($order->getState() !== Order::STATE_PENDING_PAYMENT) {
                    $this->getLogger()->addNotice('Order in state: ' . $order->getState());
                }
                $order->addStatusHistoryComment('The customer was redirected to MyFatoorah');
                $order->save();
                $this->postToCheckout($order);
                $this->getCheckoutHelper()->restoreQuote();
            }
            /* } catch (Exception $ex) {
              $this->getLogger()->addError('An exception was encountered in myfatoorah/checkout/index: ' . $ex->getMessage());
              $this->getLogger()->addError($ex->getTraceAsString());
              $this->getMessageManager()->addErrorMessage(__('Unable to start myfatoorah Checkout.')); */
        } catch (\Exception $ex) {
//            $this->getCheckoutHelper()->restoreQuote(); //restore cart
//            $this->getMessageManager()->addErrorMessage($ex->getMessage());
//            $this->_redirect('checkout/cart');

            $err = $ex->getMessage();

            $url = $this->getDataHelper()->getCancelledUrl($this->orderId, urlencode($err));

            $this->_redirect($url);
        }
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /** @var \Magento\Sales\Model\Order $order */
    private function getPayload($order, $gateway) {

        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $addressObj = $order->getShippingAddress();
        if (!is_object($addressObj)) {
            $addressObj = $order->getBillingAddress();
            if (!is_object($addressObj)) {
                throw new \Exception('Billing Address or Shipping address Data Should be set to create the invoice');
            }
        }

        $addressData = $addressObj->getData();

        $countryCode = isset($addressData['country_id']) ? $addressData['country_id'] : '';
        $city        = isset($addressData['city']) ? $addressData['city'] : '';
        $postcode    = isset($addressData['postcode']) ? $addressData['postcode'] : '';
        $region      = isset($addressData['region']) ? $addressData['region'] : '';

        $street1 = isset($addressData['street']) ? $addressData['street'] : '';
        $street  = trim(preg_replace("/[\n]/", ' ', $street1 . ' ' . $region));

        $phoneNo = isset($addressData['telephone']) ? $addressData['telephone'] : '';

        //$order->getCustomerName()  //$order->getCustomerFirstname() //$order->getCustomerLastname()
        $fName = !empty($addressObj->getFirstname()) ? $addressObj->getFirstname() : '';
        $lName = !empty($addressObj->getLastname()) ? $addressObj->getLastname() : '';

        $email = $order->getData('customer_email'); //$order->getCustomerEmail()


        $getLocale = $this->objectManager->get('Magento\Framework\Locale\Resolver');
        $haystack  = $getLocale->getLocale();
        $lang      = strstr($haystack, '_', true);

        $phone = $this->myfatoorah->getPhone($phoneNo);
        $url   = $this->getDataHelper()->getCompleteUrl();

        $userDefinedField = ($this->_gatewayConfig->getSaveCard() && $order->getCustomerId()) ? 'CK-' . $order->getCustomerId() : null;

        $shippingMethod = $order->getShippingMethod();
        $isShipping     = null;
        if (($shippingMethod == 'myfatoorah_shipping_1') || ($shippingMethod == 'myfatoorah_shippingDHL_myfatoorah_shippingDHL')) {
            $isShipping = 1;
        } else if (($shippingMethod == 'myfatoorah_shipping_2') || ($shippingMethod == 'myfatoorah_shippingAramex_myfatoorah_shippingAramex')) {
            $isShipping = 2;
        }

        $shippingConsignee = !$isShipping ? '' : array(
            'PersonName'   => "$fName $lName",
            'Mobile'       => trim($phone[1]),
            'EmailAddress' => $email,
            'LineAddress'  => trim(preg_replace("/[\n]/", ' ', $street . ' ' . $region)),
            'CityName'     => $city,
            'PostalCode'   => $postcode,
            'CountryCode'  => $countryCode
        );

        $currency = $this->getCurrencyData($gateway);

        //$invoiceItemsArr
//        $invoiceValue    = round($order->getBaseTotalDue() * $currency['rate'], 3);
//        $invoiceItemsArr = [[array('ItemName' => "Total Amount Order #$this->orderId", 'Quantity' => 1, 'UnitPrice' => $invoiceValue)]];

        $invoiceValue    = 0;
        $invoiceItemsArr = $this->getInvoiceItems($order, $currency['rate'], $isShipping, $invoiceValue);

        //ExpiryDate
        $expireAfter = $this->getPendingOrderLifetime(); //get Magento Pending Payment Order Lifetime (minutes)

        $ExpiryDate = new \DateTime('now', new \DateTimeZone('Asia/Kuwait'));
        $ExpiryDate->modify("+$expireAfter minute");
        return [
            'CustomerName'       => $fName . ' ' . $lName,
            'DisplayCurrencyIso' => $currency['code'], //$order->getOrderCurrencyCode(),
            'MobileCountryCode'  => trim($phone[0]),
            'CustomerMobile'     => trim($phone[1]),
            'CustomerEmail'      => $email,
            'InvoiceValue'       => "$invoiceValue",
            'CallBackUrl'        => $url,
            'ErrorUrl'           => $url,
            'Language'           => $lang,
            'CustomerReference'  => $this->orderId,
            'CustomerCivilId'    => $this->orderId,
            'UserDefinedField'   => $userDefinedField,
            'ExpiryDate'         => $ExpiryDate->format('Y-m-d\TH:i:s'),
            'SourceInfo'         => 'Magento 2 - API Ver 2.0',
            'CustomerAddress'    => [
                'Block'               => '',
                'Street'              => '',
                'HouseBuildingNo'     => '',
                'Address'             => $city . ', ' . $region . ', ' . $postcode,
                'AddressInstructions' => $street
            ],
            'ShippingConsignee'  => $shippingConsignee,
            'ShippingMethod'     => $isShipping,
            'InvoiceItems'       => $invoiceItemsArr
        ];
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /** @var \Magento\Sales\Model\Order $order */
    function getInvoiceItems($order, $currencyRate, $isShipping, &$amount) {

        /** @var \Magento\Framework\App\Config\ScopeConfigInterface $ScopeConfigInterface */
        $ScopeConfigInterface = $this->getObjectManager()->create('\Magento\Framework\App\Config\ScopeConfigInterface');

        $weightUnit = $ScopeConfigInterface->getValue('general/locale/weight_unit', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $weightRate = ($isShipping) ? $this->myfatoorah->getWeightRate($weightUnit) : 1;

        /** @var \Magento\Sales\Api\Data\OrderItemInterface[]  $items */
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            $itemPrice = round($item->getBasePrice() * $currencyRate, 3);
            $qty       = intval($item->getQtyOrdered());

            $invoiceItemsArr[] = [
                'ItemName'  => $item->getName(),
                'Quantity'  => $qty,
                'UnitPrice' => "$itemPrice",
                'weight'    => $item->getWeight() * $weightRate,
                'Width'     => $item->getProduct()->getData('width'),
                'Height'    => $item->getProduct()->getData('height'),
                'Depth'     => $item->getProduct()->getData('depth'),
            ];
            $amount            += round($itemPrice * $qty, 3);
        }


        $shipping = $order->getBaseShippingAmount() + $order->getBaseShippingTaxAmount();
        if (!empty($shipping) && !$isShipping) {
            $itemPrice         = round($shipping * $currencyRate, 3);
            $invoiceItemsArr[] = ['ItemName' => 'Shipping Amount', 'Quantity' => '1', 'UnitPrice' => "$itemPrice", 'Weight' => '0', 'Width' => '0', 'Height' => '0', 'Depth' => '0'];

            $amount += $itemPrice;
        }


        $discount = $order->getBaseDiscountAmount();
        if ($discount != 0) {
            $itemPrice         = round($discount * $currencyRate, 3);
            $invoiceItemsArr[] = ['ItemName' => 'Discount Amount', 'Quantity' => '1', 'UnitPrice' => "$itemPrice", 'Weight' => '0', 'Width' => '0', 'Height' => '0', 'Depth' => '0'];

            $amount += $itemPrice;
        }

        $tax = $order->getBaseTaxAmount();
        if (!empty($tax)) {
            $itemPrice         = round($tax * $currencyRate, 3);
            $invoiceItemsArr[] = ['ItemName' => 'Tax Amount', 'Quantity' => '1', 'UnitPrice' => "$itemPrice", 'Weight' => '0', 'Width' => '0', 'Height' => '0', 'Depth' => '0'];

            $amount += $itemPrice;
        }

        //Mageworx
        $fees = $order->getBaseMageworxFeeAmount();
        if (!empty($fees)) {
            $itemPrice         = round($fees * $currencyRate, 3);
            $invoiceItemsArr[] = array('ItemName' => 'Additional Fees', 'Quantity' => 1, 'UnitPrice' => "$itemPrice");
            $amount            += $itemPrice;
        }

        $productFees = $order->getBaseMageworxProductFeeAmount();
        if (!empty($productFees)) {
            $itemPrice         = round($productFees * $currencyRate, 3);
            $invoiceItemsArr[] = array('ItemName' => 'Additional Product Fees', 'Quantity' => 1, 'UnitPrice' => "$itemPrice");
            $amount            += $itemPrice;
        }



        /*
          $this->log->info(print_r('FeeAmount' . $order->getBaseMageworxFeeAmount(),1));
          $this->log->info(print_r('FeeInvoiced' . $order->getBaseMageworxFeeInvoiced(),1));
          $this->log->info(print_r('FeeCancelled' . $order->getBaseMageworxFeeCancelled(),1));
          $this->log->info(print_r('FeeTaxAmount' . $order->getBaseMageworxFeeTaxAmount(),1));
          $this->log->info(print_r('FeeDetails' . $order->getMageworxFeeDetails(),1));
          $this->log->info(print_r('FeeRefunded' . $order->getMageworxFeeRefunded(),1));

          $this->log->info(print_r('ProductFeeAmount' . $order->getBaseMageworxProductFeeAmount(),1));
          $this->log->info(print_r('ProductFeeInvoiced' . $order->getBaseMageworxProductFeeInvoiced(),1));
          $this->log->info(print_r('ProductFeeCancelled' . $order->getBaseMageworxProductFeeCancelled(),1));
          $this->log->info(print_r('ProductFeeTaxAmount' . $order->getBaseMageworxProductFeeTaxAmount(),1));
          $this->log->info(print_r('ProductFeeDetails' . $order->getMageworxProductFeeDetails(),1));
          $this->log->info(print_r('ProductFeeRefunded' . $order->getMageworxProductFeeRefunded(),1));
         */
        return $invoiceItemsArr;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    function getCurrencyData($gateway) {
        /** @var \Magento\Store\Model\StoreManagerInterface  $StoreManagerInterface */
        $store = $this->objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore();

        $KWDcurrencyRate = (double) $store->getBaseCurrency()->getRate('KWD');
        if ($gateway == 'kn' && !empty($KWDcurrencyRate)) {
            $currencyCode = 'KWD';
            $currencyRate = $KWDcurrencyRate;
        } else {
            $currencyCode = $store->getBaseCurrencyCode();
            $currencyRate = 1;
            //(double) $this->objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getCurrentCurrencyRate();
        }
        return ['code' => $currencyCode, 'rate' => $currencyRate];
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------

    /** @var \Magento\Sales\Model\Order $order */
    private function postToCheckout($order) {

        $gateway = $this->getRequest()->get('gateway') ?: 'myfatoorah';

        $payload = $this->getPayload($order, $gateway);

        $gatewayName = $gatewayId   = 'myfatoorah';
        if ($gateway != 'myfatoorah') {
            $pm          = $this->myfatoorah->getPaymentMethod($gateway, $gatewayType = 'PaymentMethodCode');
            $gatewayId   = $pm->PaymentMethodId;
        }

        $data  = $this->myfatoorah->getInvoiceURL($payload, $gatewayId, $this->orderId);
        $cards = $this->_gatewayConfig->getPaymentGateways();
        foreach ($cards as $key => $card) {
            if ($key == $gateway) {
                $gatewayName = $card->getText();
            }
        }

        //save the invoice id in myfatoorah_invoice table 
        $mf = $this->objectManager->create('MyFatoorah\MyFatoorahPaymentGateway\Model\MyfatoorahInvoice');
        $mf->addData([
            'order_id'     => $this->orderId,
            'invoice_id'   => $data['invoiceId'],
            'gateway_name' => $gatewayName,
            'invoice_url'  => $data['invoiceURL'],
        ]);
        $mf->save();

        //save the invoice id in sales_order table 
//        $this->order->setMyfatoorahInvoiceId($data['InvoiceId']);
//        $this->order->save();

        $this->_redirect($data['invoiceURL']);
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
}

<?php

namespace MyFatoorah\Myfatoorah\Model;

use MyFatoorah\Myfatoorah\Api\OrderManagementInterface;

class OrderManagement implements OrderManagementInterface {

    /**
     * Returns payment status
     *
     * @api
     * @param int $cartId cart ID.
     * @param int $billingAddressId billing Address ID.
     * @return mixed.
     */
    public $payment_gateway;
    public $token;

    public function checkoutOrder($cartId, $billingAddressId) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $quote = $objectManager->create('Magento\Quote\Model\Quote')->loadByIdWithoutStore($cartId);
        if (!$quote->getCustomerId()) {
            return '{"error": {"param": "cartId","message": "cart ID does not exist "}}';
        }

        $address = $objectManager->create('Magento\Customer\Model\Address')->load($billingAddressId);
        $addressArray = $address->getData();

        if (empty($addressArray)) {
            return '{"error": {"param": "addressId","message": "Address does not exist "}}';
        }


        $helper = $objectManager->get('\MyFatoorah\Myfatoorah\Helper\MyFatoorahAPI');
        $this->payment_gateway = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/myfatoorah/payment_gateway');



        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $customerData = $customerSession->getCustomer()->getData(); //get all data of customerData
        $Firstname = $customerData['firstname'];
        $Lastname = $customerData['lastname'];

        $customerEmail = $customerData['email'];

        $customerName = $Firstname . ' ' . $Lastname;
        $quote->reserveOrderId()->save();

        $incrementId = $quote->getReservedOrderId();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($incrementId);
        $merchant_ReferenceID = $incrementId;

        $returnURL = str_replace('?___SID=U', '', $objectManager->create('Magento\Framework\UrlInterface')->getUrl('myfatoorah/actions/response?qoid='. base64_encode($cartId.'305'), array('_secure' => true)));

        $currencyCode = $objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getCurrentCurrency()->getCode();
        $this->token = $helper->getToken();
//        echo $this->token; die; 
        if (empty($this->token)) {
            return '{"error": {"param": "API username and password","message": "MyFatoorah can not authorize your request. Please try again later or contact MyFatoorah Support"}}';
        }
        $currencyIso = $helper->checkCountryAndCurrency($currencyCode);
//print_r($currencyIso);
        if (!$currencyIso) {
            return '{"error": {"param": "Currency","message": ' . $currencyCode . '" is not supported by payment gateway. "}}';
        }

        $currencyRate = (double) $objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getCurrentCurrencyRate();
        
        $items = $quote->getAllVisibleItems();
        $invoiceItemsArr = array();
        foreach ($items as $item) {
            $product_name = $item->getName();
            $itemPrice = $item->getPrice() * $currencyRate;
            $qty = $item->getQty();

            $invoiceItemsArr[] = array('ProductId' => '', 'ProductName' => $product_name, 'Quantity' => $qty, 'UnitPrice' => $itemPrice);
        }

        $shipping = $quote->getShippingAddress()->getShippingAmount();
        if ($shipping != '0') {
            $invoiceItemsArr[] = array('ProductId' => '', 'ProductName' => 'Shipping Amount', 'Quantity' => 1, 'UnitPrice' => $shipping);
        }
        $discount = $quote->getShippingAddress()->getDiscountAmount();
        if ($discount != '0') {
            $invoiceItemsArr[] = array('ProductId' => '', 'ProductName' => 'Discount Amount', 'Quantity' => 1, 'UnitPrice' => $discount);
        }
        $tax = $quote->getShippingAddress()->getTaxAmount();
        if ($tax != '0') {
            $invoiceItemsArr[] = array('ProductId' => '', 'ProductName' => 'Tax Amount', 'Quantity' => 1, 'UnitPrice' => $tax);
        }


        $countryId = $helper->checkCountryAndCurrency($addressArray['country_id'], true);
        if (!$countryId) {
            return '{"error": {"param": "Country","message": "MyFatoorah supports only GCC countries!"}}';
        }

        $getLocale = $objectManager->get('Magento\Framework\Locale\Resolver');
        $haystack = $getLocale->getLocale();
        $lang = strstr($haystack, '_', true);
        $language = 2;
        switch ($lang) {
            case 'ar':
                $language = 1;
                break;
        }

// json data 
        $curl_data['data'] = '{
	  "InvoiceValue": ' . ltrim($merchant_ReferenceID, '0') . ',
	  "CustomerName": "' . $customerName . '",
	  "CustomerBlock": "string",
	  "CustomerStreet": "string",
	  "CustomerHouseBuildingNo": "string",
	  "CustomerCivilId": "2",
	  "CustomerAddress": "' . $addressArray['city'] . '",
	  "CustomerReference": "' . $merchant_ReferenceID . '",
	  "DisplayCurrencyIsoAlpha":"' . $currencyIso['iso'] . '",
	  "CountryCodeId": "' . $countryId . '",
	  "CustomerMobile": "' . substr($addressArray['telephone'], -10) . '",
	  "CustomerEmail": "' . $customerEmail . '",
	  "SendInvoiceOption": 2,
	  "InvoiceItemsCreate": ' . json_encode($invoiceItemsArr) . ',
	  "CallBackUrl": "' . $returnURL . '",
	  "Language": "' . $language . '",
	  "ExpireDate": "' . gmdate("D, d M Y H:i:s", time() + 7 * 24 * 60 * 60) . '",
	  "ApiCustomFileds": "string",
          "ErrorUrl": "' . $returnURL . '"
	}';
        $result = $helper->createInvoice($curl_data);

        if (isset($result->IsSuccess) && $result->IsSuccess) {
            $redirectUrl = $result->RedirectUrl;
            foreach ($result->PaymentMethods as $PaymentMethod) {
                if ($PaymentMethod->PaymentMethodCode == $this->payment_gateway) {
                    $redirectUrl = $PaymentMethod->PaymentMethodUrl;
                }
            }
            // echo $redirectUrl; die;
            header('Location:  ' . $redirectUrl);
            // added by NMSH
            exit();
        } else {
            foreach ($result->FieldsErrors as $error) {
                return '{"error": {"param": "' . $error->Name . '","message": "' . $error->Error . '"}}';
            }
        }
    }

}


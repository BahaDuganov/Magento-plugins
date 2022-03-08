<?php

namespace MyFatoorah\Myfatoorah\Controller\Actions;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;

class Redirect extends \Magento\Framework\App\Action\Action {

    protected $resultPageFactory;
    protected $checkoutSession;
    protected $CustomerSession;
    protected $cart;
    private $config;
    private $order;
    private $token;
    private $helper;
    protected $_messageManager;
    protected $_objectManager;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
            ScopeConfigInterface $config, \Magento\Framework\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory, CheckoutSession $checkoutSession, CustomerCart $cart
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->cart = $cart;
        $this->config = $config;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_messageManager = $context->getMessageManager();

        $this->payment_gateway = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/myfatoorah/payment_gateway');
        $this->helper = $this->_objectManager->get('\MyFatoorah\Myfatoorah\Helper\MyFatoorahAPI');

        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute() {
        /* CustomerDC Starts */
        if($this->getRequest()->getParam('quote_id')) {
            $quoteId = base64_decode($this->getRequest()->getParam('quote_id'));
            $this->checkoutSession->replaceQuote($this->_objectManager->get('\Magento\Quote\Model\Quote')->load($quoteId));
        }

        $quote = $this->checkoutSession->getQuote();

        $incrementId = $quote->reserveOrderId()->getReservedOrderId();

        $merchant_ReferenceID = $incrementId;
        $quote->reserveOrderId()->save();
        $qouteId = $quote->getId();
        $street_lines = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('customer/address/street_lines');
        $shippingAddress = $quote->getShippingAddress()->getStreet();
        if (count($shippingAddress) > $street_lines) {
            if ($this->helper->debug) {
                $this->helper->logger->info($quote->getId() . "Error -- Quote ID $qouteId -- Order ID $merchant_ReferenceID -- Shipping address contains only ' . $street_lines . ' line(s)");
            }
            $this->_messageManager->addErrorMessage(__('Please make sure Shipping address contains only ' . $street_lines . ' line(s)'));
            return $this->_redirect('onestepcheckout');
        }

        $billingAddress = $quote->getBillingAddress()->getStreet();
        if (count($billingAddress) > $street_lines) {
            if ($this->helper->debug) {
                $this->helper->logger->info($quote->getId() . "Error -- Quote ID $qouteId -- Order ID $merchant_ReferenceID -- Billing address contains only ' . $street_lines . ' line(s)");
            }
            $this->_messageManager->addErrorMessage(__('Please make sure Billing address contains only ' . $street_lines . ' line(s)'));
            return $this->_redirect('onestepcheckout');
        }

        if ($this->helper->debug) {
            $this->helper->logger->info($quote->getId() . "======================= START checking Adresses Quote ID $qouteId -- Order ID $merchant_ReferenceID ========================");
            $this->helper->logger->info($quote->getId() . "shipping address Quote ID $qouteId -- Order ID $merchant_ReferenceID " . json_encode($quote->getShippingAddress()->getData()));
            $this->helper->logger->info($quote->getId() . "Billing address Quote ID $qouteId -- Order ID $merchant_ReferenceID " . json_encode($quote->getBillingAddress()->getData()));
            $this->helper->logger->info($quote->getId() . "======================= END checking Adresses Quote ID $qouteId -- Order ID $merchant_ReferenceID ========================");
        }
        $ReturnURL = str_replace('?___SID=U', '', $this->_objectManager->create('Magento\Framework\UrlInterface')->getUrl('myfatoorah/actions/response?qoid='. base64_encode($qouteId.'305'), array('_secure' => true)));

        $product_name = '';
        $qty = '';

        $currencyCode = $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getCurrentCurrency()->getCode();
        $this->token = $this->helper->getToken();
        if (empty($this->token)) {
            if ($this->helper->debug) {
                $this->helper->logger->info($quote->getId() . "Error -- Quote ID $qouteId -- Order ID $merchant_ReferenceID -- API configuration is not correct");
            }
            $this->_messageManager->addError(__('MyFatoorah can not authorize your request. Please try again later or contact MyFatoorah Support'));
            return $this->_redirect('checkout/cart');
        }
        $currency = $this->helper->checkCountryAndCurrency($currencyCode);
        if (empty($currency)) {
            if ($this->helper->debug) {
                $this->helper->logger->info($quote->getId() . "Error -- Quote ID $qouteId -- Order ID $merchant_ReferenceID -- $currencyCode . ' is not supported by payment gateway.");
            }
            // go to checkout and err msg Curreny not supported with payment gateway
            $this->_messageManager->addError(__($currencyCode . ' is not supported by payment gateway.'));
            return $this->_redirect('checkout/cart');
        }

        $currencyIsoCode = $currency['iso'];
        $currencyId = $currency['id'];
        // 

        $getLocale = $this->_objectManager->get('Magento\Framework\Locale\Resolver');
        $haystack = $getLocale->getLocale();
        $lang = strstr($haystack, '_', true);
        $language = 2;
        switch ($lang) {
            case 'ar':
                $language = 1;
                break;
        }

        $currencyRate = (double) $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getCurrentCurrencyRate();
        $items = $quote->getAllVisibleItems();
        $invoiceItemsArr = array();

        $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
        $shipping = null;
        if ($shippingMethod == 'dhl_myfatoorah_dhl_myfatoorah') {
            $shipping = 1;
        }
        if ($shippingMethod == 'armx_myfatoorah_armx_myfatoorah') {
            $shipping = 2;
        }
        $curl_data['shipping'] = $shipping;
        $stockItem = $this->_objectManager->get('\Magento\CatalogInventory\Model\Stock\StockItemRepository');

        if ($shipping) {
            $discount = $quote->getShippingAddress()->getDiscountAmount();
            if ($discount != '0') {
                $invoiceItemsArr[] = array(
                    'ProductName' => 'Discount Amount',
                    "Description" => 'Discount Amount',
                    'weight' => 0,
                    'Width' => 1,
                    'Height' => 1,
                    'Depth' => 1,
                    'Quantity' => 1,
                    'UnitPrice' => $discount,
                );
            }
            // $tax = $quote->getShippingAddress()->getTaxAmount();
            // if ($tax != '0') {
            //     $invoiceItemsArr[] = array(
            //         'ProductName' => 'Tax Amount',
            //         "Description" => 'Tax Amount',
            //         'weight' => 0,
            //         'Width' => 1,
            //         'Height' => 1,
            //         'Depth' => 1,
            //         'Quantity' => 1,
            //         'UnitPrice' => $tax,
            //     );
            // }
            foreach ($items as $item) {
                $product_name = $item->getName();
                // print_r($item->getPriceInclTax() ); die; 
                // $itemPrice = $item->getPrice() * $currencyRate;
                $itemPrice = $item->getPriceInclTax() * $currencyRate;
                $qty = $item->getQty();
                $productStock = $stockItem->get($productId);

                $inStock = $productStock->getData()['is_in_stock'];
                if (!$inStock) {
                    return $this->_redirect('checkout/cart');
                }
                $invoiceItemsArr[] = array(
                    'ProductName' => $product_name,
                    "Description" => $product_name,
                    'weight' => $item->getWeight(),
                    'Width' => 1,
                    'Height' => 1,
                    'Depth' => 1,
                    'Quantity' => $qty,
                    'UnitPrice' => $itemPrice,
                );
            }
            $Firstname = $quote->getShippingAddress()->getFirtname();
            $Lastname = $quote->getShippingAddress()->getLastname();
            $customerMobile = $quote->getShippingAddress()->getTelephone();
            $customerEmail = $quote->getShippingAddress()->getEmail();

            $country = $quote->getShippingAddress()->getCountry();
            $city = $quote->getShippingAddress()->getCity();
            $streetAddr = $quote->getShippingAddress()->getStreet();
            $address = null;
            foreach ($streetAddr as $value) {
                $address .= ' ' . $value;
            }
            // json data 
            $curl_data['data'] = '{
              "ShippingMethod": "' . $shipping . '",
              "Items":  ' . json_encode($invoiceItemsArr) . ',
              "Consignee": {
                "PersonName": "' . $Firstname . ' ' . $Lastname . '",
                "Mobile": "' . substr($customerMobile, -10) . '",
                "EmailAddress": "' . $customerEmail . '",
                "LineAddress": "' . $address . ' ",
                "CityName": "' . $city . '",
                "CountryCode": "' . $country . '"
              },
              "DisplayCurrencyId": "' . $currencyId . '",
              "SendInvoiceOption": 2,
              "Language": ' . $language . ',
              "CallBackUrl": "' . ($ReturnURL) . '",
              "ErrorUrl": "' . ($ReturnURL) . '"
             }';
        } else {
            $Firstname = $quote->getCustomerFirstname();
            $Lastname = $quote->getCustomerLastname();
            $customerEmail = $quote->getCustomerEmail();
            // Check if any customer is logged in or not
            if ($quote->getCustomerId()) {
                // Change shipping address by billing address
                $customerMobile = !empty($quote->getBillingAddress()->getTelephone()) ? $quote->getBillingAddress()->getTelephone() : $quote->getShippingAddress()->getTelephone();
            } else {
                $Firstname = !empty($quote->getBillingAddress()->getFirtname()) ? $quote->getBillingAddress()->getFirtname() : $quote->getShippingAddress()->getFirtname();
                $Lastname = !empty($quote->getBillingAddress()->getLastname()) ? $quote->getBillingAddress()->getLastname() : $quote->getShippingAddress()->getLastname();
                $customerMobile = !empty($quote->getBillingAddress()->getTelephone()) ? $quote->getBillingAddress()->getTelephone() : $quote->getShippingAddress()->getTelephone();

                $customerEmail = $quote->getShippingAddress()->getEmail();
            }

            $country = !empty($quote->getBillingAddress()->getCountry()) ? $quote->getBillingAddress()->getCountry() : $quote->getShippingAddress()->getCountry();
            $countryId = $this->helper->checkCountryAndCurrency($country, true);
            if ($countryId == null) {
                $countryId = 0;
            }
            $customerName = $Firstname . ' ' . $Lastname;
            // $qouteId = $quote->getId();
            //  print_r(  $incrementId); die;

            if(!$Firstname && !$Lastname && $this->checkoutSession->getCustomerName()) {
                $customerName = $this->checkoutSession->getCustomerName();
            }

            if(!$customerEmail && $this->checkoutSession->getCustomerEmail()) {
                $customerEmail = $this->checkoutSession->getCustomerEmail();
            }

            foreach ($items as $item) {
                // print_r($item->getPriceInclTax() ); die; 
                $product_name = $item->getName();
                // $itemPrice = $item->getPrice() * $currencyRate;
                $itemPrice = $item->getPriceInclTax() * $currencyRate;
                $qty = $item->getQty();
                $productId = $item->getProductId(); // YOUR PRODUCT ID
                $productStock = $stockItem->get($productId);
                $inStock = $productStock->getData()['is_in_stock'];
                if (!$inStock) {
                    return $this->_redirect('checkout/cart');
                }
                $invoiceItemsArr[] = array('ProductId' => '', 'ProductName' => $product_name, 'Quantity' => $qty, 'UnitPrice' => $itemPrice);
            }

            $shipping = $quote->getShippingAddress()->getShippingAmount() + $quote->getShippingAddress()->getShippingTaxAmount();
            if ($shipping && $shipping != '0') {
                $invoiceItemsArr[] = array('ProductId' => '', 'ProductName' => 'Shipping Amount', 'Quantity' => 1, 'UnitPrice' => $shipping);
            }

            $discount = $quote->getShippingAddress()->getDiscountAmount();
            if ($discount && $discount != '0') {
                $invoiceItemsArr[] = array('ProductId' => '', 'ProductName' => 'Discount Amount', 'Quantity' => 1, 'UnitPrice' => $discount);
            }
            // $tax = $quote->getShippingAddress()->getTaxAmount();
            // if ($tax != '0') {
            //     $invoiceItemsArr[] = array('ProductId' => '', 'ProductName' => 'Tax Amount', 'Quantity' => 1, 'UnitPrice' => $tax);
            // }
//echo $this->checkTelephone($customerMobile); die; 
// json data 
            $curl_data['data'] = '{
	  "InvoiceValue": ' . ltrim($merchant_ReferenceID, '0') . ',
	  "CustomerName": "' . $customerName . '",
	  "CustomerBlock": "string",
	  "CustomerStreet": "string",
	  "CustomerHouseBuildingNo": "string",
	  "CustomerCivilId": "2",
	  "CustomerAddress": "string",
	  "CustomerReference": "' . $merchant_ReferenceID . '",
	  "DisplayCurrencyIsoAlpha":"' . $currencyIsoCode . '",
	  "CountryCodeId": "' . $countryId . '",
	  "CustomerMobile": "' . $this->checkTelephone($customerMobile) . '",
	  "CustomerEmail": "' . $customerEmail . '",
	  "SendInvoiceOption": 2,
	  "InvoiceItemsCreate": ' . json_encode($invoiceItemsArr) . ',
	  "CallBackUrl": "' . $ReturnURL . '",
	  "Language": "' . $language . '",
	  "ExpireDate": "' . gmdate("D, d M Y H:i:s", time() + 7 * 24 * 60 * 60) . '",
          "ApiCustomFileds": "string",
          "ErrorUrl": "' . $ReturnURL . '"
	}';
        }
        // echo "<pre>";
        // print_r($curl_data);
        // die;
        if ($this->helper->debug) {
            $this->helper->logger->info("CURL DATA -- Quote ID $qouteId -- Order ID $merchant_ReferenceID -- " . json_encode($curl_data));
        }
        $result = $this->helper->createInvoice($curl_data,$qouteId,$merchant_ReferenceID);
//  print_r($result);
//        die;
        if (isset($result->IsSuccess) && $result->IsSuccess) {
            $redirectUrl = $result->RedirectUrl;

            foreach ($result->PaymentMethods as $PaymentMethod) {
                if ($PaymentMethod->PaymentMethodCode == $this->payment_gateway) {

                    $redirectUrl = $PaymentMethod->PaymentMethodUrl;
                    if ($this->helper->debug) {
                        $this->helper->logger->info($quote->getId() . "======================= Start Payment Quote ID $qouteId -- Order ID $merchant_ReferenceID ========================");
                        $this->helper->logger->info($quote->getId() . "MyFatoorah request Quote ID $qouteId -- Order ID $merchant_ReferenceID " . json_encode($curl_data));
                        $this->helper->logger->info($quote->getId() . "Default gateway for Quote ID $qouteId -- Order ID $merchant_ReferenceID - from settings is  " . $this->payment_gateway);
                        $this->helper->logger->info($quote->getId() . "Redirect URL to Pay for Quote ID $qouteId -- Order ID $merchant_ReferenceID  " . $redirectUrl);
                        $this->helper->logger->info($quote->getId() . "shipping address for Quote ID $qouteId -- Order ID $merchant_ReferenceID  " . json_encode($quote->getShippingAddress()->getData()));
                        $this->helper->logger->info($quote->getId() . "Billing address for Quote ID $qouteId -- Order ID $merchant_ReferenceID " . json_encode($quote->getBillingAddress()->getData()));
                    }
                }
            }


            // echo $redirectUrl; die;
            header('Location:  ' . $redirectUrl);
            exit();
        } else {
            if ($this->helper->debug) {
                $this->helper->logger->info("Returned API request Error -- Quote ID $qouteId -- Order ID $merchant_ReferenceID " . json_encode($result->FieldsErrors));
            }
            foreach ($result->FieldsErrors as $error) {
                $this->_messageManager->addError(__($error->Name . ' ' . $error->Error));
            }
            return $this->_redirect('checkout/cart');
        }
    }

    function checkTelephone($phone) {
        $code = array('+973', '+965', '+968', '+974', '+962', '+966', '+971', '00973', '00965', '00968', '00974', '00962', '00966', '00971');
        $result = trim($phone);
        foreach ($code as $value) {
            if (strpos($phone, $value) !== false) {
                $result = str_replace($value, '', trim($phone));
            }
        }
        return $result;
    }

    protected function _getDirectPostSession() {
        return $this->_objectManager->get('Magento\Authorizenet\Model\Directpost\Session');
    }

}

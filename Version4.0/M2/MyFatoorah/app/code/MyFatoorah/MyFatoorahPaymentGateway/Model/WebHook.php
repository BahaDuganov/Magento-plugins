<?php

namespace MyFatoorah\MyFatoorahPaymentGateway\Model;

use Magento\Framework\App\ObjectManager;
use MyFatoorah\MyFatoorahPaymentGateway\Controller\Checkout\Success;
use Magento\Sales\Model\Order;
use MyFatoorah\Library\PaymentMyfatoorahApiV2;

class WebHook {

    private $successObj;

//-----------------------------------------------------------------------------------------------------------------------------------------
    public function __construct() {

        $objectManager    = ObjectManager::getInstance();
        $this->successObj = $objectManager->get(Success::class);

        $this->ScopeConfigInterface = $objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->scopeStore           = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $this->orderCollection = $objectManager->create(Order::class);

        $this->log = new \Zend\Log\Logger();
        $this->log->addWriter(new \Zend\Log\Writer\Stream(BP . '/var/log/myfatoorah.log'));
    }

//-----------------------------------------------------------------------------------------------------------------------------------------    

    /**
     * {@inheritdoc}
     */
    public function execute($EventType, $Event, $DateTime, $CountryIsoCode, $Data) {

        //to allow the callback code run 1st. 
        sleep(30);

        if ($EventType != 1) {
            exit();
        }

        $this->TransactionsStatusChanged($Data);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function TransactionsStatusChanged($data) {

        $orderId = $data['CustomerReference'];
        try {
            //get the order to get its store
            $order = $this->orderCollection->loadByIncrementId($orderId);
            if (!$order->getId()) {
                throw new \Exception('MyFatoorah returned an order that could not be retrieved');
            }

            //get the order store config
            $storeId          = $order->getStoreId();
            $apiKey           = $this->ScopeConfigInterface->getValue('payment/myfatoorah_gateway/api_key', $this->scopeStore, $storeId);
            $isTesting        = $this->ScopeConfigInterface->getValue('payment/myfatoorah_gateway/is_testing', $this->scopeStore, $storeId);
            $webhookSecretKey = $this->ScopeConfigInterface->getValue('payment/myfatoorah_gateway/webhookSecretKey', $this->scopeStore, $storeId);


            //get lib object
            $myfatoorah                 = new PaymentMyfatoorahApiV2($apiKey, $isTesting, $this->log, 'info');
            $myfatoorah->webhookLogPath = BP . '/var/log/'; //@todo remove the logWebhook function
            //get MyFatoorah-Signature from request headers
            $request_headers     = apache_request_headers();
            $myfatoorahSignature = $request_headers['MyFatoorah-Signature'];


            //validate signature
            $myfatoorah->validateSignature($data, $webhookSecretKey, $myfatoorahSignature);


            //update order status
            $this->successObj->checkStatus($data['InvoiceId'], 'InvoiceId', $myfatoorah, '-WebHook');
        } catch (\Exception $ex) {
            $this->log->info("Order #$orderId ----- WebHook - Excption " . $ex->getMessage());
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}

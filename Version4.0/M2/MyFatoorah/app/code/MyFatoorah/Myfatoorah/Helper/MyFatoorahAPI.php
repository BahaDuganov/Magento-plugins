<?php

namespace MyFatoorah\Myfatoorah\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class MyFatoorahAPI extends AbstractHelper {

    public $token;
    public $debug;
    public $logger;
    public function __construct() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->merchant_username = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/myfatoorah/merchant_username');
        $this->merchant_password = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/myfatoorah/merchant_password');
        $url = parse_url($objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/myfatoorah/paygateway_url'));
        $this->paygateway_url = $url['scheme'] . '://' . $url['host'];
        $this->token = '';
        $this->debug = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/myfatoorah/debug');
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/myfatoorah.log');
        $this->logger = new \Zend\Log\Logger();
        $this->logger->addWriter($writer);

    }

    public function getToken() {

        $url_token = $this->paygateway_url . '/Token';
        $client_id = $this->merchant_username;
        $client_secret = $this->merchant_password;
        $params = "grant_type=password"
                . "&username=" . $client_id
                . "&password=" . $client_secret;

        $curl = curl_init($url_token);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        $json_response = curl_exec($curl);

        $response = json_decode($json_response, true);

        if (isset($response['token_type']))
            $this->token = $response['token_type'] . ' ' . $response['access_token'];
        return $this->token;
    }

    public function checkCountryAndCurrency($data, $country = false) {
        $url = $this->paygateway_url . '/AuthLists/GetCountiesWithIso';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Content-Length: 0",
            "Authorization: " . $this->token
        ));

        $res = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        $result = json_decode($res);
        curl_close($ch);
        if ($httpcode == 200) {
            foreach ($result as $value) {
                // if (str_contains($value->IsoCurrencyCodeAlpha, $data) !== false || str_contains($value->Currency, $data) !== false) {
                if (strcmp(trim($value->IsoCurrencyCodeAlpha), $data) || strcmp(trim($value->Currency), $data)) {
                    if ($country){
                        return $value->CountryId;
                    } else {
                        return array('iso' => $value->IsoCurrencyCodeAlpha, 'id' => $value->CountryId);  
                    }
                    
                    exit;
                }
            }
        }
        return false;
    }

    public function createInvoice($curl_data, $qouteId, $orderId) {
        // call rest api 
        if (isset($curl_data['shipping'])&&$curl_data['shipping'] == true)
            $api_invoice = $this->paygateway_url . '/ApiShipping/CreateShippingInvoice';
        else
            $api_invoice = $this->paygateway_url . '/ApiInvoices/CreateInvoiceIso';

        $result = '';
        do {
            $retry = false;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_invoice);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_data['data']);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Accept: application/json",
                "Authorization: $this->token"
            ));

            $res = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            if ($this->debug) {
                $this->logger->info("CURL DATA -- Quote ID $qouteId -- Order ID $orderId -- MyFatoorah create invoice response  " . $res);
            }
            // echo "<pre>";
            //print_r( $result); die;
            if ($httpcode === 401) { // unauthorized
                $this->token = $this->getToken();
                $retry = true;
            }
            curl_close($ch);
            return json_decode($res);
        } while ($retry);
    }

    public function responseMyFatoorah($paymentId) {
        $url = $this->paygateway_url . '/ApiInvoices/Transaction/' . $paymentId;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization:" . $this->getToken()
        ));

        $json_response = curl_exec($curl);
        $err = curl_error($curl);
        $response = json_decode($json_response, true);
        curl_close($curl);
        return $response;
    }


}

<?php

namespace MyFatoorah\MyFatoorahPaymentGateway\Plugin;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order;

class OrderSenderPlugin {

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function aroundSend(OrderSender $subject, callable $proceed, Order $order, $forceSyncMode = false) {
        $payment = $order->getPayment()->getMethodInstance()->getCode();

        if ($payment === 'myfatoorah_gateway' && $order->getState() === Order::STATE_PENDING_PAYMENT) {
            return false;
        }

        return $proceed($order, $forceSyncMode);
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
}

<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MyFatoorah\MyFatoorahPaymentGateway\Helper;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;

/**
 * Checkout workflow helper
 *
 * Class Checkout
 * @package MyFatoorah\MyFatoorahPaymentGateway\Helper
 */
class Checkout {

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;

    /**
     * @param \Magento\Checkout\Model\Session $session
     */
    public function __construct(
            Session $session
    ) {
        $this->session = $session;
    }

    /**
     * Cancel last placed order with specified comment message
     *
     * @param string $comment Comment appended to order history
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return bool True if order cancelled, false otherwise
     */
    public function cancelCurrentOrder($comment, $orderId = null ) {
        $order = $this->session->getLastRealOrder();

        if (!$order || !$order->getId() && $orderId) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $objectManager->create('Magento\Sales\Model\OrderFactory')->create()->loadByIncrementId($orderId);
        }

        if ($order && $order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation('MyFatoorah: ' . $comment)->save();

            return true;
        }

        return false;
    }

    /**
     * Restores quote (restores cart)
     *
     * @return bool
     */
    public function restoreQuote() {
        return $this->session->restoreQuote();
    }

}

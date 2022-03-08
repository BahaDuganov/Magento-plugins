<?php

namespace MyFatoorah\Myfatoorah\Controller\Actions;

use Magento\Paypal\Model\Config;
use Magento\Sales\Model\Order;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\Controller\ResultFactory;

class Response extends \Magento\Framework\App\Action\Action {

    protected $resultPageFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Paypal\Model\PayflowlinkFactory
     */
    protected $_payflowModelFactory;

    /**
     * @var \Magento\Paypal\Helper\Checkout
     */
    protected $_checkoutHelper;
    protected $allowedOrderStates = [
        Order::STATE_PROCESSING,
        Order::STATE_COMPLETE,
    ];
    protected $cartManagement;
    protected $quote;
    protected $resultRedirect;
    protected $_messageManager;
    private $helper;
    private $registry;
    /**
     * Payment method code
     * @var string
     */
    protected $allowedPaymentMethodCodes = [
        Config::METHOD_PAYFLOWPRO,
        Config::METHOD_PAYFLOWLINK
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
    \Magento\Framework\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \Magento\Checkout\Model\Session $checkoutSession, \Magento\Sales\Model\OrderFactory $orderFactory, \Magento\Paypal\Model\PayflowlinkFactory $payflowModelFactory, \Magento\Paypal\Helper\Checkout $checkoutHelper, \Psr\Log\LoggerInterface $logger, CartManagementInterface $cartManagement, Quote $quote
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_logger = $logger;
        $this->_payflowModelFactory = $payflowModelFactory;
        $this->_checkoutHelper = $checkoutHelper;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	$this->helper = $objectManager->get('\MyFatoorah\Myfatoorah\Helper\MyFatoorahAPI');
        parent::__construct($context);
        $this->_messageManager = $context->getMessageManager();
        $this->cartManagement = $cartManagement;
        $this->quote = $quote;
        $this->resultRedirect = $context->getResultFactory();
        $this->registry = $objectManager->get('\Magento\Framework\Registry');
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute() {

        $paymentId = $this->getRequest()->getParam('paymentId', false);
	$response = $this->helper->responseMyFatoorah($paymentId);

        if ($response['TransactionStatus'] === 2) {

            // place order
            $this->quote = $this->_checkoutSession->getQuote();

            var_dump($this->registry->registry('quote_id')); die();

            $this->quote->getPayment()->setMethod('myfatoorah');
           // $this->quote->setCustomerEmail($this->quote->getCustomerEmail());
        	$this->quote->setCustomerEmail($this->quote->getShippingAddress()->getEmail());

            if ($this->quote->getCustomerId()) {
                
            } else {
                $this->quote->setCheckoutMethod('guest')
                        ->setCustomerId(null);
            }
            try {
                $natiga = $this->cartManagement->placeOrder($this->quote->getId());
		/*$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                	$incrementId = $this->quote->getReservedOrderId();
		//echo $incrementId; die; 
        	//$incrementId = $this->checkoutSession->getLastOrderIncrementId();
        	$order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($incrementId);
                $objectManager->create('\Magento\Sales\Model\OrderNotifier')
                    ->notify($order);*/
            } catch (\Exception $e) {
                echo $e;
                die();
            }

            // redirect to success page
            $resultRedirect = $this->resultRedirect->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/onepage/success');
            return $resultRedirect;
        } else {
            // cancelled order
		$this->_messageManager->addError(__('Error: '.$response['Error']));
           	return $this->_redirect('checkout/cart');
        }
    }
   
    /**
     * Check order state
     *
     * @param Order $order
     * @return bool
     */
    protected function checkOrderState(Order $order) {
        return in_array($order->getState(), $this->allowedOrderStates);
    }

    /**
     * Check requested payment method
     *
     * @param Order $order
     * @return bool
     */
    protected function checkPaymentMethod(Order $order) {
        $payment = $order->getPayment();
        return in_array($payment->getMethod(), $this->allowedPaymentMethodCodes);
    }

    protected function _cancelPayment($errorMsg = '') {
        $errorMsg = trim(strip_tags($errorMsg));

        $gotoSection = false;
        $this->_checkoutHelper->cancelCurrentOrder($errorMsg);
        if ($this->_checkoutSession->restoreQuote()) {
            //Redirect to payment step
            $gotoSection = 'paymentMethod';
        }

        return $gotoSection;
    }

}

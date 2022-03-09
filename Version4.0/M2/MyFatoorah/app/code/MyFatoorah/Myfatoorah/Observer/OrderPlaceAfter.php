<?php
namespace MyFatoorah\Myfatoorah\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;


class OrderPlaceAfter implements ObserverInterface {
	
	public $orderRepository;

	public function __construct(
    \Magento\Sales\Model\OrderRepository $orderRepository
	){
    $this->orderRepository = $orderRepository;
	}

    public function execute(Observer $observer){

        $orderId = $observer->getEvent()->getOrderIds();
 
        $order = $this->orderRepository->get($orderId[0]);

         if ($order->getEntityId()) {
            $items = $order->getItemsCollection();
         }
    }
}
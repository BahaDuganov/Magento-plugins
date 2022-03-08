<?php 
namespace MyFatoorah\Myfatoorah\Api;
 
 
interface OrderManagementInterface {

    /**
     * Returns payment status
     *
     * @api
     * @param int $cartId cart ID.     
     * @param int $billingAddressId billing Address ID.
     * @return mixed.
     */
     public function checkoutOrder($cartId, $billingAddressId);
}


<?php

/**

 * Copyright © 2016 Magento. All rights reserved.

 * See COPYING.txt for license details.

 */
namespace MyFatoorah\Myfatoorah\Model\Adminhtml\Source;
use Magento\Payment\Model\Method\AbstractMethod;



/**

 * Class GatewayAction

 */

class GatewayAction implements \Magento\Framework\Option\ArrayInterface

{

    /**

     * {@inheritdoc}

     */

    public function toOptionArray()

    {
        return array(
            array('value' => 'myfatoorah', 'label' =>'MyFatoorah'),
            array('value' => 'md', 'label' => 'Mada KSA'),
            array('value' => 'vm', 'label' => 'Visa / Master'),
            array('value' => 'kn', 'label' =>'Knet'),
            array('value' => 'b', 'label' => 'Benefit'),
            array('value' => 'np', 'label' => 'Qatar Debit Card - NAPS'),
            array('value' => 'uaecc', 'label' => 'Debit Cards UAE - VISA UAE'),
            array('value' => 's', 'label' => 'Sadad'),
            array('value' => 'ae', 'label' => 'AMEX'),
            
        );

    }
  

}


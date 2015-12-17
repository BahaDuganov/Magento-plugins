<?php
/**
 * Magestore
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category 	Magestore
 * @package 	Magestore_Simiaffiliatescoupon
 * @copyright 	Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license 	http://www.magestore.com/license-agreement.html
 */

 /**
 * Simiaffiliatescoupon Index Controller
 * 
 * @category 	Magestore
 * @package 	Magestore_Simiaffiliatescoupon
 * @author  	Magestore Developer
 */
class Simi_Simiaffiliatescoupon_IndexController extends Mage_Core_Controller_Front_Action
{
	/**
     * checkInstall action
     */
    public function checkInstallAction()
    {		
        echo "1";
		exit();
    }

    /**
     * checkInstall action
     */
    public function test2Action()
    {		
        Mage::helper('simiaffiliatescoupon')->getAffiliatesDiscount();
    }
}
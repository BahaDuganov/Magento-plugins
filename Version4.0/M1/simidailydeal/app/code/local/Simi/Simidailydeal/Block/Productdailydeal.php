<?php

class Simi_Simidailydeal_Block_Productdailydeal extends Mage_Core_Block_Template

{
	public function getProduct()
	{
		return Mage::registry('current_product');
	}

	public function getDealByProduct($productId)
	{
		return Mage::getModel('simidailydeal/dailydeal')->getDealByProduct($productId);
	}
}
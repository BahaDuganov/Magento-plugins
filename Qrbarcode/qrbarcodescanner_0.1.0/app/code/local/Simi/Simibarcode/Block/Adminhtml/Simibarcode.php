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
 * @package 	Magestore_Simibarcode
 * @copyright 	Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license 	http://www.magestore.com/license-agreement.html
 */

 /**
 * Simibarcode Adminhtml Block
 * 
 * @category 	Magestore
 * @package 	Magestore_Simibarcode
 * @author  	Magestore Developer
 */
class Simi_Simibarcode_Block_Adminhtml_Simibarcode extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_simibarcode';
		$this->_blockGroup = 'simibarcode';
		$this->_headerText = Mage::helper('simibarcode')->__('Manage QR & Barcodes');
		$this->_addButtonLabel = Mage::helper('simibarcode')->__('Add New Custom QR & Barcode');
		parent::__construct();
	}
}
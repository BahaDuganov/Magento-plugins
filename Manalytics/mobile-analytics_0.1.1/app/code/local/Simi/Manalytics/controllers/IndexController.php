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
 * @package 	Magestore_Manalytics
 * @copyright 	Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license 	http://www.magestore.com/license-agreement.html
 */

 /**
 * Manalytics Index Controller
 * 
 * @category 	Magestore
 * @package 	Magestore_Manalytics
 * @author  	Magestore Developer
 */
class Simi_Manalytics_IndexController extends Mage_Core_Controller_Front_Action
{
	/**
	 * index action
	 */
	public function checkInstallAction(){
		echo "1";
		exit();
	}
	public function get_ga_idAction(){
		 $information = Mage::getModel('manalytics/manalytics')->getGAId();
        $this->_printDataJson($information);
	}
    
    
    public function installDbAction() {
        $setup = new Mage_Core_Model_Resource_Setup();
        $installer = $setup;
        $installer->startSetup();

        $installer->run("

        DROP TABLE IF EXISTS {$this->getTable('simireport_transactions')};

        CREATE TABLE {$this->getTable('simireport_transactions')} (
        `transaction_id` int(11) unsigned NOT NULL auto_increment,
        `order_id` int(30),  
        PRIMARY KEY (`transaction_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        
        ");
        
        $installer->endSetup();
        echo "success";
    }
        
}
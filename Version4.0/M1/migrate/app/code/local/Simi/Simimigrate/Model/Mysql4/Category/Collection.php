<?php

class Simi_Simimigrate_Model_Mysql4_Category_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    public function _construct() {
        parent::_construct();
        $this->_init('simimigrate/category');
    }

}

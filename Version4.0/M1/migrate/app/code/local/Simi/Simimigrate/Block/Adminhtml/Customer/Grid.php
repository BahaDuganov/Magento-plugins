<?php

/**

 */
class Simi_Simimigrate_Block_Adminhtml_Customer_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('customerGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::helper('simimigrate')
                    ->joinAppConfigTable(Mage::getModel('simimigrate/customer')->getCollection());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('entity_id', array(
            'header' => Mage::helper('simimigrate')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'entity_id',
        ));

        $this->addColumn('customer_id', array(
            'header' => Mage::helper('simimigrate')->__('Customer Id'),
            'width' => '150px',
            'index' => 'customer_id'
        ));
        
        $this->addColumn('group_id', array(
            'header' => Mage::helper('simimigrate')->__('Group Id'),
            'width' => '250px',
            'index' => 'group_id'
        ));
        
        $this->addColumn('email', array(
            'header' => Mage::helper('simimigrate')->__('Customer Email'),
            'width' => '250px',
            'index' => 'email'
        ));
        
        $this->addColumn('simicart_app_config_id', array(
            'header' => Mage::helper('simimigrate')->__('SimiCart App Id'),
            'width' => '150px',
            'index' => 'simicart_app_config_id'
        ));
        
        $this->addColumn('simicart_customer_id', array(
            'header' => Mage::helper('simimigrate')->__('SimiCart User Id'),
            'width' => '150px',
            'index' => 'simicart_customer_id'
        ));
        
        $this->addColumn('user_email', array(
            'header' => Mage::helper('simimigrate')->__('SimiCart User Email'),
            'width' => '150px',
            'index' => 'user_email'
        ));
        
        $this->addColumn('action', array(
            'header' => Mage::helper('simimigrate')->__('Action'),
            'width' => '80px',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('simimigrate')->__('View'),
                    'url' => array('base' => '*/*/edit'),
                    'field' => 'id'
                )),
            'filter' => false,
            'sortable' => false,
            'index' => 'stores',
            'is_system' => true,
        ));
        return parent::_prepareColumns();
    }

    /**
     * prepare mass action for this grid
     *
     * @return Magestore_Madapter_Block_Adminhtml_Madapter_Grid
     */
    protected function _prepareMassaction() {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('entity_id');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('simimigrate')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('simimigrate')->__('Are you sure?')
        ));

        return $this;
    }

    /**
     * get url for each row in grid
     *
     * @return string
     */
    public function getRowUrl($row) {
        return $this->getUrl('*/*/edit', array('entity_id' => $row->getId()));
    }

}

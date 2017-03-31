<?php

class Simi_Simidailydeal_Block_Adminhtml_Simidailydeal_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct(){
		parent::__construct();
		$this->setId('simidailydealGrid');
		$this->setDefaultSort('simidailydeal_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
	}

	protected function _prepareCollection(){
		$collection = Mage::getModel('simidailydeal/simidailydeal')->getCollection()
                        ->addFieldToFilter('is_random','0');;
		$this->setCollection($collection);
		return parent::_prepareCollection();
	}

	protected function _prepareColumns(){
		$this->addColumn('id', array(
			'header'	=> Mage::helper('simidailydeal')->__('ID'),
			'align'	 =>'right',
			'width'	 => '50px',
			'index'	 => 'id',
		));

		$this->addColumn('title', array(
			'header'	=> Mage::helper('simidailydeal')->__('Title'),
			'align'	 =>'left',
			'index'	 => 'title',
		));

                $this->addColumn('product_name', array(
			'header'	=> Mage::helper('simidailydeal')->__('Product name'),
			'width'	 => '150px',
                        'renderer'  => 'simidailydeal/adminhtml_simidailydeal_renderer_product',
			'index'	 => 'product_name',
		));
                $this->addColumn('save', array(
			'header'	=> Mage::helper('simidailydeal')->__('Save'),
			'width'	 => '150px',
			'index'	 => 'save',
                        'renderer'  => 'simidailydeal/adminhtml_randomdeal_renderer_save',
                        'type'	 =>'number',
		));
                $this->addColumn('deal_price', array(
			'header'	=> Mage::helper('simidailydeal')->__('Deal price'),
			'width'	 => '150px',
			'index'	 => 'deal_price',
                        'type'  => 'currency',
                        'currency_code' => (string) Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
		));
                $this->addColumn('quantity', array(
			'header'	=> Mage::helper('simidailydeal')->__('Quantity'),
			'width'	 => '150px',
			'index'	 => 'quantity',
                        'type'	 =>'number',
		));
                $this->addColumn('sold', array(
			'header'	=> Mage::helper('simidailydeal')->__('Sold'),
			'width'	 => '150px',
			'index'	 => 'sold',
                        'type'	 =>'number',
		));

               $this->addColumn('start_time', array(
			'header'	=> Mage::helper('simidailydeal')->__('Start time'),
			'width'	 => '150px',
			'index'	 => 'start_time',
                        'type'	 =>'datetime',
		));
               $this->addColumn('close_time', array(
			'header'	=> Mage::helper('simidailydeal')->__('Close time'),
			'width'	 => '150px',
			'index'	 => 'close_time',
                        'type'	 =>'datetime',
		));


		$this->addColumn('status', array(
			'header'	=> Mage::helper('simidailydeal')->__('Status'),
			'align'	 => 'left',
			'width'	 => '80px',
			'index'	 => 'status',
			'type'		=> 'options',
			'options'	 => array(
				1 => 'Coming',
				3 => 'Active',
                                4 => 'Expired',
                                2 => 'Disable',
			),
		));

		$this->addColumn('action',
			array(
				'header'	=>	Mage::helper('simidailydeal')->__('Action'),
				'width'		=> '100',
				'type'		=> 'action',
				'getter'	=> 'getId',
				'actions'	=> array(
					array(
						'caption'	=> Mage::helper('simidailydeal')->__('Edit'),
						'url'		=> array('base'=> '*/*/edit'),
						'field'		=> 'id'
					)),
				'filter'	=> false,
				'sortable'	=> false,
				'index'		=> 'stores',
				'is_system'	=> true,
		));

		$this->addExportType('*/*/exportCsv', Mage::helper('simidailydeal')->__('CSV'));
		$this->addExportType('*/*/exportXml', Mage::helper('simidailydeal')->__('XML'));

		return parent::_prepareColumns();
	}

	protected function _prepareMassaction(){
		$this->setMassactionIdField('id');
		$this->getMassactionBlock()->setFormFieldName('simidailydeal');

		$this->getMassactionBlock()->addItem('delete', array(
			'label'		=> Mage::helper('simidailydeal')->__('Delete'),
			'url'		=> $this->getUrl('*/*/massDelete'),
			'confirm'	=> Mage::helper('simidailydeal')->__('Are you sure?')
		));

		$statuses = Mage::getSingleton('simidailydeal/status')->getOptionArray();

		array_unshift($statuses, array('label'=>'', 'value'=>''));
		$this->getMassactionBlock()->addItem('status', array(
			'label'=> Mage::helper('simidailydeal')->__('Change status'),
			'url'	=> $this->getUrl('*/*/massStatus', array('_current'=>true)),
			'additional' => array(
				'visibility' => array(
					'name'	=> 'status',
					'type'	=> 'select',
					'class'	=> 'required-entry',
					'label'	=> Mage::helper('simidailydeal')->__('Status'),
					'values'=> $statuses
				))
		));
		return $this;
	}

	public function getRowUrl($row){
		return $this->getUrl('*/*/edit', array('id' => $row->getId()));
	}
}
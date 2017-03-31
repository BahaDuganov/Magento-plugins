<?php

class Simi_Simidailydeal_Block_Adminhtml_Randomdeal_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form(array(
                                      'id' => 'edit_form',
                                      'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
                                      'method' => 'post',
        							  'enctype' => 'multipart/form-data'
                                   )
      );
Mage::app()->getLayout()->getBlockSingleton('core/messages')->addNotice('test');
      $form->setUseContainer(true);
      $this->setForm($form);
      return parent::_prepareForm();
  }
}
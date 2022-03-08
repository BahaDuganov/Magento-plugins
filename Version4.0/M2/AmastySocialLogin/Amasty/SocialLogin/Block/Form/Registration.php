<?php

namespace Amasty\SocialLogin\Block\Form;

use Magento\LoginAsCustomerAssistance\ViewModel\ShoppingAssistanceViewModel;

class Registration extends \Magento\Customer\Block\Form\Register
{
    /**
     * @var array
     */
    private $formRenderers = [
        'text',
        'textarea',
        'multiline',
        'date',
        'select',
        'multiselect',
        'boolean',
        'file',
        'image'
    ];

    /**
     * @return Registration|\Magento\Customer\Block\Form\Register|\Magento\Framework\View\Element\AbstractBlock
     */
    public function _prepareLayout()
    {
        $parent = $this->getParentBlock();

        return $parent ? $parent->_prepareLayout() : $this;
    }

    public function toHtml()
    {
        if ($this->_moduleManager->isEnabled('Magento_CustomerCustomAttributes')) {
            $this->changePopupTemplate();
        }

        if ($this->_moduleManager->isEnabled('Magento_LoginAsCustomerAssistance')) {
            $this->addLoginAsCustomerAssistance();
        }

        return parent::toHtml();
    }

    private function addLoginAsCustomerAssistance(): void
    {
        $this->addChild(
            'fieldset_create_info_additional',
            \Magento\Framework\View\Element\Template::class,
            [
                'template' => 'Magento_LoginAsCustomerAssistance::shopping-assistance.phtml',
                'view_model' => $this->getObject(ShoppingAssistanceViewModel::class)
            ]
        );
    }

    private function changePopupTemplate()
    {
        $this->setTemplate('Magento_CustomerCustomAttributes::/customer/form/register.phtml');
        $formBlock = $this->addChild(
            'customer_form_user_attributes',
            \Magento\CustomerCustomAttributes\Block\Form::class
        );

        if (!$this->getLayout()->getBlock('customer_form_template')) {
            $customerFormTemplate = $this->getLayout()
                ->createBlock(\Magento\Framework\View\Element\Template::class, 'customer_form_template');

            foreach ($this->formRenderers as $formRenderer) {
                $customerFormTemplate->addChild(
                    $formRenderer,
                    'Magento\\CustomAttributeManagement\\Block\\Form\\Renderer\\' . ucfirst($formRenderer)
                )->setTemplate('Magento_CustomerCustomAttributes::form/renderer/' . $formRenderer . '.phtml');
            }
        }

        $formBlock->addData(['view_model' => $this->getObject('CustomerFileAttribute')])
            ->setEntityType('customer')
            ->setEntityModelClass(\Magento\Customer\Model\Customer::class)
            ->setFormCode('customer_account_create')
            ->setShowContainer(false)
            ->setBlockId('customer_form_user_attributes')
            ->setTemplate('Magento_CustomerCustomAttributes::form/userattributes.phtml');
    }

    private function getObject(string $modelClass)
    {
        try {
            return \Magento\Framework\App\ObjectManager::getInstance()
                ->create($modelClass);
        } catch (\Exception $e) {
            return false;
        }
    }
}

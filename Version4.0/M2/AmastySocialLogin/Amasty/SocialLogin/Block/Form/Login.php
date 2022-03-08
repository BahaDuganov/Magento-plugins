<?php

namespace Amasty\SocialLogin\Block\Form;

class Login extends \Magento\Customer\Block\Form\Login
{
    /**
     * @return Login|\Magento\Customer\Block\Form\Login|\Magento\Framework\View\Element\AbstractBlock
     */
    public function _prepareLayout()
    {
        $parent = $this->getParentBlock();
        return $parent ? $parent->_prepareLayout() : $this;
    }
}

<?php

namespace Amasty\SocialLogin\Model\ResourceModel\Sales;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\Amasty\SocialLogin\Model\Sales::class, \Amasty\SocialLogin\Model\ResourceModel\Sales::class);
    }
}

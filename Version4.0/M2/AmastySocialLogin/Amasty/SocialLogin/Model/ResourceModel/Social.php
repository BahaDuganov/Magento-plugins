<?php

namespace Amasty\SocialLogin\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Social extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('amasty_sociallogin_customers', 'id');
    }
}

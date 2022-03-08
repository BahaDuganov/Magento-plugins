<?php

namespace Amasty\SocialLogin\Setup\UpgradeSchema;

use Magento\Framework\DB\Ddl\Table;
use \Magento\Framework\Setup\SchemaSetupInterface;

class ChangeFieldType
{
    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     */
    public function execute(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('amasty_sociallogin_sales');

        $connection->modifyColumn(
            $tableName,
            'amount',
            [
                'type' => Table::TYPE_DECIMAL,
                'length' => '10,2',
                'default' => 0,
                'nullable' => false
            ]
        );
    }
}

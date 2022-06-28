<?php

namespace Trunkrs\Carrier\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '2.1.0', '<')) {
            // get table customer_entity
            $eavTable = $setup->getTable('quote');
            $eavTable2 = $setup->getTable('sales_order');
            $eavTable3 = $setup->getTable('sales_order_grid');

            $tables = [$eavTable, $eavTable2, $eavTable3];

            $columnNames = ['trunkrs_delivery_date', 'trunkrs_delivery_text'];

            $connection = $setup->getConnection();
            foreach ($tables as $table) {
                // Check if the table already exists
                if ($setup->getConnection()->isTableExists($table) == true) {
                    foreach ($columnNames as $columnName) {
                        if ($connection->tableColumnExists($table, $columnName) === false) {
                            $connection->addColumn($table, $columnName, array(
                                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                                'nullable' => true,
                                'comment' => 'Delivery Date',
                            ));
                        }
                    }
                }
            }
        }

        $setup->endSetup();
    }
}

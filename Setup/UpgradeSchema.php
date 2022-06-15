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

        if (version_compare($context->getVersion(), '2.0.8', '<')) {
            // get table customer_entity
            $eavTable = $setup->getTable('quote');
            $columnName = 'trunkrs_delivery_date';

            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($eavTable) == true) {
                $connection = $setup->getConnection();

                if ($connection->tableColumnExists($eavTable, $columnName) === false) {
                    $connection->addColumn('quote', $columnName, array(
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Delivery Date',
                    ));
                }
            }
        }

        $setup->endSetup();
    }
}

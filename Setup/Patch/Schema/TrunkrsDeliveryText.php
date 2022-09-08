<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Trunkrs\Carrier\Setup\Patch\Schema;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

/**
* Patch is mechanism, that allows to do atomic upgrade data changes
*/
class TrunkrsDeliveryText implements
    SchemaPatchInterface,
    PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavTable = $this->moduleDataSetup->getTable('quote');
        $eavTable2 = $this->moduleDataSetup->getTable('sales_order');
        $eavTable3 = $this->moduleDataSetup->getTable('sales_order_grid');

        $tables = [$eavTable, $eavTable2, $eavTable3];

        $columnNames = ['trunkrs_delivery_date', 'trunkrs_delivery_text'];

        $connection = $this->moduleDataSetup->getConnection();
        foreach ($tables as $table) {
            // Check if the table already exists
            if ($this->moduleDataSetup->getConnection()->isTableExists($table) == true) {
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

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        $this->moduleDataSetup->startSetup();

        $eavTable = $this->moduleDataSetup->getTable('quote');
        $eavTable2 = $this->moduleDataSetup->getTable('sales_order');
        $eavTable3 = $this->moduleDataSetup->getTable('sales_order_grid');

        $tables = [$eavTable, $eavTable2, $eavTable3];

        $columnNames = ['trunkrs_delivery_date', 'trunkrs_delivery_text'];

        foreach ($tables as $table) {
            // Check if the table already exists
            foreach ($columnNames as $columnName) {
                $this->moduleDataSetup->getConnection()->dropColumn(
                    $this->moduleDataSetup->getTable($table),
                    $columnName,
                    $schemaName = null
                );
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        return '2.1.0';
    }
}

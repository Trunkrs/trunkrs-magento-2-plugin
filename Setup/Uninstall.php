<?php

namespace Trunkrs\Carrier\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function uninstall(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        echo "Removing Trunkrs_Carrier configs...\n";
        $connection = $setup->getConnection();
        $connection->delete('core_config_data', "path LIKE '%trunkrsShipping%'");
        echo "Finished removing Trunkrs_Carrier configs.\n";

        $setup->endSetup();
    }
}
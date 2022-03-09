<?php

namespace Trunkrs\Carrier\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $setup->getConnection()->insert(
            $setup->getTable('core_config_data'),
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'carriers/trunkrsShipping/trunkrs_token',
                'value' => md5('trunkrs' . microtime(true) . mt_Rand())
            ]
        );

        $setup->getConnection()->insert(
            $setup->getTable('core_config_data'),
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'carriers/trunkrsShipping/is_configured',
                'value' => 0
            ]
        );

        $setup->getConnection()->insert(
            $setup->getTable('core_config_data'),
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'carriers/trunkrsShipping/active',
                'value' => 1
            ]
        );

        $setup->getConnection()->insert(
            $setup->getTable('core_config_data'),
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'carriers/trunkrsShipping/title',
                'value' => 'Trunkrs'
            ]
        );

        $setup->getConnection()->insert(
            $setup->getTable('core_config_data'),
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'carriers/trunkrsShipping/shipment_method_endpoint',
                'value' => 'https://staging.shipping.trunkrs.app/v1/shipping-rates' // for staging purpose
            ]
        );

        $setup->getConnection()->insert(
            $setup->getTable('core_config_data'),
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'carriers/trunkrsShipping/shipping_endpoint',
                'value' => 'https://staging.shipping.trunkrs.app/v1/shipments' // for staging purpose
            ]
        );

        $setup->endSetup();
    }
}

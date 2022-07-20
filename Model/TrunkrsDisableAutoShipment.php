<?php
/**
 * Copyright © 2019 Trunkrs. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Trunkrs\Carrier\Model;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Request\Http;
use Trunkrs\Carrier\Api\TrunkrsDisableAutoShipmentInterface;
use Trunkrs\Carrier\Helper\Data;

class TrunkrsDisableAutoShipment implements TrunkrsDisableAutoShipmentInterface
{
    /**
     * @param $helper
     */
    protected $helper;

    /**
     * \Magento\Config\Model\ResourceModel\Config
     * @var Config $resourceConfig
     */
    protected $resourceConfig;

    /**
     * \Magento\Framework\App\Request\Http
     * @var Http $request
     */
    protected $request;

    /**
     * TrunkrsPortalEndpoint constructor.
     * @param Data $helper
     * @param Http $request
     * @param Config $resourceConfig
     */
    public function __construct(
        Data    $helper,
        Http    $request,
        Config  $resourceConfig
    )
    {
        $this->helper = $helper;
        $this->resourceConfig = $resourceConfig;
        $this->request = $request;
    }

    /**
     * set Trunkrs Disable auto Shipment creation
     * @return void
     */
    public function setDisableAutoShipment(): void
    {
        $token = $this->helper->getToken();
        $magentoToken = $this->request->getHeader('magentoToken');

        if($token !== $magentoToken) {
            return;
        }

        $data = $this->request->getContent();
        $body = json_decode($data);

        $this->resourceConfig->saveConfig(
            'carriers/trunkrsShipping/disable_auto_shipment',
            $body->disableAutoShipment,
            'default',
            0
        );
    }
}

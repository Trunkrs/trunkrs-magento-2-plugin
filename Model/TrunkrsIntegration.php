<?php
/**
 * Copyright © 2019 Trunkrs. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Trunkrs\Carrier\Model;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Request\Http;
use Trunkrs\Carrier\Api\TrunkrsShippingInterface;
use Trunkrs\Carrier\Helper\Data;

class TrunkrsIntegration implements TrunkrsShippingInterface
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
     * Save Trunkrs integration details to plugin core data
     * @return void
     */
    public function saveDetails(): void
    {
        $token = $this->helper->getToken();
        $magentoToken = $this->request->getHeader('magentoToken');

        if($token !== $magentoToken) {
            return;
        }

        $data = $this->request->getContent();
        $body = json_decode($data);

        $this->resourceConfig->saveConfig(
            'carriers/trunkrsShipping/is_configured',
            $body->isConfigured,
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'carriers/trunkrsShipping/access_token',
            $body->accessToken,
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'carriers/trunkrsShipping/integration_details',
            json_encode($body->details),
            'default',
            0
        );
    }
}

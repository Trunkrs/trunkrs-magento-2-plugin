<?php

namespace Trunkrs\Carrier\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    public $connectMode;

    /**
     * Data constructor.
     * @param \Trunkrs\Carrier\Model\Carrier\Shipping $connectMode
     */
    public function __construct(
        \Trunkrs\Carrier\Model\Carrier\Shipping $connectMode
    ) {
        $this->connectMode = $connectMode;
    }

    /**
     * @return string
     */
    public function getIntegrationToken()
    {
        $token = $this->connectMode->getIntegrationToken();
        return $token;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function timeslotsApi()
    {
        $timeslot = $this->connectMode->TrunkrsShippingMethod();
        return $timeslot;
    }

    /**
     * get Shipping endpoint
     * @return string
     */
    public function getShipmentEndpoint()
    {
        $endpoint = $this->connectMode->getShipmentEndpoint();
        return $endpoint;
    }

    /**
     * get Cancel shipment endpoint
     * @return string
     */
    public function getCancelShipmentEndpoint()
    {
        $endpoint = $this->connectMode->getCancelShipmentEndpoint();
        return $endpoint;
    }
}

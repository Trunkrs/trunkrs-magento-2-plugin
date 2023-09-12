<?php

namespace Trunkrs\Carrier\Service\Shipment\Labelling;

use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\Data\ShipmentInterface;

class GetLabels
{

    /** @var ShipmentRepositoryInterface */
    private $shipmentRepository;

    /**
     * Get labels constructor
     * @param ShipmentRepositoryInterface $shipmentRepository
     */
    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository
    )
    {
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * @param $shipmentId
     * @return array|\Magento\Sales\Api\Data\ShipmentItemInterface[]
     */
    public function get($shipmentId)
    {
        $shipment = $this->shipmentRepository->get($shipmentId);

        if (!$shipment) {
            return [];
        }

        return $this->getLabels($shipment);
    }

    /**
     * @param ShipmentInterface $shipment
     * @return array|\Magento\Sales\Api\Data\ShipmentItemInterface[]
     */
    private function getLabels(ShipmentInterface $shipment)
    {
        $labels = $shipment->getItems();

        if ($labels) {
            return $labels;
        }

        return [];
    }
}

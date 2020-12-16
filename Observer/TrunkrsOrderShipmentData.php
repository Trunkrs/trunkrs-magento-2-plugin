<?php

namespace Trunkrs\Carrier\Observer;

use Magento\Framework\Event\ObserverInterface;
use Trunkrs\Carrier\Helper\Data;
use Trunkrs\Carrier\Model\Carrier\Shipping;

class TrunkrsOrderShipmentData implements ObserverInterface
{
    /**
     * @param Data $helper
    */
    public $helper;

    /**
     * @var ShipmentTrackInterfaceFactory
     */
    private $trackFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    protected $convertOrder;

    /**
     * TrunkrsOrderShipmentData constructor.
     * @param Data $helper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Convert\Order $convertOrder
     * @param \Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory $trackFactory
     */
    public function __construct(
        Data $helper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory $trackFactory
    ) {
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
        $this->convertOrder = $convertOrder;
        $this->trackFactory = $trackFactory;
    }

    /**
     * Fetch order details after order is placed
     * @param \Magento\Framework\Event\Observer $observer
     * @return string|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        /** @var \Magento\Sales\Model\Order $order $shippingName ...*/
        $shippingName = $order->getShippingMethod();
        $shippingTitle = $order->getShippingDescription();
        $shippingDetailsData = $order->getShippingAddress();
        $orderReference = $order->getIncrementId();

        // check whether an order can be ship or not
        if ($order->canShip()) {
            if ($shippingName === Shipping::TRUNKRS_SHIPPING_METHOD) {

                /**
                 * @return $receiverData
                 */
                $receiverStreet = $shippingDetailsData->getStreet();
                $receiverName = $order->getCustomerName();
                $receiverCity = $shippingDetailsData->getCity();
                $receiverCountry = $shippingDetailsData->getCountryId();
                $receiverTel = $shippingDetailsData->getTelephone();
                $receiverEmail = $shippingDetailsData->getEmail();
                $receiverPostCode = $shippingDetailsData->getPostcode();

                $orderShipment = $this->convertOrder->toShipment($order);

                foreach ($order->getAllItems() as $orderItem) {
                    // Check virtual item and item Quantity
                    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                        continue;
                    }

                    $qty = $orderItem->getQtyToShip();
                    $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qty);

                    $orderShipment->addItem($shipmentItem);
                }

                $orderShipment->register();
                $orderShipment->getOrder()->setIsInProcess(true);

                try {

                    // Save created Order Shipment
                    $orderShipment->save();
                    $orderShipment->getOrder()->save();

                    // post shipment to Shipping portal
                    $urlHost = $this->helper->getShipmentEndpoint();
                    $client = new \GuzzleHttp\Client();
                    $data = [
                        "orderReference" => $orderReference,
                        "receiverName" => $receiverName,
                        "receiverEmail" => $receiverEmail,
                        "receiverTel" => $receiverTel,
                        "receiverStreet" => $receiverStreet[0],
                        "receiverPostCode" => $receiverPostCode,
                        "receiverCity" => $receiverCity,
                        "receiverCountry" => $receiverCountry
                    ];

                    $response = $client->post($urlHost, ['json' => $data]);
                    $trackingInfo = \GuzzleHttp\json_decode($response->getBody());

                    $track = $this->trackFactory->create();
                    $track->setCarrierCode(Shipping::CARRIER_CODE);
                    $track->setTitle($shippingTitle);
                    $track->setTrackNumber($trackingInfo->trunkrsNr);

                    $orderShipment->addTrack($track)
                        ->setShippingAddressId($trackingInfo->shipmentId)
                        ->setShippingLabel(base64_decode($trackingInfo->label));
                    $orderShipment->save();
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __($e->getMessage())
                    );
                }
            }
        }
    }
}

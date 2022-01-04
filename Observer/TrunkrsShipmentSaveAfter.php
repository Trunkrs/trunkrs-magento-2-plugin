<?php

namespace Trunkrs\Carrier\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Trunkrs\Carrier\Model\Carrier\Shipping;
use Trunkrs\Carrier\Helper\Data;

class TrunkrsShipmentSaveAfter implements ObserverInterface
{
    /**
     * @param Data $helper
     */
    public $helper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ShipmentTrackInterfaceFactory
     */
    private $trackFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    protected $registry;

    /**
     * @param Data $helper
     * @param LoggerInterface $logger
     * @param ManagerInterface $messageManager
     * @param ShipmentTrackInterfaceFactory $trackFactory
     * @param Registry $registry
     */
    public function __construct(
        Data $helper,
        LoggerInterface $logger,
        ManagerInterface $messageManager,
        ShipmentTrackInterfaceFactory $trackFactory,
        Registry $registry
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->trackFactory = $trackFactory;
        $this->registry = $registry;
    }

    /**
     * @param Observer $observer
     * @return ManagerInterface|void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $observer->getEvent()->getShipment()->getOrder();

        /** @var Order $order $shippingName ... */
        $shippingName = $order->getShippingMethod();
        $shippingTitle = $order->getShippingDescription();
        $shippingDetailsData = $order->getShippingAddress();
        $orderReference = $order->getIncrementId();

        if (!$this->registry->registry('hasShipped')) {
            if ($shippingName === Shipping::TRUNKRS_SHIPPING_METHOD) {
                $this->registry->register('hasShipped', true);
                $receiverStreet = $shippingDetailsData->getStreet();
                $receiverName = $shippingDetailsData->getName();
                $receiverCity = $shippingDetailsData->getCity();
                $receiverCountry = $shippingDetailsData->getCountryId();
                $receiverTel = $shippingDetailsData->getTelephone();
                $receiverEmail = $shippingDetailsData->getEmail();
                $receiverPostCode = $shippingDetailsData->getPostcode();

                try {
                    // post shipment to Shipping portal
                    $urlHost = $this->helper->getShipmentEndpoint();
                    $client = new \GuzzleHttp\Client();
                    $data = [
                        "orderReference" => $orderReference,
                        "receiverName" => $receiverName,
                        "receiverEmail" => $receiverEmail,
                        "receiverTel" => $receiverTel,
                        "receiverStreet" => implode(' ',$receiverStreet),
                        "receiverPostCode" => $receiverPostCode,
                        "receiverCity" => $receiverCity,
                        "receiverCountry" => $receiverCountry
                    ];

                    $response = $client->post($urlHost, ['json' => $data]);
                    $trackingInfo = \GuzzleHttp\json_decode($response->getBody());

                    if (!$trackingInfo->trunkrsNr) {
                        return $this->messageManager->addErrorMessage("Error: Invalid Shipping data.");
                    }

                    $track = $this->trackFactory->create();
                    $track->setCarrierCode(Shipping::CARRIER_CODE);
                    $track->setTitle($shippingTitle);
                    $track->setTrackNumber($trackingInfo->trunkrsNr);

                    $shipment->addTrack($track)
                        ->setShippingAddressId($trackingInfo->shipmentId)
                        ->setShippingLabel(base64_decode($trackingInfo->label));

                    $shipment->save();
                } catch (\Exception $e) {
                    $this->logger->critical('Error: '.$e->getMessage());
                    throw new LocalizedException(
                        __($e->getMessage())
                    );
                }
            }
        }
    }
}

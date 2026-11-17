<?php

namespace Trunkrs\Carrier\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Shipment as ShipmentResource;
use Psr\Log\LoggerInterface;
use Trunkrs\Carrier\Model\Carrier\Shipping;
use Trunkrs\Carrier\Helper\Data;

class TrunkrsShipmentSaveAfter implements ObserverInterface
{
    public const TRUNKRS_SHIPPING_CODE = 'trunkrsShipping_trunkrsShipping';
    public const CARRIER_CODE = 'trunkrsShipping';
    /**
     * @var Data
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

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var OrderResource
     */
    private $orderResource;

    /**
     * @var ShipmentResource
     */
    private $shipmentResource;

    /**
     * @var File
     */
    private $ioFile;

    /**
     * @param Data $helper
     * @param LoggerInterface $logger
     * @param ManagerInterface $messageManager
     * @param ShipmentTrackInterfaceFactory $trackFactory
     * @param Registry $registry
     * @param OrderResource $orderResource
     * @param ShipmentResource $shipmentResource
     * @param File $ioFile
     */
    public function __construct(
        Data $helper,
        LoggerInterface $logger,
        ManagerInterface $messageManager,
        ShipmentTrackInterfaceFactory $trackFactory,
        Registry $registry,
        OrderResource $orderResource,
        ShipmentResource $shipmentResource,
        File $ioFile
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->trackFactory = $trackFactory;
        $this->registry = $registry;
        $this->orderResource = $orderResource;
        $this->shipmentResource = $shipmentResource;
        $this->ioFile = $ioFile;
    }

    /**
     * Send manually created shipments to Trunkrs.
     *
     * @param Observer $observer
     * @return ManagerInterface|void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $observer->getEvent()->getShipment()->getOrder();

        $disableAutoShipment = $this->helper->getDisableAutoShipmentCreation();

        /** @var Order $order $shippingName ... */
        $shippingName = $order->getShippingMethod();
        $shippingTitle = $order->getShippingDescription();
        $shippingData = $order->getShippingAddress();
        $orderReference = $order->getIncrementId();

        $intendedDeliveryDate = $order->getTrunkrsDeliveryDate();

        if (!$this->registry->registry('hasShipped')) {
            if ($shippingName === self::TRUNKRS_SHIPPING_CODE && $disableAutoShipment) {
                $this->registry->register('hasShipped', true);

                try {
                    // post shipment to Shipping portal
                    $urlHost = $this->helper->getCreateShipmentEndpoint();
                    $client = new \GuzzleHttp\Client();

                    $shipmentBody = [
                        'reference' =>  $orderReference,
                        'recipient' => [
                            'name' =>  $order->getCustomerName(),
                            'email' => $shippingData->getEmail(),
                            'phoneNumber' => $shippingData->getTelephone(),
                            'location' => [
                                'address' => implode(' ', $shippingData->getStreet()),
                                'postalCode' => $shippingData->getPostcode(),
                                'city' => $shippingData->getCity(),
                                'country' => $shippingData->getCountryId()
                            ]
                        ]
                    ];

                    if (!$disableAutoShipment && !empty($intendedDeliveryDate)) {
                        $shipmentBody['intendedDeliveryDate'] = $intendedDeliveryDate;
                    }

                    $response = $client->post($urlHost, [
                        'headers' => [
                            'Authorization' => sprintf('Bearer %s', $this->helper->getAccessToken()),
                            'Content-Type' => 'application/json; charset=utf-8'],
                        'json' => ['shipments' => [$shipmentBody]]
                    ]);

                    $trunkrsObj = json_decode($response->getBody());
                    $trunkrsNumber = $trunkrsObj->success[0]->trunkrsNumber;
                    $labelUrl = $trunkrsObj->success[0]->labelUrl;

                    $order->setState(Order::STATE_PROCESSING);
                    $order->setStatus('processing');
                    $this->orderResource->save($order);

                    $track = $this->trackFactory->create();
                    $track->setCarrierCode(self::CARRIER_CODE);
                    $track->setTitle($shippingTitle || Shipping::TRUNKRS);
                    $track->setTrackNumber($trunkrsNumber);

                    $shipment->addTrack($track)
                        ->setShippingAddressId($trunkrsNumber)
                        ->setShippingLabel($this->ioFile->read($labelUrl));

                    $this->shipmentResource->save($shipment);
                } catch (\Exception $e) {
                    $this->logger->critical($e->getMessage());
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __($e->getMessage())
                    );
                }
            }
        }
    }
}

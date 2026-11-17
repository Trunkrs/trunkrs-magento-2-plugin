<?php

namespace Trunkrs\Carrier\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Shipment as ShipmentResource;
use Trunkrs\Carrier\Helper\Data;
use Trunkrs\Carrier\Model\Carrier\Shipping;

class TrunkrsSaveShipmentData implements ObserverInterface
{
    public const TRUNKRS_SHIPPING_CODE = 'trunkrsShipping_trunkrsShipping';
    public const CARRIER_CODE = 'trunkrsShipping';
    /**
     * @var Data
     */
    public $helper;

    /**
     * @var OrderResource
     */
    protected $orderResource;

    /**
     * @var ShipmentResource
     */
    private $shipmentResource;

    /**
     * @var File
     */
    private $ioFile;

    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    protected $convertOrder;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory
     */
    private $trackFactory;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * TrunkrsSaveShipmentData constructor.
     * @param Data $helper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Sales\Model\Convert\Order $convertOrder
     * @param \Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory $trackFactory
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Psr\Log\LoggerInterface $logger
     * @param OrderResource $orderResource
     * @param ShipmentResource $shipmentResource
     * @param File $ioFile
     */
    public function __construct(
        Data $helper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory $trackFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Psr\Log\LoggerInterface $logger,
        OrderResource $orderResource,
        ShipmentResource $shipmentResource,
        File $ioFile
    ) {
        $this->helper = $helper;
        $this->messageManager = $messageManager;
        $this->convertOrder = $convertOrder;
        $this->trackFactory = $trackFactory;
        $this->cart = $cart;
        $this->logger = $logger;
        $this->orderResource = $orderResource;
        $this->shipmentResource = $shipmentResource;
        $this->ioFile = $ioFile;
    }

    /**
     * Fetch order details after order is placed
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Psr\Http\Message\ResponseInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $this->cart->getQuote();

        $disableAutoShipment = $this->helper->getDisableAutoShipmentCreation();

        /** @var \Magento\Sales\Model\Order $order $shippingName ... */
        $shippingName = $order->getShippingMethod();
        $shippingData = $order->getShippingAddress();
        $orderReference = $order->getIncrementId();

        $intendedDeliveryDate = $quote->getTrunkrsDeliveryDate();

        // check whether an order can be shipped or not
        if ($order->canShip()) {
            if ($shippingName === self::TRUNKRS_SHIPPING_CODE && !$disableAutoShipment) {
                $orderShipment = $this->convertOrder->toShipment($order);

                foreach ($order->getAllItems() as $orderItem) {
                    // Check virtual if item has qty and not virtual type
                    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                        continue;
                    }

                    $qty = $orderItem->getQtyToShip();
                    $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qty);

                    $orderShipment->addItem($shipmentItem);
                }

                $orderShipment->register();

                // Save created Order Shipment
                $this->shipmentResource->save($orderShipment);

                $orderShipment->getOrder()->setState(Order::STATE_PROCESSING);
                $orderShipment->getOrder()->setStatus('processing');
                $this->orderResource->save($orderShipment->getOrder());

                try {

                    // post shipment to Shipping portal
                    $urlHost = $this->helper->getCreateShipmentEndpoint();
                    $client = new \GuzzleHttp\Client();

                    $singleShipmentBody = [
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

                    if (!empty($intendedDeliveryDate)) {
                        $singleShipmentBody['intendedDeliveryDate'] = $intendedDeliveryDate;
                    }

                    $response = $client->post($urlHost, [
                        'headers' => [
                            'Authorization' => sprintf('Bearer %s', $this->helper->getAccessToken()),
                            'Content-Type' => 'application/json; charset=utf-8'],
                        'json' => ['shipments' => [$singleShipmentBody]]
                    ]);

                    $trunkrsObj = json_decode($response->getBody());
                    $trunkrsNumber = $trunkrsObj->success[0]->trunkrsNumber;
                    $labelUrl = $trunkrsObj->success[0]->labelUrl;

                    $track = $this->trackFactory->create();
                    $track->setCarrierCode(self::CARRIER_CODE);
                    $track->setTitle(Shipping::TRUNKRS);
                    $track->setTrackNumber($trunkrsNumber);

                    $orderShipment->addTrack($track)
                        ->setShippingAddressId($trunkrsNumber)
                        ->setShippingLabel($this->ioFile->read($labelUrl));

                    $this->shipmentResource->save($orderShipment);

                    return $response;
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

<?php

namespace Trunkrs\Carrier\Observer;

use Magento\Framework\Event\ObserverInterface;
use Trunkrs\Carrier\Helper\Data;
use Trunkrs\Carrier\Model\Carrier\Shipping;

class TrunkrsSaveShipmentData implements ObserverInterface
{
    const TRUNKRS_SHIPPING_CODE = 'trunkrsShipping_trunkrsShipping';
    const CARRIER_CODE = 'trunkrsShipping';
    /**
     * @param Data $helper
     */
    public $helper;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

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
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Convert\Order $convertOrder
     * @param \Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory $trackFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Data $helper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory $trackFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->helper = $helper;
        $this->messageManager = $messageManager;
        $this->convertOrder = $convertOrder;
        $this->trackFactory = $trackFactory;
        $this->cart = $cart;
        $this->logger = $logger;
    }

    /**
     * Fetch order details after order is placed
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Psr\Http\Message\ResponseInterface
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
                $orderShipment->save();
                $orderShipment->getOrder()->save();

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

                    if(!empty($intendedDeliveryDate)) {
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

                    $orderShipment->save();

                    $track = $this->trackFactory->create();
                    $track->setCarrierCode(self::CARRIER_CODE);
                    $track->setTitle(Shipping::TRUNKRS);
                    $track->setTrackNumber($trunkrsNumber);

                    $orderShipment->addTrack($track)
                        ->setShippingAddressId($trunkrsNumber)
                        ->setShippingLabel(file_get_contents($labelUrl));

                    $orderShipment->save();

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

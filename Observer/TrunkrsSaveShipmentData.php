<?php

namespace Trunkrs\Carrier\Observer;

use Magento\Framework\Event\ObserverInterface;
use Trunkrs\Carrier\Helper\Data;

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
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * TrunkrsSaveShipmentData constructor.
     * @param Data $helper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Data $helper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->helper = $helper;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    /**
     * Fetch order details after order is placed
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Psr\Http\Message\ResponseInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        /** @var \Magento\Sales\Model\Order $order $shippingName ... */
        $shippingName = $order->getShippingMethod();
        $shippingData = $order->getShippingAddress();
        $orderReference = $order->getIncrementId();

        // check whether an order can be shipped or not
        if ($order->canShip()) {
            if ($shippingName === self::TRUNKRS_SHIPPING_CODE) {
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
                        ],
                    ];

                    return $client->post($urlHost, [
                        'headers' => [
                            'Authorization' => sprintf('Bearer %s', $this->helper->getAccessToken()),
                            'Content-Type' => 'application/json; charset=utf-8'],
                        'json' => ['shipments' => [$singleShipmentBody]]
                    ]);
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

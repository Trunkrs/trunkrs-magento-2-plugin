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
     * TrunkrsOrderShipmentData constructor.
     * @param Data $helper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Convert\Order $convertOrder
     */
    public function __construct(
        Data $helper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Convert\Order $convertOrder
    ) {
        $this->helper = $helper;
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
        $this->convertOrder = $convertOrder;
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

        /** @var \Magento\Sales\Model\Order $order $shippingName ... */
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

                    $client->post($urlHost, ['json' => $data]);
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __($e->getMessage())
                    );
                }
            }
        }
    }
}

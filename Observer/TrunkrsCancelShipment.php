<?php

namespace Trunkrs\Carrier\Observer;

use Magento\Framework\Event\ObserverInterface;
use Trunkrs\Carrier\Helper\Data;
use Trunkrs\Carrier\Model\Carrier\Shipping;

class TrunkrsCancelShipment implements ObserverInterface
{
    /**
     * @param Data $helper
    */
    public $helper;

    /**
     * TrunkrsCancelShipment constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * Cancel shipment
     * @param \Magento\Framework\Event\Observer $observer
     * @return string|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $orderReference = $order->getIncrementId();
        $shippingName = $order->getShippingMethod();

        if($shippingName === Shipping::TRUNKRS_SHIPPING_METHOD)
        {
            //post shipment to Shipping portal
            try
            {
                $urlHost = $this->helper->getCancelShipmentEndpoint();
                $client = new \GuzzleHttp\Client();
                $data = array(
                    "orderReference" => $orderReference
                );

                $request = $client->post($urlHost, ['json' => $data]);
                
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }
    }
}
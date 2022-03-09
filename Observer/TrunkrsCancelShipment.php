<?php

namespace Trunkrs\Carrier\Observer;

use Magento\Framework\Event\ObserverInterface;
use Trunkrs\Carrier\Helper\Data;

class TrunkrsCancelShipment implements ObserverInterface
{
    const TRUNKRS_CARRIER_CODE = 'trunkrsShipping_trunkrsShipping';
    /**
     * @param Data $helper
     */
    public $helper;

    /**
     * TrunkrsCancelShipment constructor.
     * @param Data $helper
     */
    public function __construct(Data $helper)
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
        $tracksCollection = $order->getTracksCollection();
        $shippingName = $order->getShippingMethod();

        $trunkrsNumber = '';
        foreach ($tracksCollection->getItems() as $track) {
            $trunkrsNumber = $track->getTrackNumber();
            break;
        }

        if($shippingName === self::TRUNKRS_CARRIER_CODE)
        {
            try
            {
                $urlHost = $this->helper->getCancelShipmentEndpoint();
                $client = new \GuzzleHttp\Client();

                $client->delete($urlHost . '/' . $trunkrsNumber,[
                    'headers' => [
                        'Authorization' => sprintf('Bearer %s', $this->helper->getAccessToken()),
                        'Content-Type' => 'application/json; charset=utf-8'],
                ]);

            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }
    }
}

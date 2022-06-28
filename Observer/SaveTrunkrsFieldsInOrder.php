<?php

namespace Trunkrs\Carrier\Observer;

class SaveTrunkrsFieldsInOrder implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();

        $order->setData("trunkrs_delivery_date", $quote->getTrunkrsDeliveryDate());
        $order->setData("trunkrs_delivery_text", $quote->getTrunkrsDeliveryText());

        return $this;
    }
}

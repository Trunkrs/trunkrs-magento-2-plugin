<?php

namespace Trunkrs\Carrier\Plugin\Quote\Address;

class Rate
{
    /**
     * @param $subject
     * @param $result
     * @param $rate
     * @return mixed
     */
    public function afterImportShippingRate($subject, $result, $rate)
    {
        if ($rate instanceof \Magento\Quote\Model\Quote\Address\RateResult\Method) {
            $result->setDeliveryOptions(
                $rate->getDeliveryOptions()
            );
        }

        return $result;
    }
}

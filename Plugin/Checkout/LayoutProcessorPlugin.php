<?php

namespace Trunkrs\Carrier\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Trunkrs\Carrier\Helper\Data;

class LayoutProcessorPlugin
{
    protected $trunkrsObj;

    public function __construct(Data $trunkrsObj)
    {
        $this->trunkrsObj = $trunkrsObj;
    }

    /**
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        LayoutProcessor $subject,
        array           $jsLayout
    )
    {

        $validation['required-entry'] = false;

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['trunkrs-shipping-method-fields']['children']['trunkrs_delivery_time'] = [
            'component' => "Magento_Ui/js/form/element/abstract",
            'config' => [
                'customScope' => 'trunkrsShippingMethodFields',
                'template' => 'ui/form/field',
                'elementTmpl' => "ui/form/element/hidden",
                'id' => "trunkrs_delivery_time"
            ],
            'dataScope' => 'trunkrsShippingMethodFields.trunkrs_shipping_field[trunkrs_delivery_time]',
            'label' => $this->getValue(),
            'value' => $this->getValue(),
            'provider' => 'checkoutProvider',
            'visible' => true,
            'validation' => $validation,
            'sortOrder' => 2,
            'id' => 'trunkrs_shipping_field[trunkrs_delivery_time]'
        ];

        return $jsLayout;
    }

    /**
     * @return string
     */
    protected function getValue(): string
    {
        try {
            return $this->trunkrsObj->getDeliveryText();
        } catch (\Exception $e) {
            return "Error delivery time slot...".$e->getMessage();
        }
    }
}

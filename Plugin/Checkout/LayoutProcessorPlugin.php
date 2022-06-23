<?php

namespace Trunkrs\Carrier\Plugin\Checkout;

use GuzzleHttp\Client;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Trunkrs\Carrier\Helper\Data;
use Trunkrs\Carrier\Model\Carrier\Shipping;

class LayoutProcessorPlugin
{
    protected $trunkrsObj;

    protected $timezone;

    public function __construct(
        Shipping $trunkrsObj,
        TimezoneInterface $timezone
    )
    {
        $this->trunkrsObj = $trunkrsObj;
        $this->timezone = $timezone;
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
        $country = $this->trunkrsObj->getCountry();
        $postalCode = $this->trunkrsObj->getPostalCode();
        $totalAmount = $this->trunkrsObj->getTotalOrderAmount();

        $urlHost = $this->trunkrsObj->getShipmentMethodEndpoint();
        $client = new Client();

        $request = $client->get(
            $urlHost . "?country=" . $country . "&postalCode=" . $postalCode. "&orderValue=" . $totalAmount, [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $this->trunkrsObj->getAccessToken()),
                'Content-Type' => 'application/json; charset=utf-8'
            ]
        ]);

        $response = json_decode($request->getBody()->getContents());

        $options = [];
        if (!empty($response)) {
            foreach($response as $delivery) {
                $options[] = [
                    'value' => Data::parse8601($delivery->deliveryDate)->format('Y-m-d'),
                    'label' => $this->timezone->formatDate(Data::parse8601($delivery->announceBefore), \IntlDateFormatter::FULL, false),
                ];
            }
        }

        $validation['required-entry'] = true;

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['trunkrs-shipping-method-fields']['children']['trunkrs_delivery_date'] = [
            'component' => 'Magento_Ui/js/form/element/select',
            'config' => [
                'customScope' => 'trunkrsShippingMethodFields',
                'template' => 'ui/form/field',
                'elementTmpl' => "ui/form/element/select",
                'id' => "trunkrs_delivery_date"
            ],
            'dataScope' => 'trunkrsShippingMethodFields.trunkrs_shipping_field[trunkrs_delivery_date]',
            'label' => __('Select delivery date:'),
            'provider' => 'checkoutProvider',
            'visible' => true,
            'validation' => $validation,
            'sortOrder' => 2,
            'id' => 'trunkrs_shipping_field[trunkrs_delivery_date]',
            'options' => $options
        ];

        return $jsLayout;
    }
}

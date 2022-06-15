<?php

namespace Trunkrs\Carrier\Model\Carrier;

use DateTime;
use GuzzleHttp\Client;
use IntlDateFormatter;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Trunkrs\Carrier\Helper\Data;

class Shipping extends AbstractCarrier implements CarrierInterface
{
    const TRUNKRS = 'Trunkrs';
    const TNT_BASE_URL = 'https://parcel.trunkrs.nl/';

    /**
     * @var string Shipping Title
     */
    protected $title;

    /**
     * @var float The shipping rate
     */
    protected $price = 0.00;

    /**
     * @var string $deliveryText The shipping method description
     */
    protected $deliveryText;

    /**
     * @var string
     */
    protected $_code = 'trunkrsShipping';

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $rateMethodFactory;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var \Magento\Shipping\Model\Tracking\ResultFactory
     */
    protected $trackFactory;

    /**
     * @var \Magento\Shipping\Model\Tracking\Result\StatusFactory
     */
    protected $statusFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateRequest
     */
    protected $rateRequest;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * Shipping constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $statusFactory
     * @param \Magento\Checkout\Model\Cart $cart
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface          $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory  $rateErrorFactory,
        \Psr\Log\LoggerInterface                                    $logger,
        \Magento\Shipping\Model\Rate\ResultFactory                  $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Store\Model\StoreManagerInterface                  $storeManagerInterface,
        \Magento\Shipping\Model\Tracking\ResultFactory              $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory       $statusFactory,
        \Magento\Checkout\Model\Cart                                $cart,
        array                                                       $data = []
    )
    {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->trackFactory = $trackFactory;
        $this->statusFactory = $statusFactory;
        $this->cart = $cart;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * get shipping method details
     * @return bool
     */
    public function getTrunkrsShippingMethod()
    {
        try {
            $country = $this->getCountry();
            $postalCode = $this->getPostalCode();
            $totalAmount = $this->getTotalOrderAmount();

            $urlHost = $this->getShipmentMethodEndpoint();
            $client = new Client();

            $request = $client->get(
                $urlHost . "?country=" . $country . "&postalCode=" . $postalCode. "&orderValue=" . $totalAmount, [
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $this->getAccessToken()),
                    'Content-Type' => 'application/json; charset=utf-8'
                ]
            ]);

            $response = json_decode($request->getBody()->getContents());

            $this->title = 'Trunkrs';
            $this->price = $response[0]->price;
            $this->deliveryText = $this->getDescription($response[0]->deliveryDate, $response[0]->announceBefore);

            return !empty($this->deliveryDate) && !empty($this->deliveryText);
        } catch (\Throwable $e) {
            $this->title = '';
            return false;
        }
    }

    /**
     * @return string
     */
    public function getShippingDescription()
    {
        return $this->deliveryText;
    }

    /**
     * @param $deliveryDate
     * @param $announceBefore
     * @return string
     */
    public function getDescription($deliveryDate, $announceBefore)
    {
        $deliveryTimestamp = Data::parse8601($deliveryDate)->getTimestamp();
        $deliveryDate = date('Y-m-d', $deliveryTimestamp);
        $parsedDeliveryDate = Data::parse8601Date($deliveryDate);
        $cutOffTime =  Data::parse8601($announceBefore);

        $type = Data::getRateType($deliveryDate);
        $fmt = new IntlDateFormatter(
            'nl_NL',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'Europe/Amsterdam',
            IntlDateFormatter::GREGORIAN,
            'cccc'
        );

        $description = '';
        switch ($type) {
            case 'same';
                $description = sprintf("Plaats je bestelling voor %s om het vandaag tussen 17u00 en 22u00 te ontvangen!",
                    date('H:i', $cutOffTime->getTimestamp() + $cutOffTime->getOffset()));
                break;

            case 'next':
                $today = new DateTime("today");
                $diff = $today->diff($parsedDeliveryDate);
                $diffDays = (integer)$diff->format("%R%a");

                $deliveryDesc = $diffDays === 1
                    ? 'morgen'
                    : 'op' . ' ' . $fmt->format($parsedDeliveryDate->getTimestamp() + $parsedDeliveryDate->getOffset());

                $hourMinutes = $diffDays === 1 ?  'morgen ' . date('H:i', $cutOffTime->getTimestamp() + $cutOffTime->getOffset()) :
                    $fmt->format($cutOffTime->getTimestamp() + $cutOffTime->getOffset()) . ' ' .
                    date('H:i', $cutOffTime->getTimestamp() + $cutOffTime->getOffset());

                $description = sprintf("Plaats je bestelling voor %s om het %s tussen 17u00 en 22u00 te ontvangen!",
                    $hourMinutes,
                    $deliveryDesc
                );
                break;
        }

        return $description;
    }

    /**
     * @param $trackingId
     * @return \Magento\Shipping\Model\Tracking\Result\Status
     */
    public function getTrackingInfo($trackingId)
    {
        $result = $this->trackFactory->create();
        $tracking = $this->statusFactory->create();

        $tracking->setCarrier($this->_code);
        $tracking->setCarrierTitle(self::TRUNKRS);
        $tracking->setTracking($trackingId);
        $tracking->setUrl(self::TNT_BASE_URL);

        $result->append($tracking);
        return $tracking;
    }

    /**
     * Check if carrier has shipping tracking option available
     *
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * Retrieves customer country
     * @return string
     */
    public function getCountry()
    {
        return $this->cart->getQuote()->getShippingAddress()->getCountry();
    }

    /**
     * Retrieves customer postcode
     * @return string
     */
    public function getPostalCode()
    {
        return $this->cart->getQuote()->getShippingAddress()->getPostcode();
    }

    /**
     * Get Grand Total for variable pricing
     * @return float
     */
    public function getTotalOrderAmount()
    {
        return $this->cart->getQuote()->getGrandTotal();
    }

    /**
     * Retrieves shipping method endpoint
     * @return string
     */
    public function getShipmentMethodEndpoint()
    {
        return $this->getConfigData('shipment_method_endpoint');
    }

    /**
     * Retrieves the access token
     * @return string The access token
     */
    public function getAccessToken()
    {
        return $this->getConfigData('access_token');
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->title];
    }

    /**
     * @param RateRequest $request
     * @return Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        $this->getTrunkrsShippingMethod();

        $this->rateRequest = $request;

        if (!$this->getIsConfigured()) {
            return false;
        }

        /* do not show trunkrs shipping if selected shipping country is not NL|BE */
        if ($this->getCountry() !== "NL" && $this->getCountry() !== "BE") {
            return false;
        }

        if (empty($this->title)) {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle('');

        $method->setMethod($this->_code);
        $method->setMethodTitle($this->title);
        $amount = $this->price;

        $method->setPrice($amount);
        $method->setCost($amount);

        $result->append($method);

        return $result;
    }

    /**
     * Reflects whether the plugin has been configured.
     * @return bool Value reflecting config status
     */
    public function getIsConfigured()
    {
        return !($this->getConfigData('is_configured') !== '1');
    }
}

<?php

namespace Trunkrs\Carrier\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;

class Shipping extends AbstractCarrier implements CarrierInterface
{
    const TRUNKRS_SHIPPING_METHOD = 'trunkrsShipping_trunkrsShipping';
    const CARRIER_CODE = 'trunkrsShipping';

    /**
     * @var string
     */
    protected $_code = 'trunkrsShipping';

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Shipping\Model\Tracking\ResultFactory
     */
    protected $_trackFactory;

    /**
     * @var \Magento\Shipping\Model\Tracking\Result\StatusFactory
     */
    protected $_statusFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * Shipping constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $statusFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $statusFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_trackFactory = $trackFactory;
        $this->_statusFactory = $statusFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->cart = $cart;
        $this->stockRepository = $stockRepository;
        $this->storeManagerInterface = $storeManagerInterface;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @return array|bool
     */
    public function trunkrsShippingMethod()
    {
        try {
            $urlHost = $this->getShipmentMethodEndpoint();
            $client = new \GuzzleHttp\Client();
            $data = [
                "trunkrs_token" => $this->getIntegrationToken(),
                "price_total" => $this->getTotalOrderAmount(),
                "postal_code" => $this->getPostalCode(),
                "country" => $this->getCountry()
            ];

            $request = $client->post($urlHost, ['json' => $data]);
            $response = json_decode($request->getBody()->getContents());

            return [
                'title' => $response->shipment_methods[0]->title,
                'name' => $response->shipment_methods[0]->name,
                'status' => $response->shipment_methods[0]->isActive,
                'price' => $response->shipment_methods[0]->price,
                'stockCheck' => $response->shipment_methods[0]->stockCheck,
                'deliveryText' => $response->shipment_methods[0]->deliveryText,
                'displayTo' => $response->shipment_methods[0]->displayTo
            ];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $trackingId
     * @return \Magento\Shipping\Model\Tracking\Result\Status
     */
    public function getTrackingInfo($trackingId)
    {
        $shipment = $this->trunkrsShippingMethod();

        $result = $this->_trackFactory->create();
        $tracking = $this->_statusFactory->create();

        $tracking->setCarrier($this->_code);
        $tracking->setCarrierTitle($shipment['title']);
        $tracking->setTracking($trackingId);
        $tracking->setUrl('https://parcel.trunkrs.nl/');

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
     * get Grand Total for variable pricing
     * @return float
     */
    public function getTotalOrderAmount()
    {
        return $this->cart->getQuote()->getGrandTotal();
    }

    /**
     * @return mixed|string|null
     */
    public function getPostalCode()
    {
        return $this->cart->getQuote()->getShippingAddress()->getPostcode();
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->cart->getQuote()->getShippingAddress()->getCountry();
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        $shipment = $this->trunkrsShippingMethod();
        return [$this->_code => $shipment['title']];
    }

    /**
     * getIntegrationToken
     * @return string
     */
    public function getIntegrationToken()
    {
        $token = $this->getConfigData('trunkrs_token');
        return $token;
    }

    /**
     * get Shipping endpoint
     * @return string
     */
    public function getShipmentEndpoint()
    {
        $endpoint = $this->getConfigData('portal_shipment');
        return $endpoint;
    }

    /**
     * get Cancel shipment endpoint
     * @return string
     */
    public function getCancelShipmentEndpoint()
    {
        $endpoint = $this->getConfigData('portal_cancel_shipment');
        return $endpoint;
    }

    /**
     * get Shipment method endpoint
     * @return string
     */
    public function getShipmentMethodEndpoint()
    {
        $endpoint = $this->getConfigData('portal_shipment_method');
        return $endpoint;
    }

    /**
     * @return array
     */
    public function stockCheck()
    {
        $stock = $this->cart->getQuote()->getAllVisibleItems();
        $items = [];
        foreach ($stock as $item) {
            $items[] = $item->getProductId();
        }
        return $items;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isInStock()
    {
        try {
            $ids = $this->stockCheck();
            $items = [];
            foreach ($ids as $id) {
                $items[] = $this->stockRepository->getStockItem($id)->getIsInStock();
            }
            return $items;
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function inStock()
    {
        $stocks = $this->isInStock();
        foreach ($stocks as $itemInStock) {
            if ($itemInStock === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param RateRequest $request
     * @return bool|\Magento\Framework\DataObject|Result|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function collectRates(RateRequest $request)
    {
        $currentStore = $this->storeManagerInterface->getStore();
        $shipment = $this->trunkrsShippingMethod();

        if (!isset($shipment['title'])) {
            return false;
        }

        $storeCode = $currentStore->getCode();
        if (!in_array($storeCode, $shipment['displayTo'])) {
            return false;
        }

        $shipAdd = $this->cart->getQuote();
        $selectedAdd = $shipAdd->getShippingAddress()->getCountry();

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        if ($shipment['status'] !== 1) {
            return false;
        }

        /* do not show trunkrs shipping if selected shipping country is not Netherlands */
        if ($selectedAdd != "NL") {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->_rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($shipment['name']);

        $method->setMethod($this->_code);

        $method->setMethodTitle($shipment['title']);
        $amount = $shipment['price'];

        $method->setPrice($amount);
        $method->setCost($amount);

        $result->append($method);

        /**
         * check whether the advanced option to hide shipping method when there
         * is product in the cart with an out of stock status — is set to Yes(1)
         */
        if ($shipment['stockCheck'] === 1) {
            /*check if any of the cart items has out of stock status(false)*/
            if ($this->inStock() === false) {
                return false;
            }
        }

        return $result;
    }
}

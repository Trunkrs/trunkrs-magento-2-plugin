<?php

namespace Trunkrs\Carrier\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;

class Shipping extends AbstractCarrier implements CarrierInterface
{
    const TRUNKRS_SHIPPING_METHOD = 'trunkrsShipping_trunkrsShipping';
    /**
     * @var string
     */
    protected $_code = 'trunkrsShipping';

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

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
     * Shipping constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockItemRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRepository,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->cart = $cart;
        $this->stockRepository = $stockRepository;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function trunkrsShippingMethod()
    {
        try
        {
            $urlHost = $this->getShipmentMethodEndpoint();
            $client = new \GuzzleHttp\Client();
            $data = array(
                "trunkrs_token" => $this->getIntegrationToken(),
                "price_total" => $this->getTotalOrderAmount()
            );

            $request = $client->post($urlHost, ['json' => $data]);
            $response = json_decode($request->getBody()->getContents());

            return [
                'title' => $response->shipment_methods[0]->title,
                'name' => $response->shipment_methods[0]->name,
                'status' => $response->shipment_methods[0]->isActive,
                'price' => $response->shipment_methods[0]->price,
                'stockCheck' => $response->shipment_methods[0]->stockCheck,
                'deliveryText' => $response->shipment_methods[0]->deliveryText
            ];

        } catch (\Exception $e) {
            return false;
        }
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
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
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
        foreach ($stock as $item){
            $items[] = $item->getProductId();
        }
        return $items;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isInStock(){
        try
        {
            $ids = $this->stockCheck();
            $items = [];
            foreach ($ids as $id){
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
            if($itemInStock===false){
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
        $shipment = $this->trunkrsShippingMethod();

        if(!isset($shipment['title']))
        {
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

        /*do not show trunkrs shipping if selected shipping country is not Netherlands */
        if($selectedAdd != "NL"){
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
        if($shipment['stockCheck'] === 1){
            /*check if any of the cart items has out of stock status(false)*/
            if($this->inStock()===false){
                return false;
            }
        }

        return $result;
    }
}
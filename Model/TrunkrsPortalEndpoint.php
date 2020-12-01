<?php
/**
 * Copyright © Trunkrs. All rights reserved.
 */
namespace Trunkrs\Carrier\Model;

use \Trunkrs\Carrier\Api\TrunkrsShippingInterface;
use Trunkrs\Carrier\Helper\Data;

class TrunkrsPortalEndpoint implements TrunkrsShippingInterface
{
  /**
  * @param $helper
  */
  protected $helper;

  /**
   * \Magento\Config\Model\ResourceModel\Config
   * @var $resourceConfig
  */
  protected $resourceConfig;

  /**
   * \Magento\Framework\App\Request\Http
   * @var $request
  */
  protected $request;

  /**
   * \Magento\Framework\Webapi\Rest\Response
   * @var $response
  */
  protected $response;

  /**
    * @var \Magento\Store\Model\StoreManagerInterface
  */
  protected $storeManagerInterface;

  /**
   * TrunkrsPortalEndpoint constructor.
   * @param Data $helper
   * @param \Magento\Framework\App\Request\Http $request
   * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
   * @param \Magento\Framework\Webapi\Rest\Response $response
   * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
  */
  public function __construct(
    Data $helper,
    \Magento\Framework\App\Request\Http $request,
    \Magento\Config\Model\ResourceModel\Config $resourceConfig,
    \Magento\Framework\Webapi\Rest\Response $response,
    \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
  )
  {
    $this->helper = $helper;
    $this->resourceConfig = $resourceConfig;
    $this->request = $request;
    $this->response = $response;
    $this->storeManagerInterface = $storeManagerInterface;
  }

  /**
   * Save endpoints to plugin core data
   * @return array
   */
  public function saveEndpoint()
  {
    // fetch active store views
    $stores = $this->storeManagerInterface->getStores();
    foreach($stores as $store)
    {
      $storeViews[] = [
          'code' => $store->getCode(),
          'name' => $store->getName()
      ];
    }

    $token = $this->helper->getIntegrationToken();
    $magentoToken = $this->request->getHeader('magentoToken');

    if($token !== $magentoToken)
    {
      $this->response->setHeader('Content-type', 'application/json');
      $this->response->setStatusCode(\Magento\Framework\Webapi\Exception::HTTP_UNAUTHORIZED);
      $this->response->setContent(\Zend_Json::encode(['Error' => __('Authorization Required')]));
      $this->response->sendResponse();
    }else
    {
      $data = $this->request->getContent();
      $postValue = json_decode($data);

      if(isset($postValue))
      {
        $portalShipmentEndpoint = isset($postValue->shipmentEndpoint) ? $postValue->shipmentEndpoint : '';
        $portalCancelShipmentEndpoint = isset($postValue->cancelShipmentEndpoint) ? $postValue->cancelShipmentEndpoint : '';
        $portalShipmentMethodEndpoint = isset($postValue->shipmentMethodEndpoint) ? $postValue->shipmentMethodEndpoint : '';
        $activeStatus = isset($postValue->activeStatus) ? $postValue->activeStatus : '';
      }

      if(empty($portalShipmentEndpoint) || empty($portalCancelShipmentEndpoint) ||
        empty($portalShipmentMethodEndpoint) || is_null($activeStatus))
      {
        $this->response->setHeader('Content-type', 'application/json');
        $this->response->setStatusCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);
        $this->response->setContent(\Zend_Json::encode(['Error' => __('One of the required parameter is missing.')]));
        $this->response->sendResponse();

      }else
      {
        //save trunkrs shipping portal endpoint to config
        $this->resourceConfig->saveConfig(
          'carriers/trunkrsShipping/portal_shipment',
          $portalShipmentEndpoint,
          'default',
          0
        );

        $this->resourceConfig->saveConfig(
          'carriers/trunkrsShipping/portal_cancel_shipment',
          $portalCancelShipmentEndpoint,
          'default',
          0
        );

        $this->resourceConfig->saveConfig(
          'carriers/trunkrsShipping/portal_shipment_method',
          $portalShipmentMethodEndpoint,
          'default',
          0
        );

        $this->resourceConfig->saveConfig(
          'carriers/trunkrsShipping/active',
          $activeStatus,
          'default',
          0
        );

        $this->resourceConfig->saveConfig(
          'carriers/trunkrsShipping/title',
          'Trunkrs',
          'default',
          0
        );

        return $storeViews;
      }
    }
  }
}
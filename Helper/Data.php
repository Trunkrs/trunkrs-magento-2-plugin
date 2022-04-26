<?php

namespace Trunkrs\Carrier\Helper;

use DateTime;
use DateTimeZone;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const CARRIER_CODE = 'trunkrsShipping';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @param string $fields
     * @return bool|string
     */
    private function getConfigData(string $fields)
    {
        if (empty($fields)) {
            return false;
        }
        return $this->scopeConfig->getValue(
            'carriers/' . $this::CARRIER_CODE . '/' .$fields,
            ScopeInterface::SCOPE_STORE,
        );
    }

    /**
     * Reflects whether the plugin has been configured.
     * @return bool Value reflecting config status
     */
    public function getIsConfigured(): bool
    {
        return !($this->getConfigData('is_configured') !== '1');
    }

    /**
     * Retrieves the plugin token
     * used for the plugin endpoint
     * @return string The plugin token
     */
    public function getToken()
    {
        return $this->getConfigData('trunkrs_token');
    }

    /**
     * Retrieves the access token
     * @return string|null The access token
     */
    public function getAccessToken()
    {
        return $this->getConfigData('access_token') ?? null;
    }

    /**
     * Retrieves shipment creation endpoint
     * @return string
     */
    public function getCreateShipmentEndpoint(): string
    {
        return $this->getConfigData('shipping_endpoint');
    }

    /**
     * Retrieves shipment cancellation endpoint
     * @return string
     */
    public function getCancelShipmentEndpoint(): string
    {
        return $this->getConfigData('shipping_endpoint');
    }

    /**
     * Retrieves the integration details
     * @return string|null The integration details
     */
    public function getIntegrationDetails()
    {
        return $this->getConfigData('integration_details') ?? null;
    }

    public static function getRateType(string $deliveryDate): string
    {
        $todayString = date('Y-m-d');
        return $todayString === $deliveryDate ? 'same' : 'next';
    }

    /**
     * Parses the ISO 8601 date string into DateTime object.
     * @param $dateString string The ISO-8601 date string.
     * @return DateTime The parsed ISO-8601 date time value.
     */
    public static function parse8601(string $dateString): DateTime
    {
        $result = DateTime::createFromFormat(
            'Y-m-d\TH:i:s.v\Z',
            $dateString
        );

        $result->setTimezone(new DateTimeZone('Europe/Amsterdam'));

        return $result;
    }

    /**
     * Parses the ISO 8601 date only string into DateTime object.
     * @param $dateString string The ISO-8601 date string.
     * @return DateTime The parsed ISO-8601 date time value.
     */
    public static function parse8601Date(string $dateString): DateTime
    {
        $result = DateTime::createFromFormat(
            'Y-m-d',
            $dateString
        );

        $result->setTimezone(new DateTimeZone('Europe/Amsterdam'));

        return $result;
    }
}

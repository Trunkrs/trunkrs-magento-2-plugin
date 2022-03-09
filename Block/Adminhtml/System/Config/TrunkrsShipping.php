<?php

namespace Trunkrs\Carrier\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Trunkrs\Carrier\Helper\Data;

class TrunkrsShipping extends Field
{
    const TRUNKRS_MAGE_SETTINGS = 'tr-mage-settings';
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Trunkrs_Carrier::system/config/trunkrs.phtml';

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Context $context
     * @param Data $helper
     */
    public function __construct(Context $context, Data $helper)
    {
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Retrieves Magento platform details
     * @return array
     */
    public function getMagentoVersion(): array
    {
        $objectManager = ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');

        return [
            'name' => $productMetadata->getName(),
            'edition' => $productMetadata->getEdition(),
            'version' => $productMetadata->getVersion()
        ];
    }

    /**
     * @return bool
     */
    public function getIsConfigured(): bool
    {
        return $this->helper->getIsConfigured();
    }

    /**
     * @return string
     */
    public function getMagentoToken()
    {
        return $this->helper->getToken();
    }

    /**
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->helper->getAccessToken();
    }

    /**
     * @return string|null
     */
    public function getIntegrationDetails()
    {
        return $this->helper->getIntegrationDetails();
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        return $this->_decorateRowHtml($element, $this->toHtml());
    }
}

<?php

namespace Trunkrs\Carrier\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;

class TrunkrsShipping extends Field
{

    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Trunkrs_Carrier::system/config/trunkrs.phtml';

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_decorateRowHtml($element, "<td class='label'>".__("Trunkrs API Token").":</td>
                            <td class='value'>" . $this->toHtml() . '</td>');
    }
}
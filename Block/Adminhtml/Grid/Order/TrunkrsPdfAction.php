<?php

namespace Trunkrs\Carrier\Block\Adminhtml\Grid\Order;

use Magento\Backend\Block\Template;
use Magento\Framework\View\Element\BlockInterface;

class TrunkrsPdfAction extends Template implements BlockInterface
{
    /**
     * @var string
     */
    protected $_template = 'Trunkrs_Carrier::order/grid/trunkrsPdfAction.phtml';

    /**
     * @return string
     */
    public function getTrunkrsDownloadActionUrl()
    {
        return $this->getUrl('trunkrs/order/CreateShipmentAndPrintPackingSlips');
    }
}

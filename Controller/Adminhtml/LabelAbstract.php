<?php

namespace Trunkrs\Carrier\Controller\Adminhtml;

use Magento\Framework\Exception\NotFoundException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use setasign\Fpdi\PdfParser\PdfParserException;
use Trunkrs\Carrier\Service\Shipment\Labelling\GetLabels;
use Trunkrs\Carrier\Service\Shipment\Packingslip\GetPackingslip;
use Trunkrs\Carrier\Controller\Adminhtml\PdfDownload as GetPdf;

abstract class LabelAbstract extends Action
{
    const TRUNKRS_SHIPPING_CODE = 'trunkrsShipping_trunkrsShipping';
    const TRUNKRS_LABEL_IN_PACKINGSLIPS = 'trunkrs_packingslips';
    const CARRIER_CODE = 'trunkrsShipping';
    /**
     * @var GetLabels
     */
    protected $getLabels;

    /**
     * @var GetPdf
     */
    protected $getPdf;

    /**
     * @var array
     */
    protected $labels = [];

    /**
     * @var GetPackingslip
     */
    private $getPackingSlip;

    /**
     * @param Context        $context
     * @param GetLabels      $getLabels
     * @param GetPdf         $getPdf
     * @param GetPackingslip $getPackingSlip
     */
    public function __construct(
        Context $context,
        GetLabels $getLabels,
        GetPdf $getPdf,
        GetPackingslip $getPackingSlip
    ) {
        parent::__construct($context);

        $this->getLabels      = $getLabels;
        $this->getPdf         = $getPdf;
        $this->getPackingSlip = $getPackingSlip;
    }

    /**
     * @param $shipmentId
     * @return void
     * @throws NotFoundException|PdfParserException|\Zend_Pdf_Exception
     */
    protected function setPackingslip($shipmentId)
    {
        $packingslip = $this->getPackingSlip->get($shipmentId);
        if (is_array($packingslip)) {
            $this->messageManager->addSuccessMessage(
                __('Something went wrong.')
            );
            return;
        }

        if (strlen($packingslip) === 0) {
            return;
        }

        $this->labels[] = $packingslip;
    }
}

<?php

namespace Trunkrs\Carrier\Service\Shipment\Packingslip;

use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use setasign\Fpdi\PdfParser\PdfParserException;
use Trunkrs\Carrier\Service\Shipment\Packingslip\Factory as PdfFactory;
use Trunkrs\Carrier\Service\Shipment\Packingslip\Items\Barcode;

class GetPackingslip
{
    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var PdfFactory
     */
    private $pdfShipment;

    /**
     * @var Barcode
     */
    private $barcodeMerger;

    /**
     * GetPackingslip constructor.
     * @param ShipmentRepositoryInterface $shipmentLabelRepository
     * @param Factory $pdfShipment
     * @param Barcode $barcode
     */
    public function __construct(
        ShipmentRepositoryInterface $shipmentLabelRepository,
        PdfFactory                  $pdfShipment,
        Barcode                     $barcode
    )
    {
        $this->shipmentRepository = $shipmentLabelRepository;
        $this->pdfShipment = $pdfShipment;
        $this->barcodeMerger = $barcode;
    }

    /**
     * @param $shipmentId
     * @return string
     * @throws NotFoundException|PdfParserException|\Zend_Pdf_Exception
     */
    public function get($shipmentId)
    {
        $shipment = $this->shipmentRepository->get($shipmentId);

        if (!$shipment) {
            return '';
        }

        $packingSlip = $this->pdfShipment->create($shipment);
        return $this->barcodeMerger->add($packingSlip, $shipment);
    }
}

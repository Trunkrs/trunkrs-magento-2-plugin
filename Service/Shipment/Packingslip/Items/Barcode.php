<?php

namespace Trunkrs\Carrier\Service\Shipment\Packingslip\Items;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Io\File as IoFile;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReaderException;
use Psr\Log\LoggerInterface as Log;

class Barcode implements ItemsInterface
{
    public const TMP_TRUNKRS_LABEL_PATH = 'tmp' . DIRECTORY_SEPARATOR . 'temptrunkrslabel';
    public const TMP_TRUNKRS_LABEL_FILE = 'trunkrs_temp_label.jpeg';

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var IoFile
     */
    private $ioFile;

    /**
     * @var array
     */
    private $fileList = [];

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var Log
     */
    private $logger;

    /**
     * @param DirectoryList $directoryList
     * @param IoFile $ioFile
     * @param Log $logger
     */
    public function __construct(
        DirectoryList $directoryList,
        IoFile        $ioFile,
        Log           $logger
    ) {
        $this->directoryList = $directoryList;
        $this->ioFile = $ioFile;
        $this->logger = $logger;
    }

    /**
     * Add the shipment barcode to the packing slip.
     *
     * @param string $packingSlip
     * @param \Magento\Sales\Api\Data\ShipmentInterface $shipment
     * @return string
     * @throws PdfParserException
     */
    public function add($packingSlip, $shipment)
    {
        $this->getFileName();
        $pdf = $this->loadPdfAndAddBarcode($packingSlip, $shipment->getShippingLabel());
        $this->cleanup();
        return $pdf->Output('S');
    }

    /**
     * Load the packing slip and overlay its barcode.
     *
     * @param string $packingSlip
     * @param string $label
     * @return Fpdi
     * @throws PdfParserException
     */
    private function loadPdfAndAddBarcode($packingSlip, $label)
    {
        $pdf = new Fpdi();
        try {
            $stream = StreamReader::createByString($packingSlip);
            $pageCount = $pdf->setSourceFile($stream);
        } catch (PdfReaderException $readerException) {
            $this->logger->error('Error while loading sourcefile: ' . $readerException->getMessage());
            return $pdf;
        }

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            try {
                $isLastPage = $pageCount === $pageNo;
                $templateId = $pdf->importPage($pageNo);
                $pageSize = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($pageSize['orientation'], $pageSize);
                $pdf->useTemplate($templateId);

                $this->addBarcodeToPage($isLastPage, $pageCount > 1, $pdf, $pageSize, $label);

                $pageCount = $pdf->setSourceFile($stream);

            } catch (PdfParserException $fpdiException) {
                $this->logger->error('[Barcode] PdfParserException: ' . $fpdiException->getMessage());
            } catch (PdfReaderException $readerException) {
                $this->logger->error('[Barcode] ReaderException: ' . $readerException->getMessage());
            } catch (FileSystemException $fileSystemException) {
                $this->logger->error('[Barcode] FileSystemException: ' . $fileSystemException->getMessage());
            }
        }

        return $pdf;
    }

    /**
     * Convert PDF points to millimeters.
     *
     * @param float $units
     * @return float
     */
    private function zendPdfUnitsToMM($units)
    {
        return ($units / 72) * 25.4;
    }

    /**
     * Overlay the label on the final packing slip page.
     *
     * @param bool $isLastPage
     * @param bool $isMultiplePages
     * @param Fpdi $pdf
     * @param array $pageSize
     * @param string $label
     * @return void
     * @throws FileSystemException
     */
    private function addBarcodeToPage($isLastPage, $isMultiplePages, $pdf, $pageSize, $label)
    {
        $position = [0, 0, 345, 220];

        // Calculate the horizontal center position
        $horizontalCenter = ($pageSize['width'] - $this->zendPdfUnitsToMM($position[2])) / 2;

        // Zend_PDF used BOTTOM LEFT as 0,0 and every point was 1/72 inch
        $x = $horizontalCenter;
        $y = $pageSize['height'] - $this->zendPdfUnitsToMM($position[3]);
        $w = $this->zendPdfUnitsToMM($position[2]);
        $h = $this->zendPdfUnitsToMM($position[3]) - $this->zendPdfUnitsToMM($position[1]);

        $tempAttachmentFile = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR
            . static::TMP_TRUNKRS_LABEL_PATH . '/' . uniqid('trunkrs-') . '.pdf';
        $this->ioFile->write($tempAttachmentFile, $label);

        if (($isLastPage && !$isMultiplePages) || ($isLastPage && $isMultiplePages)) {
            $pdf->setSourceFile($tempAttachmentFile);
            $overlayTemplate = $pdf->importPage(1);
            $pdf->useTemplate($overlayTemplate, $x, $y, $w, $h);
        }

        $this->ioFile->rm($tempAttachmentFile);
    }

    /**
     * Cleanup old files.
     */
    private function cleanup()
    {
        foreach ($this->fileList as $file) {
            $this->ioFile->rm($file);
        }
    }

    /**
     * Prepare the temporary label directory and file name.
     *
     * @return void
     * @throws FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getFileName()
    {
        $pathFile = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR . static::TMP_TRUNKRS_LABEL_PATH;
        $this->ioFile->checkAndCreateFolder($pathFile);

        $tempFileName = sha1(microtime()) . '-' . time() . '-' . static::TMP_TRUNKRS_LABEL_FILE;
        $this->fileName = $pathFile . DIRECTORY_SEPARATOR . $tempFileName;
        $this->fileList[] = $this->fileName;
    }
}

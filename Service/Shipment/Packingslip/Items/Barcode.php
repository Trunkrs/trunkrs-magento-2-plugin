<?php

namespace Trunkrs\Carrier\Service\Shipment\Packingslip\Items;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Io\File as IoFile;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReaderException;
use PSR\Log\LoggerInterface as Log;

class Barcode implements ItemsInterface
{
    const TMP_TRUNKRS_LABEL_PATH = 'tmp' . DIRECTORY_SEPARATOR . 'temptrunkrslabel';
    const TMP_TRUNKRS_LABEL_FILE = 'trunkrs_temp_label.jpeg';

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
    )
    {
        $this->directoryList = $directoryList;
        $this->ioFile = $ioFile;
        $this->logger = $logger;
    }

    /**
     * @param $packingSlip
     * @param $shipment
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
     * @param $packingSlip
     * @param $label
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
     * @param $units
     * @return float
     */
    private function zendPdfUnitsToMM($units)
    {
        return ($units / 72) * 25.4;
    }

    /**
     * @param $isLastPage
     * @param $isMultiplePages
     * @param $pdf
     * @param $pageSize
     * @param $label
     * @return void
     * @throws FileSystemException
     */
    private function addBarcodeToPage($isLastPage, $isMultiplePages, $pdf, $pageSize, $label)
    {
        $position = [0, 0, 345, 220];

        // Zend_PDF used BOTTOMLEFT as 0,0 and every point was 1/72 inch
        $x = $this->zendPdfUnitsToMM($position[0]);
        $y = $pageSize['height'] - $this->zendPdfUnitsToMM($position[3]);
        $w = $this->zendPdfUnitsToMM($position[2]) - $x;
        $h = $this->zendPdfUnitsToMM($position[3]) - $this->zendPdfUnitsToMM($position[1]);

        $tempAttachmentFile = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR . static::TMP_TRUNKRS_LABEL_PATH . '/' . uniqid('trunkrs-') . '.pdf';
        file_put_contents($tempAttachmentFile, $label);

        if (($isLastPage && !$isMultiplePages) || ($isLastPage && $isMultiplePages)) {
            $pdf->setSourceFile($tempAttachmentFile);
            $overlayTemplate = $pdf->importPage(1);
            $pdf->useTemplate($overlayTemplate, $x, $y, $w, $h);
        }

        unlink($tempAttachmentFile);
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

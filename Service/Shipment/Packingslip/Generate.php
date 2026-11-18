<?php

namespace Trunkrs\Carrier\Service\Shipment\Packingslip;

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReaderException;
use Psr\Log\LoggerInterface;

class Generate
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Combine labels into a single PDF.
     *
     * @param array $labels
     * @return string
     */
    public function run(array $labels)
    {
        $pdf = new Fpdi();

        foreach ($labels as $label) {
            $pdf = $this->addLabelToPdf($label, $pdf);
        }

        return $pdf->Output('S');
    }

    /**
     * Append the label pages to the PDF.
     *
     * @param string $label
     * @param Fpdi $pdf
     * @return Fpdi
     */
    private function addLabelToPdf($label, $pdf)
    {
        if (empty($label)) {
            return $pdf;
        }

        try {
            $stream = StreamReader::createByString($label);
            $pageCount = $pdf->setSourceFile($stream);
        } catch (PdfParserException $parserException) {
            $this->logger->error('Error while parsing sourcefile: ' . $parserException->getMessage());
            return $pdf;
        }

        for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++) {
            try {
                $templateId = $pdf->importPage($pageIndex + 1);
                $pageSize = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($pageSize['orientation'], $pageSize);

                $pdf->useTemplate($templateId);
            } catch (PdfParserException $fpdiException) {
                $this->logger->error('PdfParserException: ' . $fpdiException->getMessage());
            } catch (PdfReaderException $readerException) {
                $this->logger->error('ReaderException: ' . $readerException->getMessage());
            }
        }

        return $pdf;
    }
}

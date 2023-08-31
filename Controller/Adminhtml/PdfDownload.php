<?php

namespace Trunkrs\Carrier\Controller\Adminhtml;

use Magento\Framework\App\ResponseInterface;
use Trunkrs\Carrier\Service\Framework\FileFactory;
use Trunkrs\Carrier\Service\Shipment\Packingslip\Generate as PackingslipGenerate;

class PdfDownload
{
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var PackingslipGenerate
     */
    private $packingslipGenerator;

    /**
     * PDF download constructor
     * @param FileFactory $fileFactory
     * @param PackingslipGenerate $packingslipGenerator
     */
    public function __construct(
        FileFactory         $fileFactory,
        PackingslipGenerate $packingslipGenerator,
    )
    {
        $this->fileFactory = $fileFactory;
        $this->packingslipGenerator = $packingslipGenerator;
    }

    /**
     * @param $labels
     * @param string $filename
     * @return ResponseInterface
     */
    public function get($labels, $filename = 'ShippingLabels')
    {
        $pdfLabel = $this->generateLabel($labels);

        return $this->fileFactory->create(
            $filename . '.pdf',
            $pdfLabel
        );
    }

    /**
     * @param $labels
     * @return string
     */
    private function generateLabel($labels)
    {
        return $this->packingslipGenerator->run($labels);
    }
}

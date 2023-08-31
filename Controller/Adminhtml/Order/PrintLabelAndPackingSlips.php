<?php

namespace Trunkrs\Carrier\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Psr\Log\LoggerInterface;
use setasign\Fpdi\PdfParser\PdfParserException;
use Trunkrs\Carrier\Controller\Adminhtml\LabelAbstract;
use Trunkrs\Carrier\Controller\Adminhtml\PdfDownload as GetPdf;
use Trunkrs\Carrier\Service\Shipment\Labelling\GetLabels;
use Trunkrs\Carrier\Service\Shipment\Packingslip\GetPackingslip;

class PrintLabelAndPackingSlips extends LabelAbstract
{
    const TRUNKRS_SHIPPING_CODE = 'trunkrsShipping_trunkrsShipping';
    const TRUNKRS_LABEL_IN_PACKINGSLIPS = 'trunkrs_packingslips';

    /**
     * @var array
     */
    protected $orderIds = [];
    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;
    /**
     * @var LabelGenerator
     */
    protected $labelGenerator;
    /**
     * @var FileFactory
     */
    protected $fileFactory;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var Filter
     */
    private $filter;
    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @param Context $context
     * @param GetLabels $getLabels
     * @param GetPdf $getPdf
     * @param GetPackingslip $getPackingSlip
     * @param CollectionFactory $collectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param FileFactory $fileFactory
     * @param LabelGenerator $labelGenerator
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     * @param Filter $filter
     */
    public function __construct(
        Context $context,
        GetLabels $getLabels,
        GetPdf $getPdf,
        GetPackingslip $getPackingSlip,
        CollectionFactory $collectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        FileFactory $fileFactory,
        LabelGenerator $labelGenerator,
        ShipmentRepositoryInterface $shipmentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger,
        Filter $filter,
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->fileFactory = $fileFactory;
        $this->labelGenerator = $labelGenerator;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->filter = $filter;
        parent::__construct($context, $getLabels, $getPdf, $getPackingSlip);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|null
     * @throws NotFoundException|PdfParserException|\Zend_Pdf_Exception
     */
    public function execute()
    {
        $collection = $this->orderCollectionFactory->create();

        try {
            $collection = $this->filter->getCollection($collection);
        } catch (LocalizedException $exception) {
            $this->messageManager->addWarningMessage($exception->getMessage());
            return null;
        }

        foreach ($collection as $order) {
            $trunkrsShipment = $order->getShippingMethod() === $this::TRUNKRS_SHIPPING_CODE;
            if($trunkrsShipment) {
                $this->orderIds[] = $order->getId();
                $this->handleShipmentDataFromOrder($order);
            }
        }

        if (empty($this->orderIds)) {
            $this->messageManager->addErrorMessage(
                __('No pdf generated. Trunkrs shipment not found.')
            );
            return $this->_redirect($this->_redirect->getRefererUrl());
        }

        return $this->getPdf->get($this->labels, self::TRUNKRS_LABEL_IN_PACKINGSLIPS);
    }

    /**
     * @param $order
     * @return void
     * @throws NotFoundException|PdfParserException|\Zend_Pdf_Exception
     */
    private function handleShipmentDataFromOrder($order)
    {
        $shipments = $this->getShipmentDataByOrderId($order->getId());

        if (!$shipments) {
            return;
        }

        $this->loadLabels($shipments);
    }

    /**
     * Shipment by Order id
     *
     * @param int $orderId
     * @return ShipmentInterface[]|null
     */
    public function getShipmentDataByOrderId(int $orderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $orderId)->create();
        try {
            $shipments = $this->shipmentRepository->getList($searchCriteria);
            $shipmentRecords = $shipments->getItems();
        } catch (\Exception $exception)  {
            $this->logger->critical($exception->getMessage());
            $shipmentRecords = null;
        }
        return $shipmentRecords;
    }

    /**
     * Handle loading shipments
     * @param $shipments
     * @return void
     * @throws NotFoundException|PdfParserException|\Zend_Pdf_Exception
     */
    private function loadLabels($shipments)
    {
        if (!is_array($shipments)) {
            $this->loadLabel($shipments);
            return;
        }

        foreach ($shipments as $shipment) {
            $this->loadLabel($shipment);
        }

    }

    /**
     * @param $shipment
     * @return void
     * @throws NotFoundException|\Zend_Pdf_Exception|PdfParserException
     */
    private function loadLabel($shipment)
    {
        $this->setPackingslip($shipment->getId());
    }
}

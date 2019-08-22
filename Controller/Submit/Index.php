<?php


namespace Xigen\Tracking\Controller\Submit;

/**
 * Class Index
 * @package Xigen\Tracking\Controller\Submit
 */
class Index extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Constructor
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\UrlFactory $urlFactory,
        \Magento\Framework\Convert\ConvertArray $convertArray,
        \Xigen\Tracking\Helper\Shipment $shipmentHelper,
        \Xigen\Tracking\Helper\Tracking $trackingHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->convertArray = $convertArray;
        $this->urlModel = $urlFactory->create();
        $this->shipmentHelper = $shipmentHelper;
        $this->trackingHelper = $trackingHelper;
        parent::__construct($context);
    }

    /**
     * Execute view action
     * @return \Magento\Framework\Controller\ResultInterface
     * /xigen_tracking/submit/index?oid=000000045&carrier=customer&title=Royal%20Mail&number=http://test.com/12345
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if ($params) {
            $orderId = $this->getRequest()->getParam('oid');
            $carrierCode = $this->getRequest()->getParam('carrier');
            $carrierTitle = $this->getRequest()->getParam('title');
            $trackingNumber = $this->getRequest()->getParam('number');

            if ($orderId && $carrierCode && $carrierTitle && $trackingNumber) {
                $order = $this->shipmentHelper
                    ->getOrderByIncrementId($orderId);

                if ($order) {
                    $shipment = $this->shipmentHelper
                        ->getOrderShipmentByIncrementId($orderId);
    
                    if (!$shipment) {
                        $this->shipmentHelper->markAsShipped($order);
    
                        $shipment = $this->shipmentHelper
                            ->getOrderShipmentByIncrementId($orderId);
                    }
    
                    if (!$shipment) {
                        $data = [
                            'error' => "true",
                            'message' => __("Shipment not found")
                        ];
                    } else {
                        $tracking = $this->trackingHelper->addTrackingNumberToShipment(
                            $shipment,
                            $trackingNumber,
                            $carrierCode,
                            $carrierTitle
                        );
        
                        $data = [
                            'error' => "false",
                            'tracking_number' => $trackingNumber,
                            'carrier_code' => $carrierCode,
                            'carrier_title' => $carrierTitle,
                            'message' => __("Tracking added")
                        ];
                    }
                } else {
                    $data = [
                        'error' => "true",
                        'message' => __("Order not found")
                    ];
                }
            } else {
                $data = [
                    'error' => "true",
                    'message' => __("One or more parameters missing")
                ];
            }
        } else {
            $data = [
                'error' => "true",
                'message' => __("Parameters not provided")
            ];
        }

        $result = $this->resultRawFactory->create();
        $result->setHeader('Content-Type', 'text/xml');
        $xml = $this->convertArray->assocToXml($data, 'result');
        $result->setContents($xml->asXML());
 
        return $result;
    }
}

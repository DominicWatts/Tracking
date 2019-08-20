<?php


namespace Xigen\Tracking\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Tracking
 * @package Xigen\Tracking\Helper
 */
class Tracking extends AbstractHelper
{
    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $trackFactory;

    /**
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
    ) {
        $this->logger = $logger;
        $this->trackFactory = $trackFactory;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param string $trackingNumber
     * @param string $carrierCode
     * @param string $carrierTitle
     *
     * @return void
     */
    public function addTrackingNumberToShipment(
        \Magento\Sales\Model\Order\Shipment $shipment,
        $trackingNumber,
        $carrierCode,
        $carrierTitle
    ) {
        $shipment->addTrack(
            $this->trackFactory->create()
                ->setNumber($trackingNumber)
                ->setCarrierCode($carrierCode)
                ->setTitle($carrierTitle)
        );
        try {
            $shipment->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        return $shipment;
    }
}

<?php


namespace Xigen\Tracking\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Shipping\Model\ShipmentNotifier;

/**
 * AutoShip helper class
 */
class Shipment extends AbstractHelper
{
    const SEND_EMAIL_NO = 0;
    const SEND_EMAIL_YES = 1;

    /**
     * @var \Psr\Log\LoggerInterfaces
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    protected $convertOrder;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    /**
     * Shipment constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Model\Convert\Order $convertOrder
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->logger = $logger;
        $this->convertOrder = $convertOrder;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        parent::__construct($context);
    }

    /**
     * Load order by increment Id
     * @param string $incrementId
     * @return \Magento\Sales\Model\Data\Order
     */
    public function getOrderByIncrementId($incrementId = null)
    {
        if (!$incrementId) {
            return false;
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementId, 'eq')
            ->create();
        $order = $this->orderRepository
            ->getList($searchCriteria)
            ->getFirstItem();
        if ($order && $order->getId()) {
            return $order;
        }
        return false;
    }

    /**
     * Get shipment from order
     * @param string $incrementId
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function getOrderShipmentByIncrementId($incrementId = null)
    {
        if (!$incrementId) {
            return false;
        }
        $order = $this->getOrderByIncrementId($incrementId);
        if ($order) {
            $shipment = $order->getShipmentsCollection()
                ->getFirstItem();
            if ($shipment && $shipment->getId()) {
                return $shipment;
            }
        }
        return false;
    }

    /**
     * Mark order as shipped
     * @param \Magento\Sales\Model\Order $order
     * @param boolean $doNotify
     * @return bool
     */
    public function markAsShipped($order, $doNotify = true)
    {
        if (!$order || !$order->canShip()) {
            return;
        }
        try {
            $orderShipment = $this->convertOrder->toShipment($order);
            foreach ($order->getAllItems() as $orderItem) {
                if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                    continue;
                }
                $shipmentItem = $this->convertOrder
                    ->itemToShipmentItem($orderItem)
                    ->setQty($orderItem->getQtyToShip());
                $orderShipment->addItem($shipmentItem);
            }
            $orderShipment->register();
            $orderShipment->getOrder()->setIsInProcess(true);

            $orderShipment->save();
            $orderShipment->getOrder()->save();

            if ($doNotify) {
                $this->objectManager->create(ShipmentNotifier::class)
                    ->notify($orderShipment);
                $orderShipment->save();
            }

            $order->addStatusToHistory($order->getStatus(), 'Order has been marked as complete');
            $order->save();

            return true;
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        return false;
    }
}

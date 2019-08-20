<?php

namespace Xigen\Tracking\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddTracking
 * @package Xigen\Tracking\Console\Command
 */
class AddTracking extends Command
{
    const ORDERID_OPTION = 'orderid';
    const CARRIER_OPTION = 'carrier';
    const TITLE_OPTION = 'title';
    const NUMBER_OPTION = 'number';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var \Xigen\Tracking\Helper\Shipment
     */
    protected $shipmentHelper;

    /**
     * @var \Xigen\Tracking\Helper\Shipment
     */
    protected $trackingHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * AutoShip constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\State $state
     * @param \Xigen\Tracking\Helper\Shipment $shipmentHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\State $state,
        \Xigen\Tracking\Helper\Shipment $shipmentHelper,
        \Xigen\Tracking\Helper\Tracking $trackingHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
        $this->logger = $logger;
        $this->state = $state;
        $this->shipmentHelper = $shipmentHelper;
        $this->trackingHelper = $trackingHelper;
        $this->dateTime = $dateTime;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     * phpcs:disable
     * xigen:tracking:addtracking [-o|--orderid ORDERID] [-c|--carrier [CARRIER]] [-t|--title [TITLE]] [-u|--number [NUMBER]]
     * php bin/magento xigen:tracking:addtracking -o 000000045 -c custom -t "Royal Mail" -u "http://test.com/12345"
     * phpcs:enable
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        $orderId = $this->input->getOption(self::ORDERID_OPTION);
        $carrierCode = $this->input->getOption(self::CARRIER_OPTION);
        $carrierTitle = $this->input->getOption(self::TITLE_OPTION);
        $trackingNumber = $this->input->getOption(self::NUMBER_OPTION);

        if ($orderId && $carrierCode && $carrierTitle && $trackingNumber) {
            $this->output->writeln((string) __('%1 Processing order <info>%2</info>', $this->dateTime->gmtDate(), $orderId));

            $order = $this->shipmentHelper
                ->getOrderByIncrementId($orderId);

            if ($order) {
                $shipment = $this->shipmentHelper
                    ->getOrderShipmentByIncrementId($orderId);

                if (!$shipment) {
                    $this->shipmentHelper->markAsShipped($order);

                    $shipment = $this->shipmentHelper
                        ->getOrderShipmentByIncrementId($orderId);

                    if ($shipment) {
                        $this->output->writeln((string) __('%1 Created Shipment for order <info>%2</info>', $this->dateTime->gmtDate(), $orderId));
                    }
                }

                if (!$shipment) {
                    $this->output->writeln((string) __('%1 Shipment not found <info>%2</info>', $this->dateTime->gmtDate(), $orderId));
                    return Cli::RETURN_FAILURE;
                }

                $tracking = $this->trackingHelper->addTrackingNumberToShipment(
                    $shipment,
                    $trackingNumber,
                    $carrierCode,
                    $carrierTitle
                );

                $message = $tracking ? '[success]' : '[failure]';
                $this->output->writeln((string) __('%1 <info>%2</info> tracking added on order %3', $this->dateTime->gmtDate(), $message, $orderId));
            } else {
                $this->output->writeln((string) __('%1 Order not found <info>%2</info>', $this->dateTime->gmtDate(), $orderId));
                return Cli::RETURN_FAILURE;
            }
        } else {
            $this->output->writeln((string) __('%1 Provide order ID, carrier, title and number', $this->dateTime->gmtDate()));
            return Cli::RETURN_FAILURE;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("xigen:tracking:addtracking");
        $this->setDescription("Add tracking to order via shipment");
        $this->setDefinition([
            new InputOption(self::ORDERID_OPTION, '-o', InputOption::VALUE_REQUIRED, 'Order Increment ID'),
            new InputOption(self::CARRIER_OPTION, '-c', InputOption::VALUE_REQUIRED, 'Carrier'),
            new InputOption(self::TITLE_OPTION, '-t', InputOption::VALUE_REQUIRED, 'Title'),
            new InputOption(self::NUMBER_OPTION, '-u', InputOption::VALUE_REQUIRED, 'Number')
        ]);
        parent::configure();
    }
}

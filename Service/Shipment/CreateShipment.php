<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

namespace TIG\RoutiGo\Service\Shipment;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection as ShipmentCollection;
use TIG\RoutiGo\Logging\Log;
use TIG\RoutiGo\Model\Carrier\RoutiGo;
use TIG\RoutiGo\Model\Config\Provider\Carrier;

class CreateShipment
{
    /**
     * @var Order\ShipmentFactory
     */
    private $shipmentFactory;

    /**
     * @var Order\ShipmentRepository
     */
    private $shipmentRepository;

    /**
     * @var ConvertOrder
     */
    private $convertOrder;

    /**
     * @var Log
     */
    private $logger;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var TrackFactory
     */
    private $trackFactory;

    /**
     * @var Carrier
     */
    private $carrierConfig;

    /**
     * CreateShipment constructor.
     *
     * @param Order\ShipmentFactory $shipmentFactory
     * @param Order\ShipmentRepository $shipmentRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param ConvertOrder $convertOrder
     * @param Carrier $carrierConfig
     * @param TrackFactory $trackFactory
     * @param Log $logger
     */
    public function __construct(
        Order\ShipmentFactory    $shipmentFactory,
        Order\ShipmentRepository $shipmentRepository,
        OrderRepositoryInterface $orderRepository,
        ConvertOrder             $convertOrder,
        Carrier                  $carrierConfig,
        TrackFactory             $trackFactory,
        Log                      $logger
    )
    {
        $this->convertOrder = $convertOrder;
        $this->shipmentFactory = $shipmentFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->orderRepository = $orderRepository;
        $this->trackFactory = $trackFactory;
        $this->carrierConfig = $carrierConfig;
        $this->logger = $logger;
    }

    /**
     * @param int $orderId
     * @param null $trackingId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createOrUpdateOrderShipment($orderId, $trackingId = null)
    {
        $order = $this->orderRepository->get($orderId);
        if (!$order) {
            return;
        }

        if ($order->canShip()) {
           $this->createShipmentForOrder($order, $trackingId);
           return;
        }

        if ($trackingId) {
            $this->attachTrackToShipments($order->getShipmentsCollection(), $trackingId);
        }
    }

    /**
     * @param Order $order
     * @param $trackingId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createShipmentForOrder(Order $order, $trackingId = null) {
        $shipment = $this->convertOrder->toShipment($order);

        foreach ($order->getAllItems() as $orderItem) {
            $this->addShipmentItems($orderItem, $shipment);
        }

        $shipment->register();

        try {
            if ($trackingId) {
                $this->createTrackForShipment($shipment, $trackingId);
            }
            $this->shipmentRepository->save($shipment);
            $this->orderRepository->save($order);
        } catch (\Exception $exception) {
            $this->logger->critical('Something went wrong while creating the shipment for order with id: ' . $order->getEntityId() . ', ' . $exception->getMessage());
        }
    }

    /**
     * @param ShipmentInterface[]|ShipmentCollection $shipments
     * @param $trackingId
     * @return void
     */
    protected function attachTrackToShipments($shipments, $trackingId) {
        foreach ($shipments as $shipment) {
            try {
                $this->createTrackForShipment($shipment, $trackingId);
                $this->shipmentRepository->save($shipment);
            } catch (\Exception $exception) {
 		$message = 'Something went wrong while creating the tracking code for shipment with id: '
                    . $shipment->getEntityId() . ', ' . $exception->getMessage();
                $this->logger->critical($message);
            }
        }
    }

    /**
     * @param ShipmentInterface $shipment
     * @param string $trackingId
     * @return void
     */
    protected function createTrackForShipment($shipment, $trackingId)
    {
        /**
         * @var Track $track
         */
        $track = $this->trackFactory->create();
        $track->setNumber($trackingId);
        $track->setCarrierCode(RoutiGo::TIG_ROUTIGO_SHIPPING_METHOD);
        $track->setTitle($this->carrierConfig->getCarrierTitle());
        $shipment->addTrack($track);
    }

    /**
     * @param $orderItem
     * @param $shipment
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addShipmentItems($orderItem, $shipment)
    {
        $qtyShipped = $orderItem->getQtyToShip();
        $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);
        $shipment->addItem($shipmentItem);

        return $this;
    }

}

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
namespace TIG\Routigo\Service\Shipment;

use Exception;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order;
use TIG\Routigo\Logging\Log;

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
     * @var array
     */
    private $errors = [];

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var Log
     */
    private $logger;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    private $createdShipments;

    /**
     * CreateShipment constructor.
     *
     * @param Order\ShipmentFactory    $shipmentFactory
     * @param Order\ShipmentRepository $shipmentRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param ConvertOrder             $convertOrder
     * @param ManagerInterface         $messageManager
     * @param Log                      $logger
     */
    public function __construct(
        Order\ShipmentFactory $shipmentFactory,
        Order\ShipmentRepository $shipmentRepository,
        OrderRepositoryInterface $orderRepository,
        ConvertOrder $convertOrder,
        ManagerInterface $messageManager,
        Log $logger
    ) {
        $this->shipmentFactory    = $shipmentFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->convertOrder       = $convertOrder;
        $this->messageManager     = $messageManager;
        $this->logger             = $logger;
        $this->orderRepository    = $orderRepository;
    }

    /**
     * @param Order $order
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(Order $order)
    {
        if ($order->canShip()) {
            $shipment = $this->convertOrder->toShipment($order);

            foreach ($order->getAllItems() as $orderItem) {
               $this->addShipmentItems($orderItem, $shipment);
            }

            $shipment->register();
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus(Order::STATE_PROCESSING);

            try {
                $this->shipmentRepository->save($shipment);
                $this->orderRepository->save($order);
                $this->createdShipments[] = $shipment;
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage($exception->getMessage());
                $this->logger->critical('Something went wrong while creating the shipment with orderid: ' . $order->getId() . ', ' .$exception->getMessage());
            }
        } else {
            foreach ($order->getShipmentsCollection()->getItems() as $shipment) {
                $this->createdShipments[] = $shipment;
            }
        }
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
        $qtyShipped   = $orderItem->getQtyToShip();
        $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);
        $shipment->addItem($shipmentItem);

        return $this;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getCreatedShipments()
    {
        return $this->createdShipments;
    }
}

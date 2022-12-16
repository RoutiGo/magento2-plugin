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
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
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

namespace TIG\RoutiGo\Observer;


use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use TIG\RoutiGo\Config\Provider\General\Configuration;
use TIG\RoutiGo\Logging\Log;
use TIG\RoutiGo\Model\Carrier\RoutiGo;
use TIG\RoutiGo\Service\Shipment\CreateShipment;
use TIG\RoutiGo\Service\Shipment\UploadStop;

class OrderSaveObserver implements ObserverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var UploadStop
     */
    private $uploadStop;

    /**
     * @var Log
     */
    private $log;

    /**
     * @var CreateShipment
     */
    private $createShipment;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param Configuration $configuration
     * @param UploadStop $uploadStop
     * @param CreateShipment $createShipment
     * @param Log $log
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Configuration            $configuration,
        UploadStop               $uploadStop,
        CreateShipment           $createShipment,
        Log                      $log
    )
    {
        $this->orderRepository = $orderRepository;
        $this->configuration = $configuration;
        $this->uploadStop = $uploadStop;
        $this->log = $log;
        $this->createShipment = $createShipment;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /**
         * @var Order $order
         */
        $order = $observer->getEvent()->getOrder();

        if (!$order) {
            return;
        }

        $autoUploadWithStatus = $this->configuration->getAutoUploadWithStatus();
        if (!$this->configuration->isEnabled() || $autoUploadWithStatus === null) {
            return;
        }

        if ($order->getShippingMethod() !== RoutiGo::TIG_ROUTIGO_COMPLETE_NAME) {
            return;
        }

        if ($order->getStatus() === $autoUploadWithStatus && $order->getOrigData('status') !== $order->getData('status')) {
            try {
                $this->uploadStop->upload([$order]);
            } catch (\Exception $exception) {
                $this->log->error('TIG_RoutiGo: Fault during auto-upload ' . $exception->getMessage());
            }
        }
    }

}

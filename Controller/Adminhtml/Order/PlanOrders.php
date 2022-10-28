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

namespace TIG\RoutiGo\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Shipping\Model\Order\TrackFactory;
use Magento\Ui\Component\MassAction\Filter;
use TIG\RoutiGo\Service\Shipment\AddTrack;
use TIG\RoutiGo\Service\Shipment\CreateShipment;
use TIG\RoutiGo\Service\Shipment\UploadStop;

class PlanOrders extends Action implements HttpPostActionInterface
{
    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CreateShipment
     */
    private $createShipment;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var UploadStop
     */
    private $uploadStop;

    /**
     * @var AddTrack
     */
    private $addTrack;

    /**
     * PlanOrders constructor.
     *
     * @param Context $context
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Filter $filter
     * @param CreateShipment $createShipment
     * @param RedirectFactory $redirectFactory
     * @param AddTrack $addTrack
     * @param UploadStop $uploadStop
     */
    public function __construct(
        Context                $context,
        OrderCollectionFactory $orderCollectionFactory,
        Filter                 $filter,
        CreateShipment         $createShipment,
        RedirectFactory        $redirectFactory,
        AddTrack               $addTrack,
        UploadStop             $uploadStop
    )
    {
        parent::__construct($context);
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->filter = $filter;
        $this->createShipment = $createShipment;
        $this->redirectFactory = $redirectFactory;
        $this->uploadStop = $uploadStop;
        $this->addTrack = $addTrack;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException|\Zend_Http_Client_Exception
     */
    public function execute()
    {
        $collection = $this->orderCollectionFactory->create();
        $collection = $this->filter->getCollection($collection);

        /** @var Order $order */
        foreach ($collection as $order) {
            $this->createShipment->create($order);
        }

        $createdShipments = $this->createShipment->getCreatedShipments();

        if (!$createdShipments) {
            $this->messageManager->addWarningMessage(
                'No shipments created, so no route is planned.'
            );

            return $this->_redirect('sales/order/index');
        }

        $result = $this->uploadStop->upload($createdShipments);

        $this->addTrack->assignTrackingCodeToShipments(
            $createdShipments,
            $result['trackingId'])
        ;

        $this->messageManager->addSuccessMessage(
            sprintf(
                'Sucessfully planned orders in RoutiGo. The trackingId is %s',
                $result['trackingId']
            )
        );

        return $this->_redirect('sales/order/index');
    }
}

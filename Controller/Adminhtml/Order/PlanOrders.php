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
namespace TIG\Routigo\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use TIG\Routigo\Service\Shipment\CreateShipment;
use TIG\Routigo\Service\Shipment\UploadStop;

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
     * PlanOrders constructor.
     *
     * @param Context                  $context
     * @param OrderCollectionFactory   $orderCollectionFactory
     * @param Filter                   $filter
     * @param CreateShipment           $createShipment
     * @param RedirectFactory          $redirectFactory
     * @param UploadStop               $uploadStop
     */
    public function __construct(
        Context $context,
        OrderCollectionFactory $orderCollectionFactory,
        Filter $filter,
        CreateShipment $createShipment,
        RedirectFactory $redirectFactory,
        UploadStop $uploadStop
    ) {
        parent::__construct($context);
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->filter = $filter;
        $this->createShipment = $createShipment;
        $this->redirectFactory = $redirectFactory;
        $this->uploadStop = $uploadStop;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $collection = $this->orderCollectionFactory->create();
        $collection = $this->filter->getCollection($collection);

        /** @var Order $order */
        foreach ($collection as $order) {
            $this->createShipment->create($order);
        }

        $this->uploadStop->upload($this->createShipment->getCreatedShipments());

        return $this->_redirect('sales/order/index');
    }
}

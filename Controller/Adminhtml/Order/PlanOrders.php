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
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use TIG\RoutiGo\Model\Carrier\RoutiGo;
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
     * @var UploadStop
     */
    private $uploadStop;

    /**
     * PlanOrders constructor.
     *
     * @param Context $context
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Filter $filter
     * @param CreateShipment $createShipment
     * @param UploadStop $uploadStop
     */
    public function __construct(
        Context                $context,
        OrderCollectionFactory $orderCollectionFactory,
        Filter                 $filter,
        UploadStop             $uploadStop
    )
    {
        parent::__construct($context);
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->filter = $filter;
        $this->uploadStop = $uploadStop;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException|\Zend_Http_Client_Exception
     */
    public function execute()
    {
        $collection = $this->orderCollectionFactory->create();
        $collection->addFilter('shipping_method', RoutiGo::TIG_ROUTIGO_COMPLETE_NAME);
        $collection = $this->filter->getCollection($collection);

        if ($collection->count() === 0) {
            $this->messageManager->addWarningMessage(
                'No orders selected, so no stops are uploaded.'
            );

            return $this->_redirect('sales/order/index');
        }

        $this->uploadStop->upload($collection);

        $this->messageManager->addSuccessMessage(
            sprintf(
                'Successfully added stop%s to RoutiGo',
                $collection->count() > 1 ? 's' : ''
            )
        );

        return $this->_redirect('sales/order/index');
    }
}

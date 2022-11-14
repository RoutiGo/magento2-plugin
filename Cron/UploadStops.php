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

namespace TIG\RoutiGo\Cron;


use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use TIG\RoutiGo\Config\Provider\General\Configuration;
use TIG\RoutiGo\Service\Shipment\UploadStop;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class UploadStops
{
    private UploadStop $uploadStop;
    private CollectionFactory $collectionFactory;
    private Configuration $configuration;
    private StoreManagerInterface $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager,
        UploadStop $uploadStop,
        CollectionFactory $collectionFactory,
        Configuration $configuration
    ) {
        $this->uploadStop = $uploadStop;
        $this->collectionFactory = $collectionFactory;
        $this->configuration = $configuration;
        $this->storeManager = $storeManager;
    }

    public function exportOrders(){
        foreach ($this->storeManager->getStores() as $store) {
            $this->exportOrdersForStore($store);
        }
    }

    protected function exportOrdersForStore(StoreInterface $store) {
        $storeId = $store->getId();
        if (!$this->configuration->isEnabled($storeId)) {
            return;
        }

        $statuses = [];

        /**
         * @var Collection $collection
         */
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('status',
                ['in' => $statuses]
            );

    }
}

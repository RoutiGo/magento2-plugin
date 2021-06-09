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

namespace TIG\RoutiGo\Service\Shipment;

use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\Information;
use Magento\Store\Model\StoreManagerInterface;
use TIG\RoutiGo\Webservices\Endpoints\UploadStops;

/**
 * Class UploadStop
 * Upload a new set of stops to the RoutiGo system.
 */
class UploadStop
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Information
     */
    private $information;

    /**
     * @var UploadStops
     */
    private $uploadStops;
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * UploadStop constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Information           $information
     * @param UploadStops           $uploadStops
     * @param QuoteFactory          $quoteFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Information $information,
        UploadStops $uploadStops,
        QuoteFactory $quoteFactory
    ) {

        $this->storeManager = $storeManager;
        $this->information = $information;
        $this->uploadStops = $uploadStops;
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * @param $shipments
     *
     * @return array|mixed|\Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
     */
    public function upload($shipments)
    {
        $data = [];
        foreach($shipments as $shipment) {
            $shipmentData = [];
            $order = $shipment->getOrder();
            $quote = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
            $shipmentData['identifier'] = $shipment->getIncrementId();
            $shipmentData['scheduledDeliveryData'] = $this->getDeliveryDate($quote);
            $shipmentData['deliveryLocation'] = $this->getDeliveryLocation($shipment);

            $data[] = $shipmentData;
        }

        $result = $this->uploadStops->call($data, true);

        return $result;
    }

    /**
     * @param $shipment
     *
     * @return array
     */
    public function getDeliveryLocation($shipment)
    {
        $deliveryLocation = [];
        $shippingAddress  = $shipment->getShippingAddress();
        $houseNumber = $this->getHouseNumber($shippingAddress);
        $deliveryLocation['addressInformation']['houseNumber'] = $houseNumber;
        $deliveryLocation['addressInformation']['postcode']    = $shippingAddress->getPostCode();
        $deliveryLocation['addressInformation']['countryCode'] = $shippingAddress->getCountryId();

        return $deliveryLocation;
    }

    /**
     * @param $shippingAddress
     *
     * @return bool|mixed
     */
    public function getHouseNumber($shippingAddress)
    {
        if (isset($shippingAddress->getStreet()[1])) {
            return $shippingAddress->getStreet()[1];
        }

        preg_match_all('!\d+!', $shippingAddress->getStreet()[0], $matches);

        if (isset($matches[0])) {
            $houseNumber = end($matches[0]);
            return $houseNumber;
        }

        //error handling
        return false;
    }

    /**
     * @param $quote
     *
     * @return string
     * @throws \Exception
     */
    public function getDeliveryDate($quote)
    {
        if (!$quote->getShippingAddress()->getRoutigoDeliveryDate()) {
            $dateTime = new \DateTime('tomorrow');
            $date = $dateTime->format('Y-m-d');

            return $date;
        }

        return $quote->getShippingAddress()->getRoutigoDeliveryDate();
    }
}

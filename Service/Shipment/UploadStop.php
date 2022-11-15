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
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\History;
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
     * @var Information
     */
    private $storeInformation;

    /**
     * @var History
     */
    private $orderHistoryResource;

    /**
     * UploadStop constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Information $information
     * @param UploadStops $uploadStops
     * @param QuoteFactory $quoteFactory
     * @param Information $storeInformation
     * @param History $orderHistoryResource
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Information           $information,
        UploadStops           $uploadStops,
        QuoteFactory          $quoteFactory,
        Information           $storeInformation,
        History               $orderHistoryResource
    )
    {
        $this->storeManager = $storeManager;
        $this->information = $information;
        $this->uploadStops = $uploadStops;
        $this->quoteFactory = $quoteFactory;
        $this->storeInformation = $storeInformation;
        $this->orderHistoryResource = $orderHistoryResource;
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
        $pickupLocation = ['addressInformation' => []];

        $store = $this->storeManager->getStore();
        $address = $this->storeInformation->getStoreInformationObject($store);

        $addressParts = $this->splitStreetIntoParts($address->getData('street_line1'));

        $pickupLocation['addressInformation']['name'] = $address->getData('name');
        $pickupLocation['addressInformation']['streetName'] = $addressParts['street'];
        $pickupLocation['addressInformation']['houseNumber'] = $addressParts['houseNumber'];
        $pickupLocation['addressInformation']['houseNumberAddition'] = $addressParts['houseNumberAddition'];
        $pickupLocation['addressInformation']['postcode'] = $address->getData('postcode');
        $pickupLocation['addressInformation']['cityName'] = $address->getData('city');
        $pickupLocation['addressInformation']['countryCode'] = $address->getData('country_id');

        /**
         * @var ShipmentInterface $shipment
         */
        foreach ($shipments as $shipment) {
            $shipmentData = [];
            $order = $shipment->getOrder();
            $quote = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
            $shipmentData['identifier'] = $shipment->getEntityId();
            $shipmentData['scheduledDeliveryDate'] = $this->getDeliveryDate($quote);
            $shipmentData['deliveryLocation'] = $this->getDeliveryLocation($shipment);
            $shipmentData['pickupLocation'] = $pickupLocation;
            $data[] = $shipmentData;
        }

        $result = $this->uploadStops->call($data, true);

        if ($result['http_status'] === 202 && isset($result['trackingId'])) {
            $this->saveBatchIdAsHistoryComment($result['trackingId'], $shipments);
        }

        return $result;
    }

    /**
     * Save BatchId as Order History Comment
     *
     * @param $batchId
     * @param $shipments
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function saveBatchIdAsHistoryComment($batchId, $shipments)
    {
        /**
         * @var ShipmentInterface $shipment
         */
        foreach ($shipments as $shipment) {
            $orderHistory = $shipment->getOrder()->addCommentToStatusHistory(
                __('Uploaded to RoutiGo with batchId %1', $batchId)
            );
            $this->orderHistoryResource->save($orderHistory);
        }
    }

    /**
     * @param $shipment
     *
     * @return array
     */
    public function getDeliveryLocation($shipment)
    {
        $deliveryLocation = [];
        $shippingAddress = $shipment->getShippingAddress();
        $streetParts = $this->splitStreetIntoParts($shippingAddress->getStreet()[0]);

        $name = $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname();
        $deliveryLocation['addressInformation']['name'] = $name;
        $deliveryLocation['addressInformation']['streetName'] = $streetParts['street'];
        $deliveryLocation['addressInformation']['houseNumber'] = $streetParts['houseNumber'];
        $deliveryLocation['addressInformation']['houseNumberAddition'] = $streetParts['houseNumberAddition'];
        $deliveryLocation['addressInformation']['postcode'] = $shippingAddress->getPostCode();
        $deliveryLocation['addressInformation']['cityName'] = $shippingAddress->getCity();
        $deliveryLocation['addressInformation']['countryCode'] = $shippingAddress->getCountryId();

        return $deliveryLocation;
    }

    /**
     * Split street into parts
     *
     * @param $streetStr
     *
     * @see https://gist.github.com/R0B3RDV/e94c46c44a603e02afa2d226c6ef6367
     * @return array
     */
    private function splitStreetIntoParts($streetStr)
    {
        $aMatch         = [];
        $pattern        = '#^([\w[:punct:] ]+) (\d{1,5})\s?([\w[:punct:]\-/]*)$#';
        preg_match($pattern, $streetStr, $aMatch);
        $street         = $aMatch[1] ?? $streetStr;
        $number         = $aMatch[2] ?? '';
        $numberAddition = $aMatch[3] ?? '';
        return ['street' => $street, 'houseNumber' => $number, 'houseNumberAddition' => $numberAddition];

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

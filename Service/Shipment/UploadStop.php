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

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Sales\Model\ResourceModel\Order\Status\History;
use Magento\Store\Model\Information;
use Magento\Store\Model\StoreManagerInterface;
use TIG\RoutiGo\Config\Provider\General\Configuration;
use TIG\RoutiGo\Service\Config\Webhook;
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
     * @var UploadStops
     */
    private $uploadStops;

    /**
     * @var Information
     */
    private $storeInformation;

    /**
     * @var History
     */
    private $orderHistoryResource;

    /**
     * @var Configuration
     */
    private $routiGoConfiguration;

    /**
     * @var Order
     */
    private $orderResource;
    private Webhook $webhookService;

    /**
     * UploadStop constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param UploadStops $uploadStops
     * @param Information $storeInformation
     * @param History $orderHistoryResource
     * @param Order $orderResource
     * @param Configuration $routiGoConfiguration
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        UploadStops           $uploadStops,
        Information           $storeInformation,
        History               $orderHistoryResource,
        Order                 $orderResource,
        Configuration         $routiGoConfiguration,
        Webhook               $webhookService
    )
    {
        $this->storeManager = $storeManager;
        $this->uploadStops = $uploadStops;
        $this->storeInformation = $storeInformation;
        $this->orderHistoryResource = $orderHistoryResource;
        $this->routiGoConfiguration = $routiGoConfiguration;
        $this->orderResource = $orderResource;
        $this->webhookService = $webhookService;
    }

    /**
     * @param Order\Collection|\Magento\Sales\Model\Order[] $orders
     *
     * @return array|mixed|\Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
     */
    public function upload($orders)
    {
        $data = [];
        $pickupLocation = $this->getPickupLocationData();

        foreach ($orders as $order) {
            $shipmentData = [];

            $shipmentData['identifier'] = $order->getEntityId();
            $shipmentData['scheduledDeliveryDate'] = $this->getDeliveryDate($order);
            $shipmentData['deliveryLocation'] = $this->getDeliveryLocation($order);
            $shipmentData['pickupLocation'] = $pickupLocation;
            $data[] = $shipmentData;
        }

        $result = $this->uploadStops->call($data, true);

        if ($result['http_status'] === 202 && isset($result['trackingId'])) {
            $this->changeOrderStatusIfWanted($orders);
            $this->saveBatchIdAsHistoryComment($result['trackingId'], $orders);
            // Upload success, lets check if we can receive data back
            $this->webhookService->checkOrCreateWebhook();
        }

        return $result;
    }

    private function getPickupLocationData()
    {
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

        $emptyFieldCount = 0;
        foreach ($pickupLocation['addressInformation'] as $item) {
            if (empty($item)) {
                $emptyFieldCount++;
            }
        }

        // If all fields are empty, then the whole pickuplocation object should be null for the api call
        if ($emptyFieldCount >= count($pickupLocation['addressInformation'])) {
            $pickupLocation = null;
        }

        return $pickupLocation;
    }

    /**
     * Save BatchId as Order History Comment
     *
     * @param $batchId
     * @param Order\Collection|\Magento\Sales\Model\Order[] $orderCollection
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function saveBatchIdAsHistoryComment($batchId, $orderCollection)
    {
        /**
         * @var ShipmentInterface $shipment
         */
        foreach ($orderCollection as $order) {
            $orderHistory = $order->addCommentToStatusHistory(
                __('Uploaded to RoutiGo with batchId %1', $batchId)
            );
            $this->orderHistoryResource->save($orderHistory);
        }
    }

    /**
     * @param Order\Collection|\Magento\Sales\Model\Order[] $orders
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function changeOrderStatusIfWanted($orders)
    {
        $changeStatusTo = $this->routiGoConfiguration->getUploadChangeToStatus();

        if (!$changeStatusTo) {
            return;
        }

        foreach ($orders as $order) {
            $order->setStatus($changeStatusTo);
            $this->orderResource->save($order);
        }
    }

    /**
     * @param OrderInterface $order
     *
     * @return array
     */
    public function getDeliveryLocation($order)
    {
        $deliveryLocation = [];
        $shippingAddress = $order->getShippingAddress();
        $streetParts = $this->splitStreetIntoParts( implode(' ', $shippingAddress->getStreet()));

        $name = $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname();
        $deliveryLocation['addressInformation']['name'] = $name;
        $deliveryLocation['addressInformation']['streetName'] = $streetParts['street'];
        $deliveryLocation['addressInformation']['houseNumber'] = $streetParts['houseNumber'];
        $deliveryLocation['addressInformation']['houseNumberAddition'] = $streetParts['houseNumberAddition'];
        $deliveryLocation['addressInformation']['postcode'] = $shippingAddress->getPostCode();
        $deliveryLocation['addressInformation']['cityName'] = $shippingAddress->getCity();
        $deliveryLocation['addressInformation']['countryCode'] = $shippingAddress->getCountryId();

        $deliveryLocation['visitAfter'] = $this->convertTimeToISO8601($shippingAddress->getRoutigoVisitAfter());
        $deliveryLocation['visitBefore'] = $this->convertTimeToISO8601($shippingAddress->getRoutigoVisitBefore());

        return $deliveryLocation;
    }


    /**
     * Ex. 08:15 or 17:44 needs to be converted to PT8H or PT17H44M
     * @param $timeDigital
     * @return string|null
     */
    private function convertTimeToISO8601($timeDigital)
    {
        if (empty($timeDigital)) {
            return null;
        }

        $timeParts = explode(':', $timeDigital);

        if (count($timeParts) == 1) {
            return null;
        }

        if (count($timeParts) == 2) {
            $time = (3600 * (int) $timeParts[0]) + (60 * (int) $timeParts[1]);
        }

        if (count($timeParts) == 3) {
            $time = (3600 * (int) $timeParts[0]) + (60 * (int) $timeParts[1]) + (int) $timeParts[2];
        }

        $units = array(
            "H" =>        3600,
            "M" =>          60,
            "S" =>           1,
        );

        $str = "P";
        $istime = false;

        foreach ($units as $unitName => &$unit) {
            $quot  = intval($time / $unit);
            $time -= $quot * $unit;
            $unit  = $quot;
            if ($unit > 0) {
                if (!$istime && in_array($unitName, array("H", "M", "S"))) { // There may be a better way to do this
                    $str .= "T";
                    $istime = true;
                }
                $str .= strval($unit) . $unitName;
            }
        }

        return $str;
    }

    /**
     * Split street into parts
     *
     * @param $streetStr
     *
     * @return array
     * @see https://gist.github.com/R0B3RDV/e94c46c44a603e02afa2d226c6ef6367
     */
    private function splitStreetIntoParts($streetStr)
    {
        if ($streetStr === null) {
            $streetStr = '';
        }

        $aMatch = [];
        $pattern = '#^([\w[:punct:] ]+) (\d{1,5})\s?([\w[:punct:]\-/]*)$#';
        preg_match($pattern, $streetStr, $aMatch);
        $street = $aMatch[1] ?? $streetStr;
        $number = $aMatch[2] ?? '';
        $numberAddition = $aMatch[3] ?? '';
        return ['street' => $street, 'houseNumber' => $number, 'houseNumberAddition' => $numberAddition];

    }

    /**
     * @param OrderInterface $order
     *
     * @return string
     * @throws \Exception
     */
    public function getDeliveryDate($order)
    {
        if (!$order->getShippingAddress()->getRoutigoDeliveryDate()) {
            $dateTime = new \DateTime('tomorrow');
            return $dateTime->format('Y-m-d');
        }

        return $order->getShippingAddress()->getRoutigoDeliveryDate();
    }
}

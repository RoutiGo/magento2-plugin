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

namespace TIG\Routigo\Service\Shipment;

use Magento\Store\Model\Information;
use Magento\Store\Model\StoreManagerInterface;
use TIG\Routigo\Webservices\Endpoints\UploadStops;

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
     * UploadStop constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Information           $information
     * @param UploadStops           $uploadStops
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Information $information,
        UploadStops $uploadStops
    ) {

        $this->storeManager = $storeManager;
        $this->information = $information;
        $this->uploadStops = $uploadStops;
    }

    public function upload($shipments)
    {
        $data = [];
        foreach($shipments as $shipment) {
            $shipmentData = [];
            $shipmentData['identifier'] = $shipment->getIncrementId();
            $shipmentData['scheduledDeliveryData'] = '2020-05-11';
            $shipmentData['deliveryLocation'] = $this->getDeliveryLocation($shipment);

            $data[] = $shipmentData;
        }

        $result = $this->uploadStops->call($data, true);

        return $result;
    }

//    public function getPickupLocation()
//    {
//        $pickupLocation = [];
//        $houseNumber = $storeInformation->g
//        preg_match_all('!\d+!', $order->get_shipping_address_1(), $matches);
//        $houseNumber = array_pop($matches);
//        $storeInformation = $this->information->getStoreInformationObject($this->storeManager->getStore());
//        $pickupLocation['addressInformation']['houseNumber'] = $houseNumber;
//        $pickupLocation['addressInformation']['postcode']    = $storeInformation->getPostcode();
//        $pickupLocation['addressInformation']['countryCode'] = $storeInformation->getCountryId();
//
//        return $pickupLocation;
//    }

    public function getDeliveryLocation($shipment)
    {
        $deliveryLocation = [];
        $shippingAddress  = $shipment->getShippingAddress();
        $deliveryLocation['addressInformation']['houseNumber'] = $shippingAddress->getStreet()[1];
        $deliveryLocation['addressInformation']['postcode']    = $shippingAddress->getPostCode();
        $deliveryLocation['addressInformation']['countryCode'] = $shippingAddress->getCountryId();

        return $deliveryLocation;
    }
}

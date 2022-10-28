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

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment;
use Magento\Shipping\Model\Order\TrackFactory;
use TIG\RoutiGo\Model\Carrier\RoutiGo;
use TIG\RoutiGo\Model\Config\Provider\Carrier;

/**
 * Class AddTrack
 * Adds Tracking numbers to shipments
 */
class AddTrack
{
    /**
     * @var Carrier
     */
    private $carrierConfig;

    /**
     * @var TrackFactory
     */
    private $trackFactory;

    /**
     * @var Shipment
     */
    private $shipmentResource;

    /**
     * AddTrack constructor.
     *
     */
    public function __construct(
        Carrier      $carrierConfig,
        TrackFactory $trackFactory,
        Shipment $shipmentResource
    )
    {
        $this->carrierConfig = $carrierConfig;
        $this->trackFactory = $trackFactory;
        $this->shipmentResource = $shipmentResource;
    }

    /**
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @var string $trackingCode
     * @var ShipmentInterface[] $shipments
     */
    public function assignTrackingCodeToShipments($shipments, $trackingCode)
    {
        foreach($shipments as $shipment) {
            $track = $this->trackFactory->create();
            $track->setNumber($trackingCode);
            $track->setCarrierCode(RoutiGo::TIG_ROUTIGO);
            $track->setTitle($this->carrierConfig->getCarrierTitle());
            $shipment->addTrack($track);

            $this->shipmentResource->save($shipment);
        }
    }


}

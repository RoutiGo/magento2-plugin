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

namespace TIG\RoutiGo\Model\Api;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentInterfaceFactory;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\Spi\ShipmentResourceInterface;
use Magento\Shipping\Model\Order\TrackFactory;
use TIG\RoutiGo\Api\WebhookInterface;
use TIG\RoutiGo\Logging\Log;
use TIG\RoutiGo\Model\Carrier\RoutiGo;
use TIG\RoutiGo\Model\Config\Provider\Carrier;
use TIG\RoutiGo\Service\Shipment\UploadStop;

class Webhook implements WebhookInterface
{
    const ROUTE_PLANNED = 'ROUTE_PLANNED';

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Log
     */
    private Log $log;

    /**
     * @var ShipmentResourceInterface
     */
    private $shipmentResource;

    /**
     * @var ShipmentInterfaceFactory
     */
    private $shipmentFactory;

    /**
     * @var Carrier
     */
    private $carrierConfig;

    /**
     * @var TrackFactory
     */
    private $trackFactory;

    /**
     * @param RequestInterface $request
     * @param Log $log
     * @param ShipmentResourceInterface $shipmentResource
     * @param ShipmentInterfaceFactory $shipmentFactory
     * @param Carrier $carrierConfig
     * @param TrackFactory $trackFactory
     */
    public function __construct(
        RequestInterface          $request,
        Log                       $log,
        ShipmentResourceInterface $shipmentResource,
        ShipmentInterfaceFactory  $shipmentFactory,
        Carrier                   $carrierConfig,
        TrackFactory              $trackFactory
    )
    {
        $this->request = $request;
        $this->log = $log;
        $this->shipmentResource = $shipmentResource;
        $this->shipmentFactory = $shipmentFactory;
        $this->carrierConfig = $carrierConfig;
        $this->trackFactory = $trackFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function saveRoutigoData()
    {
        $rawContent = $this->request->getContent();
        $params = json_decode($rawContent, true);

        if (!isset($params['eventType'])) {
            $this->log->warning('RoutiGo webhook called without eventType');
            throw new WebapiException(__('Request is not a RoutiGo webhook request'), WebapiException::HTTP_BAD_REQUEST, WebapiException::HTTP_BAD_REQUEST);
        }

        if ($params['eventType'] !== self::ROUTE_PLANNED) {
            $this->log->warning('RoutiGo webhook called for other event than ROUTE_PLANNED');
            throw new WebapiException(__('Only ROUTE_PLANNED event is implemented'), WebapiException::HTTP_BAD_REQUEST, WebapiException::HTTP_BAD_REQUEST);
        }

        if (!isset($params['journey']['tourLegs'])) {
            $this->log->warning('RoutiGo webhook contains no tourloegs');
            throw new WebapiException(__('Tourlegs needs to be supplied to be processed'), WebapiException::HTTP_BAD_REQUEST, WebapiException::HTTP_BAD_REQUEST);
        }

        $this->createTrackForTourLegs($params['journey']['tourLegs']);

        return "";
    }

    /**
     * @param $tourLegs
     * @return void
     */
    protected function createTrackForTourLegs($tourLegs)
    {
        foreach ($tourLegs as $tourLeg) {
            $splitParcelId = explode('_', $tourLeg['parcelId']);

            if (count($splitParcelId) < 2) {
                $this->log->debug(sprintf('Cannot get shipment ID from %s', $splitParcelId));
                continue;
            }

            /**
             * We pass the Shipment EntityId as Identifier, we can find this back using the first part of the parcelId
             * @see UploadStop::upload()
             */
            $entityId = $splitParcelId[0];

            $this->createTrackForShipmentId($entityId, $tourLeg['trackingCode']);
        }
    }

    /**
     * @param string $entityId
     * @param string $trackingId
     * @return void
     */
    protected function createTrackForShipmentId($entityId, $trackingId)
    {
        /**
         * @var ShipmentInterface $shipment
         */
        $shipment = $this->shipmentFactory->create();
        $this->shipmentResource->load($shipment, $entityId, ShipmentInterface::ENTITY_ID);
        if (!$shipment) {
            return;
        }

        /**
         * @var Track $track
         */
        $track = $this->trackFactory->create();
        $track->setNumber($trackingId);
        $track->setCarrierCode(RoutiGo::TIG_ROUTIGO);
        $track->setTitle($this->carrierConfig->getCarrierTitle());
        $shipment->addTrack($track);

        $this->shipmentResource->save($shipment);
    }
}

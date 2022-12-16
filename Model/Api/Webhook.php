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
use Magento\Sales\Api\Data\ShipmentInterfaceFactory;
use Magento\Shipping\Model\Order\TrackFactory;
use TIG\RoutiGo\Api\WebhookInterface;
use TIG\RoutiGo\Logging\Log;
use TIG\RoutiGo\Model\Config\Provider\WebhookConfiguration;
use TIG\RoutiGo\Service\Shipment\CreateShipment;
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
    private $log;

    /**
     * @var CreateShipment
     */
    private $createShipment;
    private WebhookConfiguration $webhookConfiguration;

    /**
     * @param RequestInterface $request
     * @param Log $log
     * @param CreateShipment $createShipment
     */
    public function __construct(
        RequestInterface     $request,
        Log                  $log,
        CreateShipment       $createShipment,
        WebhookConfiguration $webhookConfiguration
    )
    {
        $this->request = $request;
        $this->log = $log;
        $this->createShipment = $createShipment;
        $this->webhookConfiguration = $webhookConfiguration;
    }

    /**
     * {@inheritDoc}
     */
    public function saveRoutigoData()
    {
        $authorizationHeader = $this->request->getHeader('Authorization') ?? '';
        if (
            !preg_match('/^Bearer ([0-9a-z]+)$/i', $authorizationHeader, $match) ||
            $match[1] !== $this->webhookConfiguration->getOrCreateWebhookToken()
        ) {
            throw new WebapiException(__('User not authorized'), WebapiException::HTTP_FORBIDDEN, WebapiException::HTTP_FORBIDDEN);
        }

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
                $this->log->debug(sprintf('Cannot get Order ID from %s', $splitParcelId));
                continue;
            }

            /**
             * We pass the Order EntityId as Identifier, we can find this back using the first part of the parcelId
             * @see UploadStop::upload()
             */
            $entityId = $splitParcelId[0];

            $this->createShipment->createOrUpdateOrderShipment($entityId, $tourLeg['trackingCode']);
        }
    }

}

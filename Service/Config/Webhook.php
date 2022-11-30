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

namespace TIG\RoutiGo\Service\Config;


use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Url;
use TIG\RoutiGo\Logging\Log;
use TIG\RoutiGo\Model\Config\Provider\WebhookConfiguration;
use TIG\RoutiGo\Webservices\Endpoints\CreateWebhook as CreateWebhookEndpoint;
use TIG\RoutiGo\Webservices\Endpoints\ListWebhooks as ListWebhooksEndpoint;

class Webhook
{
    const HEADER_AUTHORIZATION = 'Authorization';
    private CreateWebhookEndpoint $createWebhook;
    private ListWebhooksEndpoint $listWebhooks;
    private Url $frontendUrlBuilder;
    private WebhookConfiguration $webhookConfiguration;
    private Log $log;

    public function __construct(
        CreateWebhookEndpoint $createWebhook,
        ListWebhooksEndpoint  $listWebhooks,
        Url                   $frontendUrlBuilder,
        WebhookConfiguration  $webhookConfiguration,
        Log                   $log
    )
    {
        $this->createWebhook = $createWebhook;
        $this->listWebhooks = $listWebhooks;
        $this->frontendUrlBuilder = $frontendUrlBuilder;
        $this->webhookConfiguration = $webhookConfiguration;
        $this->log = $log;
    }

    /**
     * @return string|null
     */
    protected function getWebhookUrl()
    {
        return $this->frontendUrlBuilder->getUrl(
            null,
            [
                '_path' => 'enquiry',
                '_secure' => true,
                '_direct' => 'rest/V1/routigo/webhook'
            ]
        );
    }

    /**
     * @param $token
     * @return bool|string
     * @throws \Zend_Http_Client_Exception
     */
    public function createWebhook($token)
    {
        $url = $this->getWebhookUrl();
        $webhookData = [
            "routigoEvent" => "ROUTE_PLANNED",
            "routigoEventType" => "ROUTE",
            "action" => [
                "name" => "Sends RoutiGo Planned event to Magento2 webshop",
                "url" => $url,
                "headers" => [
                    self::HEADER_AUTHORIZATION => "Bearer " . $token,
                ],
                "secondsDelayBeforeRetry" => 600,
                "minutesToLive" => 1440
            ]
        ];
        $result = $this->createWebhook->call($webhookData, true);

        return $result['http_status'] === 201;
    }

    /**
     * Check if Webhook is already Created
     *
     * @param string|null $token
     * @return bool
     * @throws \Zend_Http_Client_Exception
     */
    public function isWebhookAlreadyCreated($token)
    {
        $url = $this->getWebhookUrl();
        $webhooks = $this->listWebhooks->call([]);

        foreach ($webhooks as $webhook) {
            if (isset($webhook['action']['url']) &&
                $webhook['action']['url'] === $url &&
                isset($webhook['action']['headers'][self::HEADER_AUTHORIZATION]) &&
                $webhook['action']['headers'][self::HEADER_AUTHORIZATION] === "Bearer " . $token) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if Webhook exists, if not Create
     *
     * @return void
     */
    public function checkOrCreateWebhook()
    {
        try {
            $token = $this->webhookConfiguration->getOrCreateWebhookToken();
            if ($this->isWebhookAlreadyCreated($token)) {
                return;
            }
            $this->createWebhook($token);
        } catch (LocalizedException|\Zend_Http_Client_Exception $exception) {
            $this->log->error($exception);
        }
    }


}

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

namespace TIG\RoutiGo\Controller\Adminhtml\Config;


use Magento\Backend\App\Action;
use Magento\Framework\Url;
use TIG\RoutiGo\Logging\Log;
use TIG\RoutiGo\Model\Api\Webhook;
use TIG\RoutiGo\Model\Config\Provider\WebhookConfiguration;
use TIG\RoutiGo\Service\Integration\TokenService;
use TIG\RoutiGo\Webservices\Endpoints\CreateWebhook as CreateWebhookEndpoint;
use TIG\RoutiGo\Webservices\Endpoints\ListWebhooks as ListWebhooksEndpoint;

class CreateWebhook extends Action
{
    const HEADER_AUTHORIZATION = 'Authorization';

    /**
     * @var Log
     */
    private $log;

    /**
     * @var CreateWebhookEndpoint
     */
    private $createWebhook;

    /**
     * @var Url
     */
    private $frontendUrlBuilder;

    /**
     * @var ListWebhooksEndpoint
     */
    private $listWebhooks;


    /**
     * @var WebhookConfiguration
     */
    private $webhookConfiguration;

    /**
     * @param Action\Context $context
     * @param WebhookConfiguration $webhookConfiguration
     * @param Log $log
     * @param CreateWebhookEndpoint $createWebhook
     * @param ListWebhooksEndpoint $listWebhooks
     * @param Url $frontendUrlBuilder
     */
    public function __construct(
        Action\Context        $context,
        WebhookConfiguration  $webhookConfiguration,
        Log                   $log,
        CreateWebhookEndpoint $createWebhook,
        ListWebhooksEndpoint  $listWebhooks,
        Url                   $frontendUrlBuilder
    )
    {
        parent::__construct($context);

        $this->log = $log;
        $this->createWebhook = $createWebhook;
        $this->frontendUrlBuilder = $frontendUrlBuilder;
        $this->listWebhooks = $listWebhooks;
        $this->webhookConfiguration = $webhookConfiguration;
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
    protected function createWebhook($token)
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
     * @param $token
     * @return bool
     * @throws \Zend_Http_Client_Exception
     */
    protected function isWebhookAlreadyCreated($token)
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
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Zend_Http_Client_Exception
     */
    public function execute()
    {
        $response = $this->getResponse();
        $token = $this->webhookConfiguration->getOrCreateWebhookToken();

        if (!$token) {
            $result = [
                'error' => true,
                //@codingStandardsIgnoreLine
                'message' => __('Could not create a Webhook Integration token.')
            ];
            return $response->representJson(\Zend_Json::encode($result));
        }

        if ($this->isWebhookAlreadyCreated($token)) {
            $result = [
                'error' => false,
                'message' => __('Webhook already created!')
            ];
            return $response->representJson(\Zend_Json::encode($result));
        }

        if ($this->createWebhook($token)) {
            $result = [
                'error' => false,
                'message' => __('Webhook created!')
            ];
            return $response->representJson(\Zend_Json::encode($result));
        }

        $result['message'] = __('An exception occured while creating the webhook.');

        return $response->representJson(\Zend_Json::encode($result));
    }
}

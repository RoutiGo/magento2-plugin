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
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use TIG\RoutiGo\Model\Config\Provider\WebhookConfiguration;
use TIG\RoutiGo\Service\Config\Webhook;
use TIG\RoutiGo\Service\Integration\TokenService;
use Zend_Http_Client_Exception;
use Zend_Json;

class ValidateWebhook extends Action
{
    /**
     * @var Webhook
     */
    private $webhookService;
    private WebhookConfiguration $webhookConfiguration;

    /**
     * @param Action\Context $context
     * @param Webhook $webhookService
     * @param WebhookConfiguration $webhookConfiguration
     */
    public function __construct(
        Action\Context $context,
        Webhook        $webhookService,
        WebhookConfiguration  $webhookConfiguration
    ) {
        parent::__construct($context);
        $this->webhookService = $webhookService;
        $this->webhookConfiguration = $webhookConfiguration;
    }


    /**
     * @return ResponseInterface
     * @throws Zend_Http_Client_Exception
     * @throws LocalizedException
     */
    public function execute()
    {
        $response = $this->getResponse();
        $hasToken = !empty($this->webhookConfiguration->getWebhookToken());
        $token = $this->webhookConfiguration->getOrCreateWebhookToken();

        if (!$token) {
            $result = [
                'error' => true,
                //@codingStandardsIgnoreLine
                'message' => __('Could not create a Webhook token.')
            ];
            return $response->representJson(Zend_Json::encode($result));
        }

        if ($this->webhookService->isWebhookAlreadyCreated($token)) {
            $result = [
                'error' => false,
                'message' => __('Webhook already created!')
            ];
            return $response->representJson(Zend_Json::encode($result));
        }

        if ($this->webhookService->createWebhook($token)) {
            $result = [
                'error' => false,
                'message' => __('Webhook created! Token was %1 available', $hasToken ? __('already') : __('not yet'))
            ];
            return $response->representJson(Zend_Json::encode($result));
        }

        $result['message'] = __('An exception occurred while creating the webhook.');

        return $response->representJson(Zend_Json::encode($result));
    }
}

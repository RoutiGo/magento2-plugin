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

namespace TIG\RoutiGo\Service\Integration;

use Magento\Integration\Model\AuthorizationService;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Integration\Model\OauthService;
use Magento\Integration\Model\ResourceModel\Oauth\Token as ResourceToken;
use TIG\RoutiGo\Logging\Log;

class TokenService
{
    const WEBHOOK_INTEGRATION_NAME = 'RoutiGo Webhook';
    const WEBHOOK_ACL_NAME = 'TIG_RoutiGo::webhook';

    /**
     * @var IntegrationFactory
     */
    private $integrationFactory;

    /**
     * @var OauthService
     */
    private $oauthService;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var TokenFactory
     */
    private $tokenFactory;
    private ResourceToken $resourceToken;
    private Log $log;

    public function __construct(
        IntegrationFactory   $integrationFactory,
        OauthService         $oauthService,
        AuthorizationService $authorizationService,
        TokenFactory         $tokenFactory,
        ResourceToken        $resourceToken,
        Log                   $log
    ){
        $this->integrationFactory = $integrationFactory;
        $this->oauthService = $oauthService;
        $this->authorizationService = $authorizationService;
        $this->tokenFactory = $tokenFactory;
        $this->resourceToken = $resourceToken;
        $this->log = $log;
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Oauth\Exception
     */
    public function getOrCreateIntegration()
    {
        $integrationExists = $this->integrationFactory->create()->load(self::WEBHOOK_INTEGRATION_NAME, 'name')->getData();
        if (!empty($integrationExists)) {
            $token = $this->tokenFactory->create()->load($integrationExists['consumer_id'], 'consumer_id');
            return $token->getData('token');
        }

        $integrationData = array(
            'name' => self::WEBHOOK_INTEGRATION_NAME,
            'status' => '1',
            'setup_type' => '0'
        );
        try {
            $integration = $this->integrationFactory->create();
            $integration->setData($integrationData);
            $integration->save();
            $integrationId = $integration->getId();

            // Create consumer
            $consumerName = 'Integration' . $integrationId;
            $consumer = $this->oauthService->createConsumer(['name' => $consumerName]);
            $consumerId = $consumer->getId();
            $integration->setConsumerId($consumer->getId());
            $integration->save();

            // Authorize
            $this->authorizationService->grantPermissions($integrationId, [
                self::WEBHOOK_ACL_NAME
            ]);

            /**
             * @var Token $token
             */
            $token = $this->tokenFactory->create();
            $token->createVerifierToken($consumerId);
            $token->setType('access');
            $this->resourceToken->save($token);

            return $token->getData('token');
        } catch (\Exception $e) {
            $this->log->error($e->getMessage());
        }

        return null;
    }
}

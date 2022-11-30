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
 * to support@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\RoutiGo\Model\Config\Provider;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Math\Random;
use TIG\RoutiGo\Model\AbstractConfigProvider;
use TIG\RoutiGo\Config\Provider\General\Configuration;
use Magento\Framework\App\Config\Storage\WriterInterface;

class WebhookConfiguration extends AbstractConfigProvider
{
    const XPATH_WEBHOOK_TOKEN      = 'tig_routigo/webook/token';

    /** @var Configuration */
    private $configuration;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var Random
     */
    private $mathRandom;

    /**
     * @param ScopeConfig $scopeConfig
     * @param WriterInterface $writer
     * @param Configuration $configuration
     * @param EncryptorInterface $encryptor
     * @param Random $mathRandom
     */
    public function __construct(
        ScopeConfig   $scopeConfig,
        WriterInterface $writer,
        Configuration $configuration,
        EncryptorInterface $encryptor,
        Random $mathRandom
    ) {
        parent::__construct($scopeConfig, $writer);

        $this->configuration = $configuration;
        $this->encryptor = $encryptor;
        $this->mathRandom = $mathRandom;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrCreateWebhookToken()
    {
        $token = $this->getConfigValue(static::XPATH_WEBHOOK_TOKEN);
        if ($token) {
            return $this->encryptor->decrypt($token);
        }
        $token = $this->mathRandom->getRandomString(32);

        $this->setConfigValue(static::XPATH_WEBHOOK_TOKEN, $this->encryptor->encrypt($token));

        return $token;
    }

    /**
     * @return mixed
     */
    public function getWebhookToken()
    {
        return $this->getConfigValue(static::XPATH_WEBHOOK_TOKEN);
    }
}

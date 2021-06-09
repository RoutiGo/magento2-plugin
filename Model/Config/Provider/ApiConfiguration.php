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
use TIG\RoutiGo\Model\AbstractConfigProvider;
use TIG\RoutiGo\Config\Provider\General\Configuration;

class ApiConfiguration extends AbstractConfigProvider
{
    const XPATH_ENDPOINTS_API_BASE_URL = 'tig_routigo/endpoints/api_base_url';
    const XPATH_ENDPOINTS_TEST_API_BASE_URL = 'tig_routigo/endpoints/api_base_url';

    /** @var Configuration */
    private $configuration;

    /**
     * @param ScopeConfig   $scopeConfig
     * @param Configuration $configuration
     */
    public function __construct(
        ScopeConfig $scopeConfig,
        Configuration $configuration
    ) {
        parent::__construct($scopeConfig);

        $this->configuration = $configuration;
    }

    /**
     * @return mixed
     */
    public function getLiveApiBaseUrl()
    {
        return $this->getConfigValue(static::XPATH_ENDPOINTS_API_BASE_URL);
    }

    /**
     * @return mixed
     */
    public function getTestApiBaseUrl()
    {
        return $this->getConfigValue(static::XPATH_ENDPOINTS_TEST_API_BASE_URL);
    }

    /**
     * @return mixed
     */
    public function getModeApiBaseUrl()
    {
        if ($this->configuration->liveModeEnabled()) {
            return $this->getLiveApiBaseUrl();
        }

        return $this->getTestApiBaseUrl();
    }
}

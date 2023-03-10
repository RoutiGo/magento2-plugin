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
 * to servicedesk@tig.nl so we can send you a copy immediately.
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

namespace TIG\RoutiGo\Model;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

abstract class AbstractConfigProvider
{
    /** @var ScopeConfig ScopeConfig */
    private $scopeConfig;
    private WriterInterface $configWriter;

    /**
     * Config constructor.
     *
     * @param ScopeConfig $scopeConfig
     */
    public function __construct(
        ScopeConfig     $scopeConfig,
        WriterInterface $configWriter
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    /**
     * @param $path
     * @param $store
     *
     * @return mixed
     */
    public function getConfigValue($path, $store = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param $path
     * @param $value
     * @param $store
     **/
    public function setConfigValue($path, $value, $store = null)
    {
        if ($store !== null) {
            $this->configWriter->save($path, $value, ScopeInterface::SCOPE_STORE, $store);
            return;
        }
        $this->configWriter->save($path, $value);
    }
}

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

namespace TIG\RoutiGo\Config\Provider\General;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\Encryption\EncryptorInterface;
use TIG\RoutiGo\Model\AbstractConfigProvider;
use Magento\Framework\App\Config\Storage\WriterInterface;

class Configuration extends AbstractConfigProvider
{
    const ROUTIGO_GENERAL_MODE = 'tig_routigo/general/mode';
    const ROUTIGO_KEY = 'tig_routigo/generalconfiguration_extension_status/api_key';
    const ROUTIGO_AUTO_UPLOAD_STATUS = 'tig_routigo/upload/auto_upload_with_status';
    const ROUTIGO_UPLOAD_CHANGE_TO_STATUS = 'tig_routigo/upload/upload_change_to_status';

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Configuration constructor.
     *
     * @param ScopeConfig $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ScopeConfig        $scopeConfig,
        WriterInterface    $writer,
        EncryptorInterface $encryptor
    )
    {
        parent::__construct($scopeConfig, $writer);
        $this->encryptor = $encryptor;
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function getMode($store = null)
    {
        return $this->getConfigValue(static::ROUTIGO_GENERAL_MODE, $store);
    }

    /**
     * @param null $store
     *
     * @return bool
     */
    public function liveModeEnabled($store = null)
    {
        if ($this->getMode($store) == 1) {
            return true;
        }

        return false;
    }

    /**
     * @param null $store
     *
     * @return bool
     */
    public function testModeEnabled($store = null)
    {
        if ($this->getMode($store) == 2) {
            return true;
        }

        return false;
    }

    /**
     * @param null $store
     *
     * @return bool
     */
    public function isEnabled($store = null)
    {
        if ($this->testModeEnabled($store) || $this->liveModeEnabled($store)) {
            return true;
        }

        return false;
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function getKey($store = null)
    {
        $key = $this->getConfigValue(static::ROUTIGO_KEY, $store);

        return $this->encryptor->decrypt($key);
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function getAutoUploadWithStatus($store = null)
    {
        return $this->getConfigValue(static::ROUTIGO_AUTO_UPLOAD_STATUS, $store);
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function getUploadChangeToStatus($store = null)
    {
        return $this->getConfigValue(static::ROUTIGO_UPLOAD_CHANGE_TO_STATUS, $store);
    }
}

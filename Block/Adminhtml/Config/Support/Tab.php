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

namespace TIG\Routigo\Block\Adminhtml\Config\Support;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Tab extends Template implements RendererInterface
{
    const MODULE_NAME       = 'TIG_Routigo';
    const EXTENSION_VERSION = '1.1.0';
    const XPATH_ROUTIGO_SUPPORTED_MAGENTO_VERSION = 'tig_routigo/supported_magento_version';

    // @codingStandardsIgnoreLine
    protected $_template = 'TIG_Routigo::config/support/tab.phtml';

    /** @var array */
    private $phpVersionSupport = [
        '2.3' => ['7.3' => ['+']],
        '2.4' => ['7.3' => ['+'], '7.4' => ['+']]
    ];

    /** @var ProductMetadataInterface */
    private $productMetadata;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Tab constructor.
     *
     * @param Template\Context         $context
     * @param ProductMetadataInterface $productMetadata
     * @param ScopeConfigInterface     $scopeConfig
     * @param array                    $data
     */
    public function __construct(
        Template\Context $context,
        ProductMetadataInterface $productMetadata,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->productMetadata      = $productMetadata;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(AbstractElement $element)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->setElement($element);

        return $this->toHtml();
    }

    /**
     * Retrieve the version number from the database.
     *
     * @return bool|false|string
     */
    public function getVersionNumber()
    {
        return static::EXTENSION_VERSION;
    }

    /**
     * @return string
     */
    public function getSupportedMagentoVersions()
    {
        return $this->scopeConfig->getValue(static::XPATH_ROUTIGO_SUPPORTED_MAGENTO_VERSION);
    }

    /**
     * @param $phpPatch
     * @param $currentVersion
     *
     * @return bool
     */
    public function getPhpVersion($phpPatch, $currentVersion)
    {
        $return = false;

        if (in_array($phpPatch, $currentVersion)
            || (in_array('+', $currentVersion)
                && $phpPatch >= max(
                    $currentVersion
                ))) {
            $return = true;
        }

        return $return;
    }

    /**
     * @return bool|int
     */
    /** @codingStandardsIgnoreStart */
    public function phpVersionCheck()
    {
        $magentoVersion = $this->getMagentoVersionArray();
        $phpVersion     = $this->getPhpVersionArray();

        if (!is_array($magentoVersion) || !is_array($phpVersion)) {
            return - 1;
        }

        $magentoMajorMinor = $magentoVersion[0] . '.' . $magentoVersion[1];
        $phpMajorMinor     = $phpVersion[0] . '.' . $phpVersion[1];
        $phpPatch          = (int) $phpVersion[2];

        if (!isset($this->phpVersionSupport[$magentoMajorMinor])
            || !isset($this->phpVersionSupport[$magentoMajorMinor][$phpMajorMinor])) {
            return 0;
        }

        $currentVersion = $this->phpVersionSupport[$magentoMajorMinor][$phpMajorMinor];
        if (isset($currentVersion)) {
            return $this->getPhpVersion($phpPatch, $currentVersion);
        }

        return - 1;
    }
    /** @codingStandardsIgnoreEnd */

    /**
     * @return array|bool
     */
    public function getPhpVersionArray()
    {
        $version = false;

        if (function_exists('phpversion')) {
            $version = explode('.', phpversion());
        }

        if (defined('PHP_VERSION')) {
            $version = explode('.', PHP_VERSION);
        }

        return $version;
    }

    /**
     * @return array|bool
     */
    public function getMagentoVersionArray()
    {
        $version        = false;
        $currentVersion = $this->productMetadata->getVersion();

        if (isset($currentVersion)) {
            $version = explode('.', $currentVersion);
        }

        return $version;
    }

    /**
     * @return array|bool
     */
    public function getMagentoVersionTidyString()
    {
        $magentoVersion = $this->getMagentoVersionArray();

        if (is_array($magentoVersion)) {
            return $magentoVersion[0] . '.' . $magentoVersion[1];
        }

        return false;
    }
}

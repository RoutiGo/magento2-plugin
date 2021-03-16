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
namespace TIG\Routigo\Block\Adminhtml\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Fieldset as MagentoFieldset;

class Fieldset extends MagentoFieldset
{
    const XPATH_ROUTIGO_GENERAL_MODE = 'tig_routigo/general/mode';

    private $classNames = [
        '1' => 'mode_live',
        '2' => 'mode_test',
        '0' => 'mode_off'
    ];

    /**
     * {@inheritdoc}
     */
    protected function _getFrontendClass($element)
    {
        $mode = $this->_scopeConfig->getValue(static::XPATH_ROUTIGO_GENERAL_MODE);
        $class = 'mode_off';
        if (array_key_exists($mode, $this->classNames)) {
            $class = $this->classNames[$mode];
        }
        return parent::_getFrontendClass($element) . ' ' . $class;
    }
}

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

namespace TIG\RoutiGo\Model\Config\Provider;

// @codingStandardsIgnoreFile
class Carrier extends AbstractConfigProvider
{

    const XPATH_CARRIER_DELIVERYDAYS       = 'tig_routigo/routigo_settings/shipment_days';
    const XPATH_CARRIER_CUT_OFF_TIME       = 'tig_routigo/routigo_settings/cutoff_time';
    const XPATH_CARRIER_TIMEFRAMES         = 'carriers/tig_routigo/timeframes/allowed_timeframes';
    const XPATH_CARRIER_TIMEFRAMES_ENABLED = 'carriers/tig_routigo/timeframes/timeframes_active';

    /**
     * @return bool
     */
    public function getDeliveryDays()
    {
        return $this->getConfigValue(self::XPATH_CARRIER_DELIVERYDAYS);
    }

    public function getTimeframes()
    {
        return $this->getConfigValue(self::XPATH_CARRIER_TIMEFRAMES);
    }

    public function isTimeFramesEnabled()
    {
        return $this->getConfigValue(self::XPATH_CARRIER_TIMEFRAMES_ENABLED);
    }

    public function getCutOffTime()
    {
        return $this->getConfigValue(self::XPATH_CARRIER_CUT_OFF_TIME);
    }
}

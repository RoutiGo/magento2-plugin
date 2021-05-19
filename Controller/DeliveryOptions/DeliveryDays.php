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

namespace TIG\Routigo\Controller\DeliveryOptions;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use TIG\Routigo\Controller\AbstractDeliveryOptions;
use TIG\Routigo\Model\Config\Provider\Carrier;

class DeliveryDays extends AbstractDeliveryOptions
{
    /** @var Session $checkoutSession */
    private $checkoutSession;

    /** @var LocaleResolver $scopeConfig */
    private $localeResolver;
    /**
     * @var Carrier
     */
    private $carrier;

    /**
     * Services constructor.
     *
     * @param Context        $context
     * @param Session        $checkoutSession
     * @param LocaleResolver $localeResolver
     * @param Carrier        $carrier
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        LocaleResolver $localeResolver,
        Carrier $carrier
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->localeResolver  = $localeResolver;
        $this->carrier = $carrier;

        parent::__construct($context);
    }

    public function execute()
    {
        $deliveryDays = $this->carrier->getDeliveryDays();
        $deliveryDays = explode(',', $deliveryDays);
        $deliveryDays = array_map(function($key) {
            return array(
                'day' => $key
            );
        }, $deliveryDays);

        $timeFrames = $this->carrier->getTimeframes();
        $timeFrames = json_decode($timeFrames);

        foreach($deliveryDays as &$deliveryDay) {
            foreach ($timeFrames as $key => $timeFrame) {
                if ($timeFrame->timeframe_day == $deliveryDay['day']) {
                    $deliveryDay['timeFrames'][$timeFrame->timeframe_sort_order] = [
                        'earliest_time' => $timeFrame->timeframe_earliest_time,
                        'latest_time' => $timeFrame->timeframe_latest_time
                    ];
                }
            }
        }

        foreach($deliveryDays as &$deliveryDay) {
            if (isset($deliveryDay['timeFrames'])) {
                ksort($deliveryDay['timeFrames'], SORT_NUMERIC);
                $deliveryDay['timeFrames'] = array_values($deliveryDay['timeFrames']);
            }
        }

        return $this->jsonResponse($deliveryDays);
    }
}

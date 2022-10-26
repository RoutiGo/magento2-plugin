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

namespace TIG\RoutiGo\Controller\DeliveryOptions;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use TIG\RoutiGo\Controller\AbstractDeliveryOptions;
use TIG\RoutiGo\Model\Config\Provider\Carrier;

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

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|mixed
     * @throws \Exception
     */
    public function execute()
    {
        if (!$this->carrier->isTimeFramesEnabled()) {
            return false;
        }

        $deliveryDays = $this->getDeliveryDays();
        $timeFrames   = $this->getTimeFrames();
        $deliveryDays = $this->addTimeFrames($deliveryDays, $timeFrames);
        $deliveryDays = $this->sortDeliveryDays($deliveryDays);
        $deliveryDays = $this->addDeliveryDate($deliveryDays);
        $deliveryDays = $this->removeEmptyDays($deliveryDays);
        $deliveryDays = $this->checkCutOffTime($deliveryDays);

        return $this->jsonResponse($deliveryDays);
    }

    /**
     * @return array|bool
     */
    public function getDeliveryDays()
    {
        $deliveryDays = $this->carrier->getDeliveryDays();
        $deliveryDays = explode(',', $deliveryDays);
        $deliveryDays = array_map(function ($key) {
            return [
                'day' => $key
            ];
        }, $deliveryDays);

        return $deliveryDays;
    }

    /**
     * @return mixed
     */
    public function getTimeFrames()
    {
        $timeFrames = $this->carrier->getTimeframes();
        return json_decode($timeFrames);
    }

    /**
     * @param $deliveryDays
     * @param $timeFrames
     *
     * @return mixed
     */
    public function addTimeFrames($deliveryDays, $timeFrames)
    {
        foreach ($deliveryDays as &$deliveryDay) {
            foreach ($timeFrames as $key => $timeFrame) {
                if ($timeFrame->timeframe_day == $deliveryDay['day']) {
                    $deliveryDay['timeFrames'][$timeFrame->timeframe_sort_order] = [
                        'earliest_time' => $timeFrame->timeframe_earliest_time,
                        'latest_time' => $timeFrame->timeframe_latest_time,
                        'fee' => $timeFrame->timeframe_fee
                    ];
                    continue;
                }
            }
        }

        foreach ($deliveryDays as &$deliveryDay) {
            if (isset($deliveryDay['timeFrames'])) {
                ksort($deliveryDay['timeFrames'], SORT_NUMERIC);
                $deliveryDay['timeFrames'] = array_values($deliveryDay['timeFrames']);
            }
        }

        return $deliveryDays;
    }

    /**
     * @param $deliveryDays
     *
     * @return array
     * @throws \Exception
     */
    public function sortDeliveryDays($deliveryDays)
    {
        $firstDeliveryDay = new \DateTime('tomorrow');
        $firstDeliveryDay = $firstDeliveryDay->format('l');
        $firstDeliveryDay = strtolower($firstDeliveryDay);

        foreach ($deliveryDays as $key => $val) {
            if ($val['day'] === $firstDeliveryDay) {
                $firstDeliveryDay = $key;
            }
        }

        if ($firstDeliveryDay !== 0) {
            $firstDays = array_slice($deliveryDays, $firstDeliveryDay);
            $lastDays = array_slice($deliveryDays, 0, $firstDeliveryDay);
            $deliveryDays = array_merge($firstDays, $lastDays);
        }

        return $deliveryDays;
    }

    /**
     * @param $deliveryDays
     *
     * @return mixed
     * @throws \Exception
     */
    public function addDeliveryDate($deliveryDays)
    {
        $dateCount = 1;

        foreach ($deliveryDays as &$day) {
            setlocale(LC_ALL, $this->localeResolver->getLocale());
            $date = new \DateTime('today');
            $date->modify('+' . strval($dateCount) . ' day');
            $date = $date->format('j M Y');
            $day['deliveryDate'] = date('j M Y', strtotime($date));

            if (isset($day['timeFrames'])) {
                foreach ($day['timeFrames'] as &$timeFrame) {
                    $dateValue = new \DateTime('today');
                    $dateValue->modify('+' . strval($dateCount) . ' day');
                    $dateValue = $dateValue->format('Y-m-d');
                    $timeFrame['deliveryDateValue'] = $dateValue;
                }
            }

            $dateCount++;
        }

        return $deliveryDays;
    }

    /**
     * @param $deliveryDays
     *
     * @return array
     */
    public function removeEmptyDays($deliveryDays)
    {
        foreach ($deliveryDays as $key => &$deliveryDay) {
            if (!isset($deliveryDay['timeFrames'])) {
                unset($deliveryDays[$key]);
            }
        }

        return  array_values($deliveryDays);
    }

    /**
     * @param $deliveryDays
     *
     * @return mixed
     * @throws \Exception
     */
    public function checkCutOffTime($deliveryDays)
    {
        $cuttOffTime = $this->carrier->getCutOffTime();
        setlocale(LC_ALL, $this->localeResolver->getLocale());
        $dateTime = new \DateTime('tomorrow');
        $date = $dateTime->format('j M Y');
        $firstPossibleDeliveryDay  = date('j M Y', strtotime($date));

        foreach ($deliveryDays as $key => &$day) {
            if ($day['deliveryDate'] === $firstPossibleDeliveryDay
                && date('H:i:s', strtotime('+ 2 hours')) > $cuttOffTime
            ) {
                $day = $this->setNewDeliveryDate($day, $dateTime);
                unset($deliveryDays[$key]);
                array_push($deliveryDays, $day);
            }
        }

        return array_values($deliveryDays);
    }

    /**
     * @param $day
     * @param $dateTime
     *
     * @return mixed
     */
    public function setNewDeliveryDate($day, $dateTime)
    {
        $newDate = $dateTime->modify('+1 week');
        $newDate = $dateTime->format('j M Y');
        $day['deliveryDate'] = date('j M Y', strtotime($newDate));

        foreach ($day['timeFrames'] as &$timeFrame) {
            $newDeliveryDateValue = $dateTime->format('Y-m-d');
            $timeFrame['deliveryDateValue'] = $newDeliveryDateValue;
        }

        return $day;
    }
}

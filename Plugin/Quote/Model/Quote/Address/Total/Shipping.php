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

namespace TIG\RoutiGo\Plugin\Quote\Model\Quote\Address\Total;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface as ShippingAssignmentApi;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total as QuoteAddressTotal;

class Shipping
{
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * Shipping constructor.
     *
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @param                       $subject
     * @param                       $result
     * @param Quote                 $quote
     * @param ShippingAssignmentApi $shippingAssignment
     * @param QuoteAddressTotal     $total
     *
     * @return void|mixed
     */
    // @codingStandardsIgnoreLine
    public function afterCollect($subject, $result, Quote $quote, ShippingAssignmentApi $shippingAssignment, QuoteAddressTotal $total)
    {
        $shipping = $shippingAssignment->getShipping();
        $address  = $shipping->getAddress();
        $rates    = $address->getAllShippingRates();

        if (!$rates) {
            return $result;
        }

        $timeframesFee = $this->getTimeFramesFee($address, $quote);

        if (!$timeframesFee) {
            return $result;
        }

        $rate    = $this->extractRate($shipping->getMethod(), $rates);
        $fee     = $this->calculateFee($rate['price'], $timeframesFee);
        $title   = 'RoutiGo';

        $this->adjustTotals($rate['method_title'], $subject->getCode(), $address, $total, $fee, $title);
    }

    /**
     * @param $address
     * @param $quote
     *
     * @return mixed|null
     */
    private function getTimeframesFee($address, $quote)
    {
        $timeFramesFee = $address->getRoutigoTimeframesFee();

        if (!$timeFramesFee) {
            return null;
        }

        $storeCode      = $quote->getStore()->getCode();
        $currencySymbol = $this->priceCurrency->getCurrencySymbol($storeCode);

        $timeFramesFee = ltrim($timeFramesFee, $currencySymbol);
        $timeFramesFee = str_replace(',', '.', $timeFramesFee);

        return floatval($timeFramesFee);
    }

    /**
     * @param $method
     * @param $rates
     *
     * @return array|null
     */
    private function extractRate($method, $rates)
    {
        if ($method != 'tig_routigo_tig_routigo') {
            return null;
        }

        $rate = array_filter($rates, function (Quote\Address\Rate $rate) use ($method) {
            return $rate->getCode() == $method;
        });

        if (!$rate) {
            return null;
        }

        $rate = reset($rate);

        return $rate->getData();
    }

    /**
     * @param $ratePrice
     * @param $additionalFee
     *
     * @return float|int
     */
    private function calculateFee($ratePrice, $additionalFee)
    {
        if (!$additionalFee) {
            return $ratePrice;
        }

        $fee = $ratePrice + $additionalFee < 0 ? 0 : $ratePrice + $additionalFee;

        return $fee;
    }

    /**
     * @param $name
     * @param $code
     * @param $address
     * @param $total
     * @param $fee
     * @param $description
     */
    private function adjustTotals($name, $code, $address, $total, $fee, $description)
    {
        $total->setTotalAmount($code, $fee);
        $total->setBaseTotalAmount($code, $fee);
        $total->setBaseShippingAmount($fee);
        $total->setShippingAmount($fee);
        $total->setShippingDescription($name . ' - ' . $description);
        $address->setShippingDescription($name . ' - ' . $description);
    }
}

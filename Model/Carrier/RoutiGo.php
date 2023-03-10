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

namespace TIG\RoutiGo\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Shipping\Model\Tracking\Result\Status;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Psr\Log\LoggerInterface;

class RoutiGo extends AbstractCarrier implements CarrierInterface
{
    const TRACK_URL = 'https://tracking.routigo.com/?trackingCode=%s';

    const TIG_ROUTIGO_CARRIER_GROUP = 'tig_routigo';

    const TIG_ROUTIGO_SHIPPING_METHOD = 'tig_routigo';

    const TIG_ROUTIGO_COMPLETE_NAME = self::TIG_ROUTIGO_CARRIER_GROUP . '_' . self::TIG_ROUTIGO_SHIPPING_METHOD;

    /**
     * @var ResultFactory
     */
    private $rateResultFactory;

    /**
     * @var MethodFactory
     */
    private $rateMethodFactory;



    /**
     * @var string $_code
     */
    protected $_code = self::TIG_ROUTIGO_SHIPPING_METHOD;

    /**
     * @var StatusFactory
     */
    private $trackStatusFactory;

    /**
     * RoutiGo constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory         $rateErrorFactory,
        LoggerInterface      $logger,
        ResultFactory        $rateResultFactory,
        MethodFactory        $rateMethodFactory,
        StatusFactory        $trackStatusFactory,
        array                $data = []
    )
    {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->trackStatusFactory = $trackStatusFactory;
    }

    /**
     * @param RateRequest $request
     *
     * @return bool|\Magento\Framework\DataObject|\Magento\Shipping\Model\Rate\Result|null
     */
    public function collectRates(RateRequest $request)
    {
//        if (!$this->getConfigFlag('active')) {
//            return false;
//        }

//        if  ($this->$this->getConfigFlag('specificcountry') !== 'NL') {
//            return false;
//        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();

        $method = $this->getMethod($request);

        $result->append($method);

        return $result;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     * @api
     */
    public function getAllowedMethods()
    {
        return [self::TIG_ROUTIGO_SHIPPING_METHOD => $this->getConfigData('name')];
    }

    /**
     * @param $request
     *
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    public function getMethod($request)
    {
        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier(self::TIG_ROUTIGO_SHIPPING_METHOD);
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));

        $amount = $this->getConfigData('price');

        if ($request->getFreeShipping()) {
            $amount = 0;
        }

        $method->setPrice($amount);
        $method->setCost($amount);

        return $method;
    }

    /**
     * Add Tracking option to shipments
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * Get tracking
     *
     * @param string|string[] $trackings
     * @return Result
     */
    public function getTrackingInfo($trackings)
    {
        if (!is_array($trackings)) {
            $trackings = [$trackings];
        }
        /**
         * @var Status $trackStatus
         */
        $trackStatus = $this->trackStatusFactory->create();
        foreach ($trackings as $trackingId) {
            $trackStatus->setCarrier(self::TIG_ROUTIGO_SHIPPING_METHOD);
            $trackStatus->setCarrierTitle($this->getConfigData('title'));
            $trackStatus->setUrl(sprintf(self::TRACK_URL, $trackingId));
            $trackStatus->setTracking($trackingId);
            break;
        }

        return $trackStatus;
    }
}

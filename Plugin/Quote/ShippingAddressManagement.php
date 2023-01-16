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

namespace TIG\RoutiGo\Plugin\Quote;

use Magento\Quote\Model\ShippingAddressManagement as QuoteShippingAddressManagement;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;

class ShippingAddressManagement
{
    /** @var CartRepositoryInterface $quoteRepository */
    private $quoteRepository;

    /**
     * ShippingAddressManagement constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param QuoteShippingAddressManagement $subject
     * @param                                $cartId
     * @param AddressInterface|null          $address
     *
     * @return array|void
     */
    // @codingStandardsIgnoreLine
    public function beforeAssign(QuoteShippingAddressManagement $subject, $cartId, AddressInterface $address = null)
    {
        $result = [$cartId, $address];

        if (!$address) {
            return $result;
        }

        $extensionAttributes = $address->getExtensionAttributes();

        if (!$extensionAttributes
            || !$extensionAttributes->getRoutigoTimeframesFee()
            || !$extensionAttributes->getRoutigoDeliveryDate()
            || !$extensionAttributes->getRoutigoVisitAfter()
            || !$extensionAttributes->getRoutigoVisitBefore()
        ) {
            return $result;
        }

        $timeframesFee = $extensionAttributes->getRoutigoTimeframesFee();
        $deliveryDate  = $extensionAttributes->getRoutigoDeliveryDate();
        $visitAfter = $extensionAttributes->getRoutigoVisitAfter();
        $visitBefore = $extensionAttributes->getRoutigoVisitBefore();

        $address->setRoutigoTimeframesFee($timeframesFee);
        $address->setRoutigoDeliveryDate($deliveryDate);
        $address->setRoutigoVisitAfter($visitAfter);
        $address->setRoutigoVisitBefore($visitBefore);
    }
}

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
/*global alert*/
define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'uiRegistry',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
], function (
    $,
    quote,
    Registry,
    modal,
    __
) {
    'use strict';
    
    return function (Component) {
        return Component.extend({
            validateShippingInformation: function () {
                
                var originalResult = this._super(),
                    shippingAddress = quote.shippingAddress(),
                    timeFramesFee   = $("input[name='routigo_delivery_option']:checked").val(),
                    labels          = $("input[name='routigo_delivery_option']:checked").prop('labels'),
                    deliveryDate    = $(labels).attr('deliveryDate');
                
                if (shippingAddress.extension_attributes === undefined) {
                    shippingAddress.extension_attributes = {};
                }
                
                shippingAddress.extension_attributes.routigo_timeframes_fee = timeFramesFee;
                shippingAddress.extension_attributes.routigo_delivery_date  = deliveryDate;
                
                return originalResult;
            }
        });
    };
});

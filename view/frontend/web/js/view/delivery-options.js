/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..
 *  ..:
 *  :
 *  ....::
 *   ::::
 *  :::
 *  :
 *  :
 *   ::
 *   ::::
 *  :::
 *  :
 *  '''
 * ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE
 * OF
 * LICENSE
 *
 * This
 * source
 * file
 * is
 * subject
 * to
 * the
 * Creative
 * Commons
 * License.
 * It is available through the world-wide-web at this URL: http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US If you are unable to obtain it through the world-wide-web, please send an email to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do
 * not
 * edit
 * or
 * add
 * to
 * this
 * file
 * if
 * you
 * wish to upgrade this module to newer versions in the future. If you wish to customize this module for your needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright
 *     (c)
 *     Total
 *     Internet
 *     Group
 *     B.V.
 *     https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
/* jshint esversion: 6 */
define([
    'jquery',
    'uiComponent',
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils'
], function (
    $,
    Component,
    ko,
    quote,
    priceUtils,
) {
    'use strict';
    
    return Component.extend({
        defaults: {
            template: 'TIG_Routigo/delivery/options',
            postcode: null,
            country: null,
            deliveryDays: ko.observableArray(),
        },
        
        initObservable: function () {
            this.selectedMethod = ko.computed(function () {
                var method = quote.shippingMethod();
                var selectedMethod = method != null ? method.carrier_code + '_' + method.method_code : null;
                var price = priceUtils.formatPrice(5, quote.getPriceFormat());
                return selectedMethod;
            }, this);
    
            this.getDeliveryDays();
            
            this._super().observe([
                'deliveryDays',
            ]);
            
            return this;
        },
    
        /**
         * Retrieve
         * Delivery
         * Days.
         */
        getDeliveryDays: function () {
            $.ajax({
                method    : 'GET',
                url       : '/routigo/deliveryoptions/deliverydays',
                type      : 'jsonp',
                showLoader: true,
            }).done(function (data) {
                this.deliveryDays(data);
                data.forEach(function (day) {
                    day.timeFrames.forEach(function(timeframe) {
                        if (timeframe.fee) {
                            timeframe.fee = priceUtils.formatPrice(timeframe.fee, quote.getPriceFormat());
                        }
                    })
                })
            }.bind(this));
        },
    });
    
});

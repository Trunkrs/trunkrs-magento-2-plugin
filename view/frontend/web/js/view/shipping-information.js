define([
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/sidebar',
    'Magento_Catalog/js/price-utils',
    'mage/translate'
], function ($, Component, quote, stepNavigator, sidebarModel, priceUtils, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Trunkrs_Carrier/shipping-information'
        },

        /**
         * @return {Boolean}
         */
        isVisible: function () {
            return !quote.isVirtual() && stepNavigator.isProcessed('shipping');
        },

        /**
         * @return {String}
         */
        getShippingMethodTitle: function () {
            var shippingMethod = quote.shippingMethod();
            return shippingMethod ? shippingMethod['method_title'] === 'Trunkrs' ? shippingMethod['method_title'] + ' - ' + this.getShippingPrice() :
                shippingMethod['carrier_title'] + ' - ' + shippingMethod['method_title'] : '';
        },

        /**
         * @return {String}
         */
        getTrunkrsDeliveryTimeslot: function () {
            var shippingMethod = quote.shippingMethod().method_code+'_'+quote.shippingMethod().carrier_code;
            var selectedTimeslot = jQuery('[name="trunkrs_shipping_field[trunkrs_delivery_date]"] option:selected').text();
            if(shippingMethod === "trunkrsShipping_trunkrsShipping") {
                return $t('Delivery date:') + ' ' + selectedTimeslot;
            }
            return '';
        },

        /**
         * @return {String}
         */
        getShippingPrice: function () {
            var price;
            price = quote.totals()['shipping_amount'];

            return priceUtils.formatPrice(price, quote.getPriceFormat());
        },

        /**
         * Back step.
         */
        back: function () {
            sidebarModel.hide();
            stepNavigator.navigateTo('shipping');
        },

        /**
         * Back to shipping method.
         */
        backToShippingMethod: function () {
            sidebarModel.hide();
            stepNavigator.navigateTo('shipping', 'opc-shipping_method');
        }
    });
});

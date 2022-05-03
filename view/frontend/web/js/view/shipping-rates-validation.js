define([
    'uiComponent',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-rates-validation-rules',
    '../model/shipping-rates-validator',
    '../model/shipping-rates-validation-rules'
], function (
    Component,
    defaultShippingRatesValidator,
    defaultShippingRatesValidationRules,
    trunkrsShippingShippingRatesValidator,
    trunkrsShippingShippingRatesValidationRules
) {
    'use strict';

    defaultShippingRatesValidator.registerValidator('trunkrsShipping', trunkrsShippingShippingRatesValidator);
    defaultShippingRatesValidationRules.registerRules('trunkrsShipping', trunkrsShippingShippingRatesValidationRules);

    return Component;
});

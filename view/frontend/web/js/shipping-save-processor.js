
define(
   [
       'ko',
       'Magento_Checkout/js/model/quote',
       'Magento_Checkout/js/model/resource-url-manager',
       'mage/storage',
       'Magento_Checkout/js/model/payment-service',
       'Magento_Checkout/js/model/payment/method-converter',
       'Magento_Checkout/js/model/error-processor',
       'Magento_Checkout/js/model/full-screen-loader',
       'Magento_Checkout/js/action/select-billing-address'
   ],
   function (
       ko,
       quote,
       resourceUrlManager,
       storage,
       paymentService,
       methodConverter,
       errorProcessor,
       fullScreenLoader,
       selectBillingAddressAction
   ) {
       'use strict';

       return {
           saveShippingInformation: function () {
               var payload;

               var shippingMethod = quote.shippingMethod().method_code+'_'+quote.shippingMethod().carrier_code;

               var trunkrs_delivery_date = null;

               if(shippingMethod === "trunkrsShipping_trunkrsShipping") {
                   trunkrs_delivery_date = jQuery('#trunkrs-delivery-options option:selected').val();
               }

               if (!quote.billingAddress()) {
                   selectBillingAddressAction(quote.shippingAddress());
               }
               payload = {
                   addressInformation: {
                       shipping_address: quote.shippingAddress(),
                       billing_address: quote.billingAddress(),
                       shipping_method_code: quote.shippingMethod().method_code,
                       shipping_carrier_code: quote.shippingMethod().carrier_code,
                       extension_attributes: {
                           trunkrs_delivery_date : trunkrs_delivery_date
                       }
                   }
               };
               fullScreenLoader.startLoader();

               return storage.post(
                   resourceUrlManager.getUrlForSetShippingInformation(quote),
                   JSON.stringify(payload)
               ).done(
                   function (response) {
                       quote.setTotals(response.totals);
                       paymentService.setPaymentMethods(methodConverter(response.payment_methods));
                       fullScreenLoader.stopLoader();
                   }
               ).fail(
                   function (response) {
                       errorProcessor.process(response);
                       fullScreenLoader.stopLoader();
                   }
               );
           }
       };
   }
);

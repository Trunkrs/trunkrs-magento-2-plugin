const config = {
   config: {
       mixins: {
           'Magento_Checkout/js/view/shipping': {
               'Trunkrs_Carrier/js/view/shipping': true
           }
       }
   },

   "map": {
       "*": {
           "Magento_Checkout/js/model/shipping-save-processor/default" : "Trunkrs_Carrier/js/shipping-save-processor",
           "Magento_Checkout/js/view/shipping-information" : "Trunkrs_Carrier/js/view/shipping-information",
           "Magento_Checkout/js/view/shipping-information/list" : "Trunkrs_Carrier/js/view/shipping-information/list"
       }
   }
};

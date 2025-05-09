var config = {
    config: {
        mixins: {
            'Adyen_Payment/js/view/payment/method-renderer/adyen-cc-method': {
                'Forter_Forter/js/model/adyen-cc-method-mixin': true
            },
            'Adyen_Payment/js/view/payment/method-renderer/adyen-oneclick-method': {
                'Forter_Forter/js/model/adyen-oneclick-method-mixin': true
            },
            'Adyen_Payment/js/view/payment/method-renderer/vault': {
                'Forter_Forter/js/model/vault-mixin': true
            },
            'PayPal_Braintree/js/view/payment/method-renderer/cc-form': {
                'Forter_Forter/js/model/paypal-braintree-cc-mixin': true
            },
             'Adyen_Payment/js/view/payment/method-renderer/adyen-cc-vault-method': {
                'Forter_Forter/js/model/adyen-cc-vault-method-mixin': true
            },
        }
    }
};

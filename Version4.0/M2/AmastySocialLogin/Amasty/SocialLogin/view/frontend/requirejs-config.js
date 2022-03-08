var config = {
    config: {
        mixins: {
            'mage/validation': {
                'Amasty_Xnotif/js/validation-mixin': false,
                'Amasty_SocialLogin/js/validation-mixin': true
            },
            'Magento_Customer/js/model/authentication-popup': {
                'Amasty_SocialLogin/js/authentication-popup-mixin': true
            },
            'Magento_Checkout/js/view/progress-bar': {
                'Amasty_SocialLogin/js/mixins/magento_checkout/view/progress-bar': true
            },
            'Amasty_Faq/js/rating/yes-no-voting': {
                'Amasty_SocialLogin/js/mixins/amfaq/rating/amsl-yes-no-voting': true
            }
        }
    }
};

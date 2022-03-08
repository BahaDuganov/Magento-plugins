/**
 * Amasty Social login mixin for the Magento Checkout Progress Bar Component
 *
 * @desc Check hash and replace it for reloading page after success login
 */

define(function () {
    'use strict';

    var mixin = {
        /**
         * @inheritDoc
         */
        initialize: function () {
            this._cutFacebookHash();

            this._super();
        },

        /**
         * Remove facebook hash from window location hash
         *
         * @returns {void}
         */
        _cutFacebookHash: function () {
            var facebookSessionHash = '#_=_';

            if (window.location.hash === facebookSessionHash) {
                window.location.hash = '';
            }
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});

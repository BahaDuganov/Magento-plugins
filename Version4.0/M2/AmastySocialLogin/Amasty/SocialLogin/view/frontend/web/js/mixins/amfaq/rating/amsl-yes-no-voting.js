/**
 * Amasty Social login mixin for the Amasty FAQ module
 */

define([
    'jquery',
    'underscore'
], function ($, _) {
    'use strict';

    var mixin = {
        amslDefaults: {
            widget: {
                name: 'mage-amLoginPopup'
            },
            selectors: {
                popup: '[data-am-js="am-login-popup"]',
                form: '.form-login'
            },
            classes: {
                info: '-info'
            }
        },

        /**
         * Override
         *
         * @param {Object} response
         * @returns {this} Chainable
         */
        onVoteError: function (response) {
            var $popup = $(this.amslDefaults.selectors.popup),
                $form = $popup.find(this.amslDefaults.selectors.form),
                widget = $popup.data(this.amslDefaults.widget.name);

            if (_.isUndefined(widget)) {
                this.messageContainer.addErrorMessage({ message: response.responseJSON.result.message });

                return this;
            }

            if (_.isObject(widget)) {
                widget
                    .openPopup(0)
                    .showDefaultMessage(
                        $form,
                        [ { text: response.responseJSON.result.message } ],
                        this.amslDefaults.classes.info
                    );
            }

            return this;
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});

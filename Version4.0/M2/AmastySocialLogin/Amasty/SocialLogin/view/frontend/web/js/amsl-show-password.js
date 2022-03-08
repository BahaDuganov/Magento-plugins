/*
* Extend Magento ShowPassword component
*/

define([
    'showPassword'
], function (ShowPassword) {
    'use strict';

    return ShowPassword.extend({
        defaults: {
            template: 'Amasty_SocialLogin/show-password'
        }
    });
});

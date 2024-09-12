define([
    'jquery'
], function ($) {
    'use strict';
    return function (target) {
        $.validator.addMethod(
            'validate-otp-length',
            function (value) {
                return (value >= 4 && value <= 6);
            },
            $.mage.__('Please enter otp length minimun 4 & maximum 6 digit number.')
        );
        return target;
    };
});
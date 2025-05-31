/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */

define([
    'ko',
    'underscore',
    'Magento_Customer/js/customer-data'
], function (ko, _, customerData) {
    'use strict';

    var isIdentified = false;

    return function () {
        var customer = customerData.get('customer'),
            isLoggedIn = customerData.get('mage-customer-login');

        if (typeof _nzm === 'undefined') {
            return;
        }

        if (!isLoggedIn()) {
            return;
        }

        customer.subscribe(function (data) {
            if (!_.isEmpty(data['email']) && !isIdentified && !_nzm.isIdentified()) {
                isIdentified = true;

                _nzm.identify({
                    email: data['email'],
                    first_name: data['firstname'],
                    last_name: data['lastname']
                });
            }
        });
    }
});

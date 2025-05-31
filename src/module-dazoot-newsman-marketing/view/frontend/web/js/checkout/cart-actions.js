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

    return function () {
        var marketing = customerData.get('dazoot-marketing');

        marketing.subscribe(function (data) {
            var localData = data,
                isChange = false;

            if (!_.isEmpty(data['add_cart'])) {
                localData = _.omit(data, 'add_cart');
                isChange = true;
            }

            if (!_.isEmpty(data['remove_cart'])) {
                localData = _.omit(data, 'remove_cart');
                isChange = true;
            }

            if (!_.isEmpty(data['add_cart']) && (typeof _nzm !== 'undefined')) {
                _.each(data['add_cart'], function (product) {
                    _nzm.run('ec:addProduct', product);
                    _nzm.run('ec:setAction', 'add');
                    _nzm.run('send', 'event', 'UX', 'click', 'add to cart');
                });
            }

            if (!_.isEmpty(data['remove_cart']) && (typeof _nzm !== 'undefined')) {
                _.each(data['remove_cart'], function (product) {
                    _nzm.run('ec:addProduct', product);
                    _nzm.run('ec:setAction', 'remove');
                    _nzm.run('send', 'event', 'UX', 'click', 'remove from cart');
                });
            }

            if (isChange) {
                // Delete from local storage add_cart/remove_cart
                customerData.set('dazoot-marketing', localData);
            }
        });
    }
});

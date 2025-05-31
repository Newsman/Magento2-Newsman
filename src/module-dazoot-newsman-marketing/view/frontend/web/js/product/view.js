/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */

define([
    'underscore'
], function (_) {
    'use strict';

    /**
     * @param {Object} config
     */
    return function (config) {
        if (typeof _nzm === 'undefined') {
            return;
        }

        if (!(config && config.product && !_.isEmpty(config.product))) {
            return;
        }

        _nzm.run('ec:addProduct', config.product);
        _nzm.run('ec:setAction', 'detail');
        _nzm.run('send', 'pageview');
    }
});

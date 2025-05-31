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

        if (!(config && config.list && !_.isEmpty(config.list))) {
            return;
        }

        _.each(config.list, function (product) {
            _nzm.run('ec:addImpression', product);
        })

        _nzm.run('send', 'pageview');
    }
});

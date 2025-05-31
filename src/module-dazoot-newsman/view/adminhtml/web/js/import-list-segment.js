/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */

define([
    'jquery',
    'underscore',
    'Magento_Ui/js/modal/alert',
    'jquery/ui'
], function ($, _, alert) {
    'use strict';

    $.widget('mage.newsmanImportListSegment', {
        options: {
            url: '',
            elementId: '',
            successText: '',
            failedText: '',
            fieldMapping: ''
        },

        /**
         * Bind handlers to events
         */
        _create: function () {
            this._on({
                'click': $.proxy(this._connect, this)
            });
        },

        /**
         * @private
         */
        _connect: function () {
            var result = this.options.failedText,
                element =  $('#' + this.options.elementId),
                self = this,
                params = {},
                msg = '',
                fieldToCheck = this.options.fieldToCheck || 'success';

            element.removeClass('success').addClass('fail');
            $.each(JSON.parse(this.options.fieldMapping), function (key, el) {
                params[key] = $('#' + el).val();
            });

            $.ajax({
                url: this.options.url,
                showLoader: true,
                data: params,
                headers: this.options.headers || {}
            }).done(function (response) {
                var userEl,
                    listsEl = [],
                    segmentsEl = [];

                userEl = $('#' + self.options.userElementId);

                if (response[fieldToCheck]) {
                    element.removeClass('fail').addClass('success');
                    result = self.options.successText;

                    listsEl = $('#' + self.options.listElementId).html('');
                    listsEl.append(
                        $("<option></option>").val('')
                            .text(self.options.listEmptyLabel)
                    );
                    if (!_.isEmpty(response.lists)) {
                        _.each(response.lists, function (item) {
                            listsEl.append(
                                $("<option></option>").val(item['list_id'])
                                    .text(
                                        '[' + userEl.val() + '] ' + item['list_name'] +
                                        ' (' + item['list_id'] + ')'
                                    )
                            );
                        });
                    }

                    segmentsEl = $('#' + self.options.segmentElementId).html('');
                    segmentsEl.append(
                        $("<option></option>").val('')
                            .text(self.options.segmentEmptyLabel)
                    );
                    if (!_.isEmpty(response.segments)) {
                        _.each(response.segments, function (userSegments, aUserId) {
                            if (!_.isEmpty(userSegments)) {
                                _.each(userSegments, function (listSegments, aListId) {
                                    if (!_.isEmpty(listSegments)) {
                                        _.each(listSegments, function (item) {
                                            segmentsEl.append(
                                                $("<option></option>").val(item['segment_id'])
                                                    .text(
                                                        '{' + aListId + '} ' + item['segment_name'] +
                                                        ' (' + item['segment_id'] + ')'
                                                    )
                                            );
                                        });
                                    }
                                });
                            }
                        });
                    }
                } else {
                    msg = response.errorMessage;

                    if (msg) {
                        alert({
                            content: msg
                        });
                    }
                }
            }).always(function () {
                $('#' + self.options.elementId + '_result').text(result);
            });
        }
    });

    return $.mage.newsmanImportListSegment;
});

/**
 * Copyright © Dazoot Software S.R.L. rights reserved.
 * See LICENSE.txt for license details.
 *
 * @website https://www.newsman.ro/
 */

define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('mage.newsmanSegmentListFilter', {
        options: {
            listElementId: '',
            segmentElementId: ''
        },

        /**
         * Cache the full segment option set and start filtering.
         *
         * @private
         */
        _create: function () {
            this.listEl = document.getElementById(this.options.listElementId);
            this.segmentEl = document.getElementById(this.options.segmentElementId);

            if (!this.listEl || !this.segmentEl) {
                return;
            }

            this._buildCache();
            this._bind();
            this._apply();
        },

        /**
         * Snapshot every segment option, parsing the owning list ID from the
         * leading "{listId}" token in the option label.
         *
         * @private
         */
        _buildCache: function () {
            var self = this;

            this.placeholder = {value: '', text: ''};
            this.items = [];

            $(this.segmentEl).find('option').each(function () {
                var $option = $(this),
                    value = $option.val(),
                    match;

                if (value === '' || value === null || typeof value === 'undefined') {
                    self.placeholder = {value: value, text: $option.text()};
                    return;
                }

                match = /^\s*\{(\d+)\}/.exec($option.text());
                self.items.push({
                    value: value,
                    text: $option.text(),
                    listId: match ? match[1] : null
                });
            });
        },

        /**
         * Re-filter on list change, on inheritance toggle, and when the sync
         * button signals it rebuilt the option lists.
         *
         * @private
         */
        _bind: function () {
            var inherit = document.getElementById(this.options.segmentElementId + '_inherit');

            $(this.listEl).on('change.newsmanSegmentListFilter', $.proxy(this._apply, this));
            $(this.listEl).on(
                'newsmanListsSynced.newsmanSegmentListFilter',
                $.proxy(this._resync, this)
            );

            if (inherit) {
                $(inherit).on('change.newsmanSegmentListFilter', $.proxy(this._apply, this));
            }
        },

        /**
         * After the sync button replaced the options, re-snapshot the fresh
         * full set and filter it again.
         *
         * @private
         */
        _resync: function () {
            this._buildCache();
            this._apply();
        },

        /**
         * Rebuild the segment options so only segments of the selected list
         * remain. Resets the selection when it no longer matches the list.
         *
         * @private
         */
        _apply: function () {
            var selectedList,
                previous,
                $segment,
                keepPrevious;

            // Leave the field untouched while it inherits a parent scope value.
            if (this.segmentEl.disabled) {
                return;
            }

            selectedList = String(this.listEl.value == null ? '' : this.listEl.value);
            previous = String(this.segmentEl.value == null ? '' : this.segmentEl.value);
            $segment = $(this.segmentEl);
            keepPrevious = previous === String(this.placeholder.value);

            $segment.empty();
            $segment.append(
                $('<option></option>').val(this.placeholder.value).text(this.placeholder.text)
            );

            if (selectedList !== '') {
                $.each(this.items, function (index, item) {
                    if (item.listId === null || String(item.listId) === selectedList) {
                        $segment.append($('<option></option>').val(item.value).text(item.text));

                        if (String(item.value) === previous) {
                            keepPrevious = true;
                        }
                    }
                });
            }

            if (keepPrevious) {
                $segment.val(previous);
            } else {
                $segment.val(this.placeholder.value);

                if (!this.segmentEl.disabled) {
                    $segment.trigger('change');
                }
            }
        }
    });

    return $.mage.newsmanSegmentListFilter;
});

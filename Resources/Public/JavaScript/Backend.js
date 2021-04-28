/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 Sjoerd Zonneveld  <typo3@bitpatroon.nl>
 *  Date: 4-4-2019 15:16
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

define(['jquery'], function ($) {

    var FormEngine = {
        legacyFieldChangedCb: function () {
            !$.isFunction(TYPO3.settings.FormEngine.legacyFieldChangedCb) || TYPO3.settings.FormEngine.legacyFieldChangedCb();
        }
    };

    /**
     * Initialises the buttons
     * @param {jQuery} $control
     */
    var initAddButtons = function ($control) {
        var $addButton = $control.find('.action-button-add');
        var format = $control.find('.format').html();
        var $startDate = $control.find('input[name*="[tx_exp_gr_st]"]');
        var $endDate = $control.find('input[name*="[tx_exp_gr_ed]"]');

        $addButton.click(function () {
            var $selectedNewValue = $control.find('select[data-name="tx_exp_gr_select"] option:selected');
            var title = $selectedNewValue.html();
            var id = $selectedNewValue.attr('value');
            var startDateTS = $startDate.val();
            var endDateTS = $endDate.val();

            var stop = false;
            if ((startDateTS || null) == null) {
                $('input[data-formengine-input-name="' + $startDate.attr('name') + '"]').css('background-color', 'red');
                stop = true;
            }

            if ((endDateTS || null) == null) {
                $('input[data-formengine-input-name="' + $endDate.attr('name') + '"]').css('background-color', 'red');
                stop = true;
            }

            if (stop) {
                return;
            }
            var option = getSelectedOption(format, startDateTS, endDateTS, title, id);
            $control.find('select[data-name="tx_exp_gr_ui"]').append(option);
            updateHiddenControl($control);
        });
    };

    /**
     * Converts into a date
     * @param {string}  format
     * @param {number|string} fromTS
     * @param {number|string} toTS
     * @param {string} title
     * @param {number} groupId
     * @return {string}
     */
    var getSelectedOption = function (format, fromTS, toTS, title, groupId) {

        let fromDateTime = new Date(fromTS);
        let toDateTime = new Date(toTS);
        let fromTimestamp = convertToLocalTimeStamp(fromDateTime.getTime(), false);
        let toTimestamp = convertToLocalTimeStamp(toDateTime.getTime(), false);
        let value = [groupId, fromTimestamp, toTimestamp].join('|');
        let fromString = formatDate(fromTimestamp, '%H:%i %e-%m-%Y');
        let untilString = formatDate(toTimestamp, '%H:%i %e-%m-%Y');
        let displayTitle = format
            .replace('###TITLE###', title)
            .replace('###FROMSTRING###', fromString)
            .replace('###TOSTRING###', untilString);
        return '<option value="' + value + '">' + displayTitle + '</option>';
    };

    /**
     * Remove button handler
     * @param {jQuery} $control
     */
    var initRemoveButton = function ($control) {
        var $deleteButton = $control.find('.action-button-delete');
        $deleteButton.attr('href', '');
        $deleteButton.click(function (event) {
            event.preventDefault();
            $control.find('select[data-name="tx_exp_gr_ui"] option:selected').remove();
            updateHiddenControl($control);
        });
    };

    /**
     * @param {jQuery} $control
     */
    var updateHiddenControl = function ($control) {
        var values = [];
        $control.find('select[data-name="tx_exp_gr_ui"] option').each(function () {
            var option = $(this);
            values.push(option.attr('value'));
        });

        $control.find('.hidden-tx_expiringfegroups_groups').val(values.join('*'));
        FormEngine.legacyFieldChangedCb();
    };

    /**
     * @params {number} timeStamp
     * @params {boolean} ms
     * @return {number} the timestamp WITHOUT milliseconds
     */
    var convertToLocalTimeStamp = function (timeStamp, ms) {
        var parsedTS = parseInt(timeStamp);
        if (parsedTS < 1480000000000) {
            parsedTS = parsedTS * 1000;
        }

        var dateInstance = new Date(parsedTS);
        var resultMS = dateInstance.getTime() + dateInstance.getTimezoneOffset() * 60 * 1000;
        if(ms){
            return resultMS;
        }
        return resultMS / 1000;
    };

    /**
     * Converts to JS milliseconds
     * @params {number} timeStamp
     * @return {number} the timestamp WITHOUT milliseconds
     */
    var convertToMS = function (timeStamp) {
        var parsedTS = parseInt(timeStamp);
        if (parsedTS < 1480000000000) {
            return parsedTS * 1000;
        } else {
            return parsedTS;
        }
    };

    /**
     * Formats a date instance
     * @param {number} localTimeStamp
     * @param {string} format
     * @param {boolean} convertToLocalDT
     * @returns {string}
     */
    var formatDate = function (localTimeStamp, format, convertToLocalDT) {
        if (format == null) return '';
        if (localTimeStamp == null) return '';
        localTimeStamp = convertToMS(localTimeStamp);
        // }
        var dateInstance = new Date(localTimeStamp);

        var result = format;
        // day
        result = result.replace('%d', '' + dateInstance.getDate());
        result = result.replace('%e', padLeft(dateInstance.getDate(), 2, '0'));

        // month
        result = result.replace('%m', padLeft(dateInstance.getMonth() + 1, 2, '0'));

        // year
        result = result.replace('%y', '' + dateInstance.getYear());
        result = result.replace('%Y', '' + dateInstance.getFullYear());

        // hour
        result = result.replace('%H', padLeft(dateInstance.getHours(), 2, '0'));
        result = result.replace('%k', '' + dateInstance.getUTCHours());

        // Minutes
        result = result.replace('%i', padLeft(dateInstance.getMinutes(), 2, '0'));
        result = result.replace('%l', '' + dateInstance.getMinutes());

        return result;
    };

    /**
     * Pads at the left with leading zero's
     * @param {string|number} value The value to pad at the left.
     * @param {number} len The length of the string
     * @param {string} token the token to add left
     * @returns {Array|string|Blob|*}
     */
    var padLeft = function (value, len, token) {
        value = value || '';
        if (!len) return value;
        token = token || '0';

        var res = '';
        for (var index = 0; index < len; index++) {
            res += token;
        }

        return (res + value).slice(-1 * len);
    };

    /**
     * @param {jQuery} $control
     */
    var initDateControls = function ($control) {
        var $startDate = $control.find('input[name*="[tx_exp_gr_st]"]');
        var $endDate = $control.find('input[name*="[tx_exp_gr_ed]"]');

        var startDateDisplayControl = $('input[data-formengine-input-name="' + $startDate.attr('name') + '"]');
        var endDateDisplayControl = $('input[data-formengine-input-name="' + $endDate.attr('name') + '"]');
        startDateDisplayControl.focus(function () {
            $(this).css('background-color', '');
        });
        endDateDisplayControl.focus(function () {
            $(this).css('background-color', '');
        });
    };

    $(document).ready(function () {
        var $control = $('.module-tx_expiringfegroups_groups');
        initAddButtons($control);
        initRemoveButton($control);
        initDateControls($control);
    });

});

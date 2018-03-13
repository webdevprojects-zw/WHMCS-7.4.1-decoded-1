/*!
 * WHMCS Dynamic Client Dropdown Library
 *
 * Based upon Selectize.js
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2015
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

$(document).ready(function(){

    var clientSearchSelectize = jQuery(".selectize-client-search").selectize({
        valueField: jQuery(".selectize-client-search").attr('data-value-field'),
        labelField: 'name',
        searchField: ['name', 'email', 'companyname'],
        create: false,
        maxItems: 1,
        preload: 'focus',
        render: {
            item: function(item, escape) {
                if (typeof dropdownSelectClient == "function") {
                    dropdownSelectClient(
                        escape(item.id),
                        escape(item.name) + (item.companyname ? ' (' + escape(item.companyname) + ')' : '') + ' - #' + item.id,
                        escape(item.email)
                    );
                }
                return '<div><span class="name">' + escape(item.name) +
                    (item.companyname ? ' (' + escape(item.companyname) + ')' : '')  +
                    ' - #' + escape(item.id) + '</span></div>';
            },
            option: function(item, escape) {
                return '<div><span class="name">'
                    + escape(item.name) + (item.companyname ? ' (' + escape(item.companyname) + ')' : '') + ' - #' +
                    escape(item.id) + '</span>' +
                    (item.email ? '<span class="email">' + escape(item.email) + '</span>' : '') + '</div>';
            }
        },
        load: function(query, callback) {
            jQuery.ajax({
                url: getClientSearchPostUrl(),
                type: 'POST',
                dataType: 'json',
                data: {
                    dropdownsearchq: query,
                    clientId: currentValue
                },
                error: function() {
                    callback();
                },
                success: function(res) {
                    callback(res);
                }
            });
        },
        onChange: function(value) {
            if (jQuery('#goButton').length) {
                if (value.length && value != currentValue) {
                    jQuery('#goButton').click();
                }
            }
        },
        onFocus: function() {
            currentValue = clientSearchSelectize.getValue();
            clientSearchSelectize.clear();
        },
        onBlur: function()
        {
            if (clientSearchSelectize.getValue() == '') {
                clientSearchSelectize.setValue(currentValue);
            }
        }
    });
    var currentValue = '';

    if (clientSearchSelectize.length) {
        /**
         * selectize assigns any items to an array. In order to be able to run additional
         * functions on this (like auto-submit and clear).
         *
         * @link https://github.com/brianreavis/selectize.js/blob/master/examples/api.html
         */
        clientSearchSelectize = clientSearchSelectize[0].selectize;
    }

});

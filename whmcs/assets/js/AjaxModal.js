/*!
 * WHMCS Ajax Driven Modal Framework
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2016
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
jQuery(document).ready(function(){
    jQuery(document).on('click', '.open-modal', function(e) {
        e.preventDefault();
        var url = jQuery(this).attr('href'),
            modalSize = jQuery(this).data('modal-size'),
            modalClass = jQuery(this).data('modal-class'),
            modalTitle = jQuery(this).data('modal-title'),
            submitId = jQuery(this).data('btn-submit-id'),
            submitLabel = jQuery(this).data('btn-submit-label'),
            hideClose = jQuery(this).data('btn-close-hide'),
            disabled = jQuery(this).attr('disabled'),
            successDataTable = jQuery(this).data('datatable-reload-success');

        if (!disabled) {
            openModal(url, '', modalTitle, modalSize, modalClass, submitLabel, submitId, hideClose, successDataTable);
        }
    });

    // define modal close reset action
    jQuery('#modalAjax').on('hidden.bs.modal', function (e) {
        if (jQuery(this).hasClass('modal-feature-highlights')) {
            var dismissForVersion = jQuery('#cbFeatureHighlightsDismissForVersion').is(':checked');
            jQuery.post(
                'whatsnew.php',
                {
                    dismiss: "1",
                    until_next_update: dismissForVersion ? '1' : '0',
                    token: csrfToken
                }
            );
        }

        jQuery('#modalAjax').find('.modal-body').empty();
        jQuery('#modalAjax').children('div.modal-dialog').removeClass('modal-lg');
        jQuery('#modalAjax').removeClass().addClass('modal whmcs-modal fade');
        jQuery('#modalAjax .modal-title').html('Title');
        jQuery('#modalAjax .modal-submit').html('Submit')
            .removeClass()
            .addClass('btn btn-primary modal-submit')
            .removeAttr('id')
            .removeAttr('disabled');
        jQuery('#modalAjax .loader').show();
    });
});

function openModal(url, postData, modalTitle, modalSize, modalClass, submitLabel, submitId, hideClose, successDataTable) {
    //set the text of the modal title
    jQuery('#modalAjax .modal-title').html(modalTitle);

    // set the modal size via a class attribute
    if (modalSize) {
        jQuery('#modalAjax').children('div[class="modal-dialog"]').addClass(modalSize);
    }
    // set the modal class
    if (modalClass) {
        jQuery('#modalAjax').addClass(modalClass);
    }

    // set the modal class
    if (modalClass) {
        jQuery('#modalAjax').addClass(modalClass);
    }

    // set the text of the submit button
    if(!submitLabel){
       jQuery('#modalAjax .modal-submit').hide();
    } else {
        jQuery('#modalAjax .modal-submit').show().html(submitLabel);
        // set the button id so we can target the click function of it.
        if (submitId) {
            jQuery('#modalAjax .modal-submit').attr('id', submitId);
        }
    }

    if (hideClose) {
        jQuery('#modalAjaxClose').hide();
    }

    jQuery('#modalAjax .modal-body').html('');

    jQuery('#modalSkip').hide();
    jQuery('#modalAjax .modal-submit').prop('disabled', true);

    // show modal
    jQuery('#modalAjax').modal('show');

    // fetch modal content
    jQuery.post(url, postData, function(data) {
        updateAjaxModal(data);
    }, 'json').fail(function() {
        jQuery('#modalAjax .modal-body').html('An error occurred while communicating with the server. Please try again.');
        jQuery('#modalAjax .loader').fadeOut();
    });

    //define modal submit button click
    if (submitId) {
        /**
         * Reloading ajax modal multiple times on the same page can add
         * multiple "on" click events which submits the same form over
         * and over.
         * Remove the on click event with "off" to avoid multiple growl
         * and save events being run.
         *
         * @see http://api.jquery.com/off/
         */
        var submitButton = jQuery('#' + submitId);
        submitButton.off('click');
        submitButton.on('click', function() {
            var modalForm = jQuery('#modalAjax').find('form');
            jQuery('#modalAjax .loader').show();
            var modalPost = jQuery.post(
                modalForm.attr('action'),
                modalForm.serialize(),
                function(data) {
                    if (successDataTable) {
                        data.successDataTable = successDataTable;
                    }
                    updateAjaxModal(data);
                },
                'json'
            ).fail(function(xhr) {
                var data = xhr.responseJSON;
                var genericErrorMsg = 'An error occurred while communicating with the server. Please try again.';
                if (data && data.data) {
                    data = data.data;
                    if (data.errorMsg) {
                        jQuery.growl.warning({ title: data.errorMsgTitle, message: data.errorMsg });
                    } else if (data.data.body) {
                        jQuery('#modalAjax .modal-body').html(data.body);
                    } else {
                        jQuery('#modalAjax .modal-body').html(genericErrorMsg);
                    }
                } else {
                    jQuery('#modalAjax .modal-body').html(genericErrorMsg);
                }
                jQuery('#modalAjax .loader').fadeOut();
            });
        })
    }
}

function updateAjaxModal(data) {
    if (data.successDataTable) {
        WHMCS.ui.dataTable.getTableById(data.successDataTable, undefined).ajax.reload();
    }
    if (data.dismiss) {
        dialogClose();
    }
    if (data.successMsg) {
        jQuery.growl.notice({ title: data.successMsgTitle, message: data.successMsg });
    }
    if (data.errorMsg) {
        jQuery.growl.warning({ title: data.errorMsgTitle, message: data.errorMsg });
    }
    if (data.title) {
        jQuery('#modalAjax .modal-title').html(data.title);
    }
    if (data.body) {
        jQuery('#modalAjax .modal-body').html(data.body);
    } else {
        if (data.url) {
            jQuery.post(data.url, '', function(data2) {
                jQuery('#modalAjax').find('.modal-body').html(data2.body);
            }, 'json').fail(function() {
                jQuery('#modalAjax').find('.modal-body').html('An error occurred while communicating with the server. Please try again.');
                jQuery('#modalAjax').find('.loader').fadeOut();
            });
        }
    }
    if (data.submitlabel) {
        jQuery('#modalAjax .modal-submit').html(data.submitlabel).show();
        if (data.submitId) {
            jQuery('#modalAjax').find('.modal-submit').attr('id', data.submitId);
        }
    }

    if (data.submitId) {
        /**
         * Reloading ajax modal multiple times on the same page can add
         * multiple "on" click events which submits the same form over
         * and over.
         * Remove the on click event with "off" to avoid multiple growl
         * and save events being run.
         *
         * @see http://api.jquery.com/off/
         */
        var submitButton = jQuery('#' + data.submitId);
        submitButton.off('click');
        submitButton.on('click', function() {
            var modalForm = jQuery('#modalAjax').find('form');
            jQuery('#modalAjax .loader').show();
            var modalPost = jQuery.post(modalForm.attr('action'), modalForm.serialize(),
                function(data) {
                    updateAjaxModal(data);
                }, 'json').fail(function() {
                    jQuery('#modalAjax .modal-body').html('An error occurred while communicating with the server. Please try again.');
                    jQuery('#modalAjax .loader').fadeOut();
                }
            );
        })
    }

    jQuery('#modalAjax .loader').fadeOut();
    jQuery('#modalAjax .modal-submit').removeProp('disabled');
}

// backwards compat for older dialog implementations

function dialogSubmit() {
    jQuery('#modalAjax .modal-submit').prop("disabled", true);
    jQuery('#modalAjax .loader').show();
    jQuery.post('', jQuery('#modalAjax').find('form').serialize(),
        function(data) {
            updateAjaxModal(data);
        }, 'json').fail(function() {
            jQuery('#modalAjax .modal-body').html('An error occurred while communicating with the server. Please try again.');
            jQuery('#modalAjax .loader').fadeOut();
        });
}

function dialogClose() {
    jQuery('#modalAjax').modal('hide');
}

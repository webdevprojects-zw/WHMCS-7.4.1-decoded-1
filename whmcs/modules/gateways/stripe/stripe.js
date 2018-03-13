/*
 * WHMCS Stripe Javascript
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2016
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
jQuery(document).ready(function(){
    var paymentMethod = jQuery('input[name="paymentmethod"]'),
        frm = jQuery('#frmCheckout'),
        newCcForm = jQuery('#frmNewCc'),
        paymentForm = jQuery('#frmPayment'),
        adminCreditCard = jQuery('#frmCreditCardDetails');
    if (paymentMethod.length) {
        if (existingToken) {
            jQuery('#creditCardInputFields').remove();
            frm.append("<input type='hidden' name='stripeToken' value='" + existingToken + "' />");
            return '';
        }

        var newOrExisting = jQuery('input[name="ccinfo"]'),
            selectedPaymentMethod = jQuery('input[name="paymentmethod"]:checked').val();

        checkApplePayAvailableForCart();

        if (selectedPaymentMethod === 'stripe') {
            enable_stripe();
            if (newOrExisting.val() === 'useexisting') {
                frm.off('submit', validateStripe);
            }
        }

        paymentMethod.on('ifChecked change', function(){
            selectedPaymentMethod = jQuery(this).val();
            if (selectedPaymentMethod === 'stripe') {
                enable_stripe();
                if (newOrExisting.val() === 'useexisting') {
                    frm.off('submit', validateStripe);
                }
            } else {
                disable_stripe();
            }
        });
        newOrExisting.on('ifChecked change', function() {
            frm.off('submit', validateStripe);
            if (selectedPaymentMethod === 'stripe') {
                if (jQuery(this).val() !== 'useexisting') {
                    frm.on('submit', validateStripe);
                }
            }
        });
    } else if (newCcForm.length) {
        // Remove name from CC Input fields, but add stripe-data
        newCcForm.find('#inputCardType').removeAttr('name').parents('div.form-group').remove();
        newCcForm.find('#inputCardNumber').removeAttr('name').attr('data-stripe', 'number').payment('formatCardNumber');
        newCcForm.find('#inputCardExpiry').removeAttr('name').attr('data-stripe', 'exp_month');
        newCcForm.find('select[name="ccexpiryyear"]').removeAttr('name').attr('data-stripe', 'exp_year');
        newCcForm.find('#inputCardCVV').removeAttr('name').attr('data-stripe', 'cvc').payment('formatCardCVC');

        // get the original submit button out of the way as we need another name='submit' field to click
        // due to Firefox issues
        newCcForm.find('#btnSubmitNewCard').removeAttr('name');

        newCcForm.on('submit', validateNewCcStripe);
    } else if (paymentForm.length) {
        if (jQuery('input[name="ccinfo"]:checked').val() == 'new') {
            enable_payment_stripe();
        } else {
            paymentForm.find('#inputCardCvv').parents('div.form-group').hide('fast');
        }
        jQuery('input[name="ccinfo"]').on('change', function(){
            if (jQuery(this).val() == 'new') {
                enable_payment_stripe();
            } else {
                paymentForm.find('#inputCardCvv').parents('div.form-group').hide('fast');
                paymentForm.off('submit', validatePaymentStripe);
            }
        });
        checkApplePayAvailableForPayment();
    } else if (adminCreditCard.length) {
        adminCreditCard.find('#cctype').removeAttr('name').parents('tr#rowCardType').hide('fast');
        adminCreditCard.find('#inputCardNumber').removeAttr('name').attr('data-stripe', 'number');
        adminCreditCard.find('#inputCardMonth').removeAttr('name').attr('data-stripe', 'exp_month');
        adminCreditCard.find('#inputCardYear').removeAttr('name').attr('data-stripe', 'exp_year');
        adminCreditCard.find('#cardcvv').removeAttr('name').attr('data-stripe', 'cvc');

        // same as above - Firefox issues
        adminCreditCard.find('#btnSaveChanges').removeAttr('name');

        adminCreditCard.on('submit', validateAdminStripe);
    }
});

function validateStripe(event) {
    var paymentMethod = jQuery('input[name="paymentmethod"]:checked'),
        frm = jQuery('#frmCheckout');
    if (paymentMethod.val() != 'stripe') {
        return true;
    }
    event.preventDefault();
    // Disable the submit button to prevent repeated clicks:
    frm.find('#btnCompleteOrder').attr('disabled', 'disabled').addClass('disabled');

    // Request a token from Stripe:
    Stripe.card.createToken(frm, stripeResponseHandler);

    // Prevent the form from being submitted:
    return false;
}

function stripeResponseHandler(status, response) {
    var frm = jQuery('#frmCheckout');
    if (response.error) { // Problem!

        // Show the errors on the form:
        frm.find('.gateway-errors').text(response.error.message).removeClass('hidden');
        scrollToError();
        frm.find('#btnCompleteOrder').removeAttr('disabled').removeClass('disabled'); // Re-enable submission

    } else { // Token was created!
        frm.find('.gateway-errors').text('').addClass('hidden');
        // Insert the token ID into the form so it gets submitted to the server:
        frm.append(jQuery('<input type="hidden" name="stripeToken">').val(response.id));

        // Submit the form:
        frm.off('submit', validateStripe);
        frm.find('#btnCompleteOrder').removeAttr('disabled').removeClass('disabled')
            .click().addClass('disabled').attr('disabled', 'disabled');
    }
}

function validateNewCcStripe(event) {
    var newCcForm = jQuery('#frmNewCc');
    event.preventDefault();
    jQuery('#btnSubmitNewCard').attr('disabled', 'disabled').addClass('disabled');
    Stripe.card.createToken(newCcForm, stripeNewCcResponseHandler);
    return false;
}

function stripeNewCcResponseHandler(status, response) {
    var newCcForm = jQuery('#frmNewCc');
    if (response.error) { // Problem!

        // Show the errors on the form:
        newCcForm.find('.gateway-errors').text(response.error.message).removeClass('hidden');
        scrollToError();
        jQuery('#btnSubmitNewCard').removeAttr('disabled').removeClass('disabled'); // Re-enable submission

    } else { // Token was created!
        newCcForm.find('.gateway-errors').text('').addClass('hidden');
        // Insert the token ID into the form so it gets submitted to the server:
        newCcForm.append(jQuery('<input type="hidden" name="stripeToken">').val(response.id));

        // Submit the form:
        newCcForm.off('submit', validateNewCcStripe);

        // Firefox will be unable to re-enable and click original submit button, so we will inject another one
        newCcForm.append('<input type="submit" id="hiddenSubmit" name="submit" value="Save Changes" style="display: none">');

        jQuery('#hiddenSubmit').click();
    }
}

function enable_stripe() {
    var frm = jQuery('#frmCheckout');
    frm.find('#inputAddress1').attr('data-stripe', 'address_line1');
    frm.find('#inputAddress2').attr('data-stripe', 'address_line2');
    frm.find('#inputState').attr('data-stripe', 'address_state');
    frm.find('#inputCountry').attr('data-stripe', 'address_country');
    frm.find('#inputPostcode').attr('data-stripe', 'address_zip');
    frm.find('#cctype').removeAttr('name');
    frm.find('#inputCardCvvExisting').removeAttr('name');
    frm.find('#inputCardNumber').removeAttr('name').attr('data-stripe', 'number');
    frm.find('#inputCardExpiry').removeAttr('name').attr('data-stripe', 'exp');
    frm.find('#inputCardCVV').removeAttr('name').attr('data-stripe', 'cvc');
    var cardTypeInput = frm.find('#cardType');
    if (cardTypeInput.length) {
        frm.find('#cardType').parents('div.col-sm-6').slideUp('fast', function() {
            frm.find('#inputCardNumber').parents('div.col-sm-6').toggleClass('col-sm-6 col-sm-12');
        });
    } else {
        //legacy template
        frm.find('#cctype').parents('div.new-card-info').slideUp('fast');
        frm.find('#cctype').parents('tr.newccinfo').slideUp('fast');
    }

    frm.on('submit', validateStripe);
}

function disable_stripe() {
    var frm = jQuery('#frmCheckout');

    frm.find('#inputCardCvvExisting').attr('name', 'cccvvexisting');
    frm.find('#inputCardNumber').removeAttr('data-stripe').attr('name', 'ccnumber');
    frm.find('#inputCardExpiry').removeAttr('data-stripe').attr('name', 'ccexpirydate');
    frm.find('#inputCardCVV').removeAttr('data-stripe').attr('name', 'cccvv');
    frm.find('#cctype').attr('name', 'cctype');
    var cardTypeInput = frm.find('#cardType');
    if (cardTypeInput.length) {
        frm.find('#inputCardNumber').parents('div.col-sm-12').toggleClass('col-sm-6 col-sm-12');
        frm.find('#cardType').parents('div.col-sm-6').slideDown('fast');
    } else {
        //legacy template
        frm.find('#cctype').parents('div.new-card-info').slideDown('fast');
        frm.find('#cctype').parents('tr.newccinfo').slideDown('fast');
    }

    frm.off('submit', validateStripe);
}

function enable_payment_stripe() {
    var paymentForm = jQuery('#frmPayment');
    paymentForm.find('#inputAddress1').attr('data-stripe', 'address_line1');
    paymentForm.find('#inputAddress2').attr('data-stripe', 'address_line2');
    paymentForm.find('#inputCity').attr('data-stripe', 'address_city');
    paymentForm.find('#inputState').attr('data-stripe', 'address_state');
    paymentForm.find('#inputPostcode').attr('data-stripe', 'address_zip');
    paymentForm.find('#inputCountry').attr('data-stripe', 'address_country');
    paymentForm.find('#inputPostcode').attr('data-stripe', 'address_zip');
    paymentForm.find('#cctype').removeAttr('name').parents('div.form-group').remove();
    paymentForm.find('#inputCardNumber').removeAttr('name').attr('data-stripe', 'number').payment('formatCardNumber');
    paymentForm.find('#inputCardExpiry').removeAttr('name').attr('data-stripe', 'exp_month');
    paymentForm.find('#inputCardExpiryYear').removeAttr('name').attr('data-stripe', 'exp_year');
    paymentForm.find('#inputCardCvv').removeAttr('name')
        .attr('data-stripe', 'cvc').parents('div.form-group').show('fast').payment('formatCardCVC');
    paymentForm.on('submit', validatePaymentStripe);
}

function validatePaymentStripe(event) {
    var paymentForm = jQuery('#frmPayment');
    event.preventDefault();
    jQuery('#btnSubmit').attr('disabled', 'disabled').addClass('disabled');
    Stripe.card.createToken(paymentForm, stripePaymentResponseHandler);
    return false;
}

function stripePaymentResponseHandler(status, response) {
    var paymentForm = jQuery('#frmPayment');
    if (response.error) { // Problem!

        // Show the errors on the form:
        paymentForm.find('.gateway-errors').text(response.error.message).removeClass('hidden');
        scrollToError();
        jQuery('#btnSubmit').removeAttr('disabled').removeClass('disabled')
            .find('span').toggleClass('hidden'); // Re-enable submission

    } else { // Token was created!
        paymentForm.find('.gateway-errors').text('').addClass('hidden');
        // Insert the token ID into the form so it gets submitted to the server:
        paymentForm.append(jQuery('<input type="hidden" name="stripeToken">').val(response.id));

        // Submit the form:
        paymentForm.off('submit', validatePaymentStripe);
        paymentForm.find('#btnSubmit').removeAttr('disabled').removeClass('disabled')
            .click().addClass('disabled').attr('disabled', 'disabled');
    }
}

function validateAdminStripe(event) {
    var adminCreditCard = jQuery('#frmCreditCardDetails');
    event.preventDefault();
    adminCreditCard.find('#btnSaveChanges').attr('disabled', 'disabled').addClass('disabled');
    Stripe.card.createToken(adminCreditCard, stripeAdminResponseHandler);
    return false;
}

function stripeAdminResponseHandler(status, response) {
    var adminCreditCard = jQuery('#frmCreditCardDetails');
    if (response.error) { // Problem!
        // Show the errors on the form:
        adminCreditCard.find('.gateway-errors').text(response.error.message).removeClass('hidden');
        scrollToError();
        adminCreditCard.find('#btnSaveChanges').removeAttr('disabled').removeClass('disabled'); // Re-enable submission
    } else {
        adminCreditCard.find('.gateway-errors').text('').addClass('hidden');
        // Insert the token ID into the form so it gets submitted to the server:
        adminCreditCard.append(jQuery('<input type="hidden" name="stripeToken">').val(response.id));

        adminCreditCard.off('submit', validateAdminStripe);

        // Firefox will be unable to re-enable and click original submit button, so we will inject another one
        adminCreditCard.append('<input type="submit" id="hiddenSubmit" name="submit" value="Save Changes" style="display: none">');

        jQuery('#hiddenSubmit').click();
    }
}

function checkApplePayAvailableForCart() {
    if (applePay) {
        Stripe.applePay.checkAvailability(
            function(available) {
                if (available) {
                    var frm = jQuery('#frmCheckout'),
                        applePayButton = jQuery('#applePayButton');

                    if (applePayButton.length == 0) {
                        frm.find('div.form-group').find('div:first').after('<br /><button id="applePayButton"></button>');
                        applePayButton = jQuery('#applePayButton');
                    }
                    applePayButton.on('click', beginApplePayForCart);
                }
            }
        );
    }
}

function beginApplePayForCart(event) {
    event.preventDefault();
    jQuery('#frmCheckout').off('submit', validateStripe);
    jQuery('input[name="paymentmethod"]').val('stripe');
    var paymentRequest = {
        countryCode: 'US',
        currencyCode: applePayCurrency,
        total: {
            label: applePayDescription,
            amount: applePayAmountDue
        }
    },
        session = Stripe.applePay.buildSession(
            paymentRequest,
            function(result, completion) {
                completion(ApplePaySession.STATUS_SUCCESS);
                // You can now redirect the user to a receipt page, etc.
                var frm = jQuery('#frmCheckout'),
                    token = result.token.id;
                //frm.attr('action', frm.attr('action') + '&submit=true');
                frm.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
                jQuery('#btnCompleteOrder').removeAttr('disabled').removeClass('disabled')
                    .click().addClass('disabled').attr('disabled', 'disabled');
            }, function(error) {
                jQuery('#btnCompleteOrder').removeAttr('disabled').removeClass('disabled');
                jQuery('.gateway-errors').text(error.message).removeClass('hidden');
                scrollToError();
                jQuery('#frmCheckout').on('submit', validateStripe);
            }
        );

    session.begin();
}

function checkApplePayAvailableForPayment() {
    if (applePay) {
        Stripe.applePay.checkAvailability(
            function(available) {
                if (available) {
                    if (jQuery('#applePayButton').length == 0) {
                        jQuery('#frmPayment').find('div.form-group.cc-details:first')
                            .before('<div class="form-group apple-pay"><button id="applePayButton"></button></div>');
                    }
                    jQuery('#applePayButton').on('click', beginApplePayForPayment);
                }
            }
        );
    }
}

function beginApplePayForPayment(event) {
    event.preventDefault();
    jQuery('#frmPayment').off('submit', validatePaymentStripe);
    var paymentRequest = {
        countryCode: 'US',
        currencyCode: applePayCurrency,
        total: {
            label: applePayDescription,
            amount: applePayAmountDue
        }
    },
        session = Stripe.applePay.buildSession(
            paymentRequest,
            function(result, completion) {
                completion(ApplePaySession.STATUS_SUCCESS);
                // You can now redirect the user to a receipt page, etc.
                var frm = jQuery('#frmPayment'),
                    token = result.token.id;
                frm.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
                jQuery('#btnSubmit').removeAttr('disabled').removeClass('disabled')
                    .click().addClass('disabled').attr('disabled', 'disabled');
            }, function(error) {
                jQuery('#btnSubmit').removeAttr('disabled').removeClass('disabled');
                jQuery('.gateway-errors').text(error.message).removeClass('hidden');
                scrollToError();
                jQuery('#frmPayment').on('submit', validatePaymentStripe);
            }
        );

    session.begin();
}

function scrollToError() {
    jQuery('html, body').animate(
        {
            scrollTop: jQuery('.gateway-errors').offset().top - 50
        },
        500
    );
}

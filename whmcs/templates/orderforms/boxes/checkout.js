function chooseDomainReg(type) {
    jQuery(".domain-option").hide();
    jQuery("#domopt-" + type).hide().removeClass('hidden').fadeIn();
}

function removeItem(type,num) {
    var response = confirm(removeItemText);
    if (response) {
        window.location = 'cart.php?a=remove&r=' + type + '&i=' + num;
    }
}

function showPromoInput() {
    jQuery("#promoAddText").fadeOut('slow', function() {
        jQuery("#promoInput").hide().removeClass('hidden').fadeIn('slow');
    });
}

function showLogin() {
    jQuery("#inputCustType").val('existing');
    jQuery("#signupContainer").fadeOut();
    jQuery("#btnCompleteOrder").attr('formnovalidate', true);
    jQuery("#loginContainer").hide().removeClass('hidden').fadeIn();
}

function showSignup() {
    jQuery("#inputCustType").val('new');
    jQuery("#loginContainer").fadeOut();
    jQuery("#signupContainer").fadeIn();
    jQuery("#btnCompleteOrder").removeAttr('formnovalidate');
}

function domainContactChange() {
    if (jQuery("#inputDomainContact").val() == "addingnew") {
        jQuery("#domainContactContainer").hide().removeClass('hidden').slideDown();
    } else {
        jQuery("#domainContactContainer").slideUp();
    }
}

function showCCForm() {
    jQuery("#ccinputform").hide().removeClass('hidden').slideDown();
}

function hideCCForm() {
    jQuery("#ccinputform").slideUp();
}

function useExistingCC() {
    jQuery(".new-card-info").hide();
}

function enterNewCC() {
    jQuery(".new-card-info").removeClass('hidden').show();
}

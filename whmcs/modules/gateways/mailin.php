<?php

# Main in Payment Gateway Module

if (!defined("WHMCS")) die("This file cannot be accessed directly");

function mailin_config() {

    $configarray = array(
     "FriendlyName" => array(
        "Type" => "System",
        "Value" => "Mail In Payment"
        ),
     "instructions" => array(
        "FriendlyName" => "Bank Transfer Instructions",
        "Type" => "textarea",
        "Rows" => "5",
        "Value" => "Bank Name:\nPayee Name:\nSort Code:\nAccount Number:",
        "Description" => "The instructions you want displaying to customers who choose this payment method - the invoice number will be shown underneath the text entered above",
        ),
    );

    return $configarray;

}

function mailin_link($params) {
    global $_LANG;

    $code = '<p>'.nl2br($params['instructions']).'<br />'.$_LANG['invoicerefnum'].': '.$params['invoiceid'].'</p>';

    return $code;

}
<?php 
define("CLIENTAREA", true);
require("init.php");
require(ROOTDIR . "/includes/ccfunctions.php");
require(ROOTDIR . "/includes/clientfunctions.php");
require(ROOTDIR . "/includes/gatewayfunctions.php");
require(ROOTDIR . "/includes/invoicefunctions.php");
$clientArea = new WHMCS\ClientArea();
$clientArea->initPage();
$clientArea->setPageTitle(Lang::trans("ordercheckout"));
$clientArea->setDisplayTitle(Lang::trans("creditcard"));
$clientArea->setTagLine("");
$clientArea->addToBreadCrumb("#", Lang::trans("ordercheckout"));
$clientArea->setTemplate("creditcard");
$invoiceid = (int) $whmcs->get_req_var("invoiceid");
$userId = (int) WHMCS\Session::get("uid");
if( !$userId || !$invoiceid ) 
{
    redir("", "clientarea.php");
}

$invoice = new WHMCS\Invoice($invoiceid);
if( !$invoice->isAllowed() ) 
{
    redir("", "clientarea.php");
}

$invoiceid = $invoice->getData("invoiceid");
$status = $invoice->getData("status");
$total = $invoice->getData("total");
if( $status != "Unpaid" ) 
{
    redir("", "clientarea.php");
}

$gateways = new WHMCS\Gateways();
$action = $whmcs->get_req_var("action");
$ccinfo = $whmcs->get_req_var("ccinfo");
$cctype = $whmcs->get_req_var("cctype");
$ccnumber = $whmcs->get_req_var("ccnumber");
$ccexpirymonth = $whmcs->get_req_var("ccexpirymonth");
$ccexpiryyear = $whmcs->get_req_var("ccexpiryyear");
$ccstartmonth = $whmcs->get_req_var("ccstartmonth");
$ccstartyear = $whmcs->get_req_var("ccstartyear");
$ccissuenum = $whmcs->get_req_var("ccissuenum");
$nostore = $whmcs->get_req_var("nostore");
$cccvv = $whmcs->get_req_var("cccvv");
$cccvv2 = $whmcs->get_req_var("cccvv2");
$firstname = $whmcs->get_req_var("firstname");
$lastname = $whmcs->get_req_var("lastname");
$address1 = $whmcs->get_req_var("address1");
$address2 = $whmcs->get_req_var("address2");
$city = $whmcs->get_req_var("city");
$state = $whmcs->get_req_var("state");
$postcode = $whmcs->get_req_var("postcode");
$country = $whmcs->get_req_var("country");
$phonenumber = App::formatPostedPhoneNumber();
$userDetailsValidationError = false;
$params = NULL;
$errormessage = false;
$fromorderform = false;
if( WHMCS\Session::get("cartccdetail") ) 
{
    $cartccdetail = unserialize(base64_decode(decrypt(WHMCS\Session::getAndDelete("cartccdetail"))));
    list($cctype, $ccnumber, $ccexpirymonth, $ccexpiryyear, $ccstartmonth, $ccstartyear, $ccissuenum, $cccvv, $nostore) = $cartccdetail;
    $action = "submit";
    if( ccFormatNumbers($ccnumber) ) 
    {
        $ccinfo = "new";
    }

    $fromorderform = true;
}

$gateway = new WHMCS\Module\Gateway();
$gateway->load($invoice->getData("paymentmodule"));
if( $gateway->functionExists("credit_card_input") ) 
{
    if( is_null($params) ) 
    {
        $params = getCCVariables($invoiceid);
    }

    $clientArea->assign("credit_card_input", $gateway->call("credit_card_input", $params));
}

if( $action == "submit" ) 
{
    if( !$fromorderform ) 
    {
        check_token();
    }

    if( $nostore && (!WHMCS\Config\Setting::getValue("CCAllowCustomerDelete") || $gateway->functionExists("storeremote")) ) 
    {
        $nostore = "";
    }

    if( !$fromorderform ) 
    {
        $errormessage = checkDetailsareValid($userId, false, false, false, false);
        if( $errormessage ) 
        {
            $userDetailsValidationError = true;
        }

        if( $gateway->functionExists("cc_validation") ) 
        {
            $params = array(  );
            $params["cardtype"] = $cctype;
            $params["cardnum"] = ccFormatNumbers($ccnumber);
            $params["cardexp"] = ccFormatDate(ccFormatNumbers($ccexpirymonth . $ccexpiryyear));
            $params["cardstart"] = ccFormatDate(ccFormatNumbers($ccstartmonth . $ccstartyear));
            $params["cardissuenum"] = ccFormatNumbers($ccissuenum);
            $errormessage = $gateway->call("cc_validation", $params);
            $params = NULL;
        }
        else
        {
            if( $ccinfo == "new" ) 
            {
                $errormessage .= updateCCDetails("", $cctype, $ccnumber, $cccvv, $ccexpirymonth . $ccexpiryyear, $ccstartmonth . $ccstartyear, $ccissuenum, "", "", $gateway->getLoadedModule());
            }

            if( $cccvv2 ) 
            {
                $cccvv = $cccvv2;
            }

            if( !$cccvv ) 
            {
                $errormessage .= "<li>" . $_LANG["creditcardccvinvalid"];
            }

        }

        if( !$errormessage ) 
        {
            $currentClientsDetails = getClientsDetails($userId);
            $old_firstname = $currentClientsDetails["firstname"];
            $old_lastname = $currentClientsDetails["lastname"];
            $old_address1 = $currentClientsDetails["address1"];
            $old_address2 = $currentClientsDetails["address2"];
            $old_city = $currentClientsDetails["city"];
            $old_state = $currentClientsDetails["state"];
            $old_postcode = $currentClientsDetails["postcode"];
            $old_country = $currentClientsDetails["country"];
            $old_phonenumber = $currentClientsDetails["phonenumberformatted"];
            $email = $currentClientsDetails["email"];
            $billingcid = $currentClientsDetails["billingcid"];
            if( $billingcid ) 
            {
                $table = "tblcontacts";
                $array = array( "firstname" => $firstname, "lastname" => $lastname, "address1" => $address1, "address2" => $address2, "city" => $city, "state" => $state, "postcode" => $postcode, "country" => $country, "phonenumber" => $phonenumber );
                $where = array( "id" => $billingcid, "userid" => $userId );
                update_query($table, $array, $where);
            }
            else
            {
                if( $firstname != $old_firstname || $lastname != $old_lastname || $address1 != $old_address1 || $address2 != $old_address2 || $city != $old_city || $state != $old_state || $postcode != $old_postcode || $country != $old_country || $phonenumber != $old_phonenumber ) 
                {
                    $table = "tblcontacts";
                    $array = array( "userid" => $userId, "firstname" => $firstname, "lastname" => $lastname, "email" => $email, "address1" => $address1, "address2" => $address2, "city" => $city, "state" => $state, "postcode" => $postcode, "country" => $country, "phonenumber" => $phonenumber );
                    $billingcid = insert_query($table, $array);
                    update_query("tblclients", array( "billingcid" => $billingcid ), array( "id" => $userId ));
                }

            }

            if( $ccinfo == "new" ) 
            {
                $errormessage .= updateCCDetails($userId, $cctype, $ccnumber, $cccvv, $ccexpirymonth . $ccexpiryyear, $ccstartmonth . $ccstartyear, $ccissuenum, $nostore, "", $gateway->getLoadedModule());
            }

        }

    }

    if( !$errormessage ) 
    {
        $params = getCCVariables($invoiceid);
        if( $ccinfo == "new" ) 
        {
            $params["cardtype"] = $cctype;
            $params["cardnum"] = ccFormatNumbers($ccnumber);
            $params["cardexp"] = ccFormatDate(ccFormatNumbers($ccexpirymonth . $ccexpiryyear));
            $params["cardstart"] = ccFormatDate(ccFormatNumbers($ccstartmonth . $ccstartyear));
            $params["cardissuenum"] = ccFormatNumbers($ccissuenum);
            $params["gatewayid"] = get_query_val("tblclients", "gatewayid", array( "id" => $userId ));
        }

        if( function_exists($params["paymentmethod"] . "_3dsecure") ) 
        {
            $params["cccvv"] = $cccvv;
            $buttoncode = call_user_func($params["paymentmethod"] . "_3dsecure", $params);
            $buttoncode = str_replace("<form", "<form target=\"3dauth\"", $buttoncode);
            $smartyvalues["code"] = $buttoncode;
            $smartyvalues["width"] = "400";
            $smartyvalues["height"] = "500";
            if( $buttoncode == "success" || $buttoncode == "declined" ) 
            {
                $result = $buttoncode;
            }
            else
            {
                $clientArea->setTemplate("3dsecure");
                $clientArea->output();
                exit();
            }

        }
        else
        {
            $result = captureCCPayment($invoiceid, $cccvv, true);
        }

        if( $params["paymentmethod"] == "offlinecc" ) 
        {
            sendAdminNotification("account", "Offline Credit Card Payment Submitted", "<p>An offline credit card payment has just been submitted.  Details are below:</p><p>Client ID: " . $userId . "<br />Invoice ID: " . $invoiceid . "</p>");
            redir("id=" . $invoiceid . "&offlinepaid=true", "viewinvoice.php");
        }

        if( $result == "success" ) 
        {
            redir("id=" . $invoiceid . "&paymentsuccess=true", "viewinvoice.php");
        }
        else
        {
            $errormessage = "<li>" . $_LANG["creditcarddeclined"];
            $action = "";
            if( $ccinfo == "new" ) 
            {
                updateCCDetails($userId, "", "", "", "", "", "", "", "", $gateway->getLoadedModule());
            }

        }

    }

}

$clientsdetails = getClientsDetails($userId, "billing");
$cardtype = $clientsdetails["cctype"];
$cardlastfour = $clientsdetails["cclastfour"];
if( !$errormessage || $fromorderform ) 
{
    $firstname = $clientsdetails["firstname"];
    $lastname = $clientsdetails["lastname"];
    $email = $clientsdetails["email"];
    $address1 = $clientsdetails["address1"];
    $address2 = $clientsdetails["address2"];
    $city = $clientsdetails["city"];
    $state = $clientsdetails["state"];
    $postcode = $clientsdetails["postcode"];
    $country = $clientsdetails["country"];
    $phonenumber = $clientsdetails["phonenumberformatted"];
}

$invoiceData = $invoice->getOutput();
$existingCard = getCCDetails($userId);
$countryObject = new WHMCS\Utility\Country();
$smartyvalues = array( "companyname" => $clientsdetails["companyname"], "firstname" => $firstname, "lastname" => $lastname, "address1" => $address1, "address2" => $address2, "city" => $city, "state" => $state, "postcode" => $postcode, "country" => $country, "countryname" => $countryObject->getName($country), "countriesdropdown" => getCountriesDropDown($country), "phonenumber" => $phonenumber, "acceptedcctypes" => explode(",", WHMCS\Config\Setting::getValue("AcceptedCardTypes")), "cardOnFile" => 0 < strlen($existingCard["cardlastfour"]), "addingNewCard" => $ccinfo == "new" || 0 >= strlen($existingCard["cardlastfour"]), "ccinfo" => $ccinfo, "cardtype" => $existingCard["cardtype"], "cardnum" => $existingCard["cardlastfour"], "existingCardType" => $existingCard["cardtype"], "existingCardLastFour" => $existingCard["cardlastfour"], "existingCardExpiryDate" => $existingCard["expdate"], "existingCardStartDate" => $existingCard["startdate"], "existingCardIssueNum" => $existingCard["issuenumber"], "cctype" => $cctype, "ccnumber" => $ccnumber, "ccexpirymonth" => $ccexpirymonth, "ccexpiryyear" => $ccexpiryyear, "ccstartmonth" => $ccstartmonth, "ccstartyear" => $ccstartyear, "ccissuenum" => $ccissuenum, "cccvv" => $cccvv, "errormessage" => $errormessage, "invoiceid" => $invoiceid, "total" => $invoiceData["total"], "balance" => $invoiceData["balance"], "showccissuestart" => WHMCS\Config\Setting::getValue("ShowCCIssueStart"), "shownostore" => WHMCS\Config\Setting::getValue("CCAllowCustomerDelete") && !$gateway->functionExists("storeremote"), "invoice" => $invoiceData, "invoiceitems" => $invoice->getLineItems(), "userDetailsValidationError" => $userDetailsValidationError );
$smartyvalues["months"] = $gateways->getCCDateMonths();
$smartyvalues["startyears"] = $gateways->getCCStartDateYears();
$smartyvalues["years"] = $gateways->getCCExpiryDateYears();
$smartyvalues["expiryyears"] = $smartyvalues["years"];
if( is_null($params) ) 
{
    $params = getCCVariables($invoiceid);
}

$smartyvalues["remotecode"] = "";
if( function_exists($params["paymentmethod"] . "_remoteinput") ) 
{
    $buttoncode = call_user_func($params["paymentmethod"] . "_remoteinput", $params);
    $buttoncode = str_replace("<form", "<form target=\"3dauth\"", $buttoncode);
    $smartyvalues["remotecode"] = $buttoncode;
}
else
{
    if( function_exists($params["paymentmethod"] . "_remoteInputWithTemplate") ) 
    {
        $templatefile = DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "gateways" . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . $params["paymentmethod"] . DIRECTORY_SEPARATOR . "creditcard.tpl";
        $clientArea->setTemplate($templatefile);
        $variablesToAssign = call_user_func($params["paymentmethod"] . "_remoteInputWithTemplate", $params);
        foreach( $variablesToAssign as $variable => $value ) 
        {
            $smartyvalues[$variable] = $value;
        }
    }

}

$clientArea->addOutputHookFunction("ClientAreaPageCreditCardCheckout");
$clientArea->output();


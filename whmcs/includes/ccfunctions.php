<?php 
function updateCCDetails($userid, $cardtype, $cardnum, $cardcvv, $cardexp, $cardstart, $cardissue, $noremotestore = "", $fullclear = "", $paymentMethod = "")
{
    global $_LANG;
    global $cc_encryption_hash;
    $gatewayid = get_query_val("tblclients", "gatewayid", array( "id" => $userid ));
    if( $fullclear ) 
    {
        update_query("tblclients", array( "cardtype" => "", "cardlastfour" => "", "cardnum" => "", "expdate" => "", "startdate" => "", "issuenumber" => "", "gatewayid" => "" ), array( "id" => $userid ));
    }

    $cardnum = ccFormatNumbers($cardnum);
    $cardexp = ccFormatNumbers($cardexp);
    $cardstart = ccFormatNumbers($cardstart);
    $cardissue = ccFormatNumbers($cardissue);
    $cardexp = ccFormatDate($cardexp);
    $cardstart = ccFormatDate($cardstart);
    $cardcvv = ccFormatNumbers($cardcvv);
    if( $cardtype ) 
    {
        $errormessage = checkCreditCard($cardnum, $cardtype);
        if( !$cardexp || strlen($cardexp) != 4 ) 
        {
            $errormessage .= "<li>" . $_LANG["creditcardenterexpirydate"];
        }
        else
        {
            if( (int) ("20" . substr($cardexp, 2) . substr($cardexp, 0, 2)) < (int) date("Ym") ) 
            {
                $errormessage .= "<li>" . $_LANG["creditcardexpirydateinvalid"];
            }

        }

    }

    if( $errormessage ) 
    {
        return $errormessage;
    }

    if( !$userid ) 
    {
        return "";
    }

    if( $noremotestore ) 
    {
        return "";
    }

    $remotestored = false;
    $ccGateways = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("setting", "type")->whereIn("value", array( "OfflineCC", "CC" ))->pluck("gateway");
    if( $paymentMethod ) 
    {
        $paymentMethod = " AND `gateway` = '" . $paymentMethod . "'";
    }

    $result = select_query("tblpaymentgateways", "gateway,(SELECT id FROM tblinvoices WHERE paymentmethod=gateway AND userid='" . (int) $userid . "' ORDER BY id DESC LIMIT 0,1) AS invoiceid", "setting='name'" . $paymentMethod, "order");
    while( $data = mysql_fetch_array($result) ) 
    {
        $gateway = $data["gateway"];
        if( !$gateway ) 
        {
            $gateway = getClientsPaymentMethod($userid);
        }

        if( !in_array($gateway, $ccGateways) ) 
        {
            continue;
        }

        if( !isValidforPath($gateway) ) 
        {
            exit( "Invalid Gateway Module Name" );
        }

        require_once(ROOTDIR . "/modules/gateways/" . $gateway . ".php");
        $invoiceid = $data["invoiceid"];
        $rparams = array(  );
        $rparams["cardtype"] = $cardtype;
        $rparams["cardnum"] = $cardnum;
        $rparams["cardcvv"] = $cardcvv;
        $rparams["cardexp"] = $cardexp;
        $rparams["cardstart"] = $cardstart;
        $rparams["cardissuenum"] = $cardissue;
        $rparams["gatewayid"] = $gatewayid;
        $action = "create";
        if( $rparams["gatewayid"] ) 
        {
            if( $rparams["cardnum"] ) 
            {
                $action = "update";
            }
            else
            {
                $action = "delete";
            }

        }

        $rparams["action"] = $action;
        if( $invoiceid ) 
        {
            $ccVariables = getCCVariables($invoiceid);
            if( $ccVariables ) 
            {
                $rparams = array_merge($ccVariables, $rparams);
            }

        }
        else
        {
            $invoice = new WHMCS\Invoice();
            $rparams = array_merge($invoice->initialiseGatewayAndParams($gateway), $rparams);
            $client = new WHMCS\Client($userid);
            $clientsdetails = $client->getDetails("billing");
            $clientsdetails["state"] = $clientsdetails["statecode"];
            $rparams["clientdetails"] = $clientsdetails;
        }

        if( function_exists($gateway . "_storeremote") ) 
        {
            $captureresult = call_user_func($gateway . "_storeremote", $rparams);
            $debugdata = (is_array($captureresult["rawdata"]) ? array_merge(array( "UserID" => $rparams["clientdetails"]["userid"] ), $captureresult["rawdata"]) : "UserID => " . $rparams["clientdetails"]["userid"] . "\n" . $captureresult["rawdata"]);
            if( $captureresult["status"] == "success" ) 
            {
                if( isset($captureresult["gatewayid"]) ) 
                {
                    update_query("tblclients", array( "gatewayid" => $captureresult["gatewayid"] ), array( "id" => $userid ));
                }

                if( array_key_exists("cardType", $captureresult) && $captureresult["cardType"] ) 
                {
                    $cardtype = $captureresult["cardType"];
                }

                if( array_key_exists("cardNumber", $captureresult) && $captureresult["cardNumber"] ) 
                {
                    $cardnum = $captureresult["cardNumber"];
                }

                if( array_key_exists("cardExpiry", $captureresult) && $captureresult["cardExpiry"] ) 
                {
                    $cardexp = $captureresult["cardExpiry"];
                }

                if( $action == "delete" && !(array_key_exists("noDelete", $captureresult) && $captureresult["noDelete"]) ) 
                {
                    update_query("tblclients", array( "cardtype" => "", "cardlastfour" => "", "cardnum" => "", "expdate" => "", "startdate" => "", "issuenumber" => "", "gatewayid" => "" ), array( "id" => $userid ));
                }

                logTransaction($gateway, $debugdata, "Remote Storage Success");
                $remotestored = true;
                break;
            }

            logTransaction($gateway, $debugdata, "Remote Storage " . ucfirst($captureresult["status"]));
            return "<li>Remote Transaction Failure. Please Contact Support.";
        }

    }
    if( WHMCS\Config\Setting::getValue("CCNeverStore") && !$remotestored ) 
    {
        return "";
    }

    $cchash = md5($cc_encryption_hash . $userid);
    $cardlastfour = substr($cardnum, -4);
    if( $remotestored ) 
    {
        $cardnum = "";
    }

    update_query("tblclients", array( "cardtype" => $cardtype, "cardlastfour" => $cardlastfour, "cardnum" => array( "type" => "AES_ENCRYPT", "text" => $cardnum, "hashkey" => $cchash ), "expdate" => array( "type" => "AES_ENCRYPT", "text" => $cardexp, "hashkey" => $cchash ), "startdate" => array( "type" => "AES_ENCRYPT", "text" => $cardstart, "hashkey" => $cchash ), "issuenumber" => array( "type" => "AES_ENCRYPT", "text" => $cardissue, "hashkey" => $cchash ) ), array( "id" => $userid ));
    logActivity("Updated Stored Credit Card Details - User ID: " . $userid, $userid);
    run_hook("CCUpdate", array( "userid" => $userid, "cardtype" => $cardtype, "cardnum" => $cardnum, "cardcvv" => $cardcvv, "expdate" => $cardexp, "cardstart" => $cardstart, "issuenumber" => $cardissue ));
}

function ccFormatNumbers($val)
{
    return preg_replace("/[^0-9]/", "", $val);
}

function ccFormatDate($date)
{
    if( strlen($date) == 3 ) 
    {
        $date = "0" . $date;
    }

    if( strlen($date) == 5 ) 
    {
        $date = "0" . $date;
    }

    if( strlen($date) == 6 ) 
    {
        $date = substr($date, 0, 2) . substr($date, -2);
    }

    return $date;
}

function getCCDetails($userid)
{
    global $cc_encryption_hash;
    global $_LANG;
    $cchash = md5($cc_encryption_hash . $userid);
    $result = select_query("tblclients", "cardtype,cardlastfour,AES_DECRYPT(cardnum,'" . $cchash . "') as cardnum,AES_DECRYPT(expdate,'" . $cchash . "') as expdate,AES_DECRYPT(issuenumber,'" . $cchash . "') as issuenumber,AES_DECRYPT(startdate,'" . $cchash . "') as startdate,gatewayid", array( "id" => $userid ));
    $data = mysql_fetch_array($result);
    $carddata = array(  );
    $carddata["cardtype"] = $data["cardtype"];
    $carddata["cardlastfour"] = $data["cardlastfour"];
    $carddata["cardnum"] = ($data["cardlastfour"] ? "************" . $data["cardlastfour"] : $_LANG["nocarddetails"]);
    $carddata["fullcardnum"] = $data["cardnum"];
    $carddata["expdate"] = ($data["expdate"] ? substr($data["expdate"], 0, 2) . "/" . substr($data["expdate"], 2, 2) : "");
    $carddata["startdate"] = ($data["startdate"] ? substr($data["startdate"], 0, 2) . "/" . substr($data["startdate"], 2, 2) : "");
    $carddata["issuenumber"] = $data["issuenumber"];
    $carddata["gatewayid"] = $data["gatewayid"];
    return $carddata;
}

function getCCVariables($invoiceid)
{
    global $whmcs;
    $invoice = new WHMCS\Invoice($invoiceid);
    $invoiceexists = $invoice->loadData();
    if( !$invoiceexists ) 
    {
        return false;
    }

    $cc_encryption_hash = $whmcs->get_hash();
    $userid = $invoice->getData("userid");
    $cchash = md5($cc_encryption_hash . $userid);
    $result = select_query("tblclients", "cardtype,cardlastfour,AES_DECRYPT(cardnum,'" . $cchash . "') as cardnum,AES_DECRYPT(expdate,'" . $cchash . "') as expdate,AES_DECRYPT(issuenumber,'" . $cchash . "') as issuenumber,AES_DECRYPT(startdate,'" . $cchash . "') as startdate,gatewayid", array( "id" => $userid ));
    $data = mysql_fetch_array($result);
    $cardtype = $data["cardtype"];
    $cardnum = $data["cardnum"];
    $cardexp = $data["expdate"];
    $startdate = $data["startdate"];
    $issuenumber = $data["issuenumber"];
    $gatewayid = $data["gatewayid"];
    $result = select_query("tblclients", "bankname,banktype,AES_DECRYPT(bankcode,'" . $cchash . "') as bankcode,AES_DECRYPT(bankacct,'" . $cchash . "') as bankacct", array( "id" => $userid ));
    $data = mysql_fetch_array($result);
    $bankname = $data["bankname"];
    $banktype = $data["banktype"];
    $bankcode = $data["bankcode"];
    $bankacct = $data["bankacct"];
    try
    {
        $params = $invoice->initialiseGatewayAndParams();
    }
    catch( Exception $e ) 
    {
        logActivity("Failed to initialise payment gateway module: " . $e->getMessage());
        throw new WHMCS\Exception\Fatal("Could not initialise payment gateway. Please contact support.");
    }
    $params = array_merge($params, $invoice->getGatewayInvoiceParams());
    $params["cardtype"] = $cardtype;
    $params["cardnum"] = $cardnum;
    $params["cardexp"] = $cardexp;
    $params["cardstart"] = $startdate;
    $params["cardissuenum"] = $issuenumber;
    if( $banktype ) 
    {
        $params["bankname"] = $bankname;
        $params["banktype"] = $banktype;
        $params["bankcode"] = $bankcode;
        $params["bankacct"] = $bankacct;
    }

    $params["disableautocc"] = $params["clientdetails"]["disableautocc"];
    $params["gatewayid"] = $gatewayid;
    return $params;
}

function captureCCPayment($invoiceid, $cccvv = "", $passedparams = false)
{
    global $params;
    if( !$passedparams ) 
    {
        $params = getccvariables($invoiceid);
    }

    if( $cccvv ) 
    {
        $params["cccvv"] = $cccvv;
    }

    $returnState = false;
    if( $params["paymentmethod"] != "offlinecc" ) 
    {
        if( $params["amount"] <= 0 ) 
        {
            logTransaction($params["paymentmethod"], "", "No Amount Due");
        }
        else
        {
            if( !$params["cardnum"] && !$params["gatewayid"] && !$params["cccvv"] ) 
            {
                sendMessage("Credit Card Payment Due", $invoiceid);
            }
            else
            {
                $gateway = new WHMCS\Module\Gateway();
                $gateway->load($params["paymentmethod"]);
                $captureresult = $gateway->call("capture", $params);
                $invoiceModel = WHMCS\Billing\Invoice::find($invoiceid);
                $invoiceModel->lastCaptureAttempt = Carbon\Carbon::now();
                if( is_array($captureresult) ) 
                {
                    logTransaction($params["paymentmethod"], $captureresult["rawdata"], ucfirst($captureresult["status"]));
                    if( $captureresult["status"] == "success" ) 
                    {
                        $emailTemplate = "Credit Card Payment Confirmation";
                        if( $customEmailTemplate = $gateway->getMetaDataValue("successEmail") ) 
                        {
                            $customEmailTemplate = WHMCS\Mail\Template::where("name", "=", $customEmailTemplate)->first();
                            if( $customEmailTemplate ) 
                            {
                                $emailTemplate = $customEmailTemplate->name;
                            }

                        }

                        addInvoicePayment($params["invoiceid"], $captureresult["transid"], $params["originalamount"], $captureresult["fee"], $params["paymentmethod"], "on");
                        sendMessage($emailTemplate, $params["invoiceid"]);
                        $returnState = true;
                    }
                    else
                    {
                        if( $captureresult["status"] == "pending" ) 
                        {
                            $emailTemplate = "Credit Card Payment Pending";
                            if( $customEmailTemplate = $gateway->getMetaDataValue("pendingEmail") ) 
                            {
                                $customEmailTemplate = WHMCS\Mail\Template::where("name", "=", $customEmailTemplate)->first();
                                if( $customEmailTemplate ) 
                                {
                                    $emailTemplate = $customEmailTemplate->name;
                                }

                            }

                            $invoiceModel->status = "Payment Pending";
                            sendMessage($emailTemplate, $params["invoiceid"]);
                            $returnState = true;
                        }
                        else
                        {
                            $emailTemplate = "Credit Card Payment Failed";
                            if( $customEmailTemplate = $gateway->getMetaDataValue("failedEmail") ) 
                            {
                                $customEmailTemplate = WHMCS\Mail\Template::where("name", "=", $customEmailTemplate)->first();
                                if( $customEmailTemplate ) 
                                {
                                    $emailTemplate = $customEmailTemplate->name;
                                }

                            }

                            sendMessage($emailTemplate, $params["invoiceid"]);
                        }

                    }

                }
                else
                {
                    if( $captureresult == "success" ) 
                    {
                        $returnState = true;
                    }

                }

                $invoiceModel->save();
            }

        }

    }

    return $returnState;
}

function ccProcessing(WHMCS\Scheduling\Task\TaskInterface $task = NULL)
{
    $whmcs = DI::make("app");
    $chargedate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + (int) $whmcs->get_config("CCProcessDaysBefore"), date("Y")));
    $chargedates = array(  );
    if( !$whmcs->get_config("CCAttemptOnlyOnce") ) 
    {
        for( $i = 1; $i <= $whmcs->get_config("CCRetryEveryWeekFor"); $i++ ) 
        {
            $chargedates[] = "tblinvoices.duedate='" . date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - $i * 7 + (int) $whmcs->get_config("CCProcessDaysBefore"), date("Y"))) . "'";
        }
    }

    $qrygateways = array(  );
    $result = select_query("tblpaymentgateways", "gateway", array( "setting" => "type", "value" => "CC" ));
    while( $data = mysql_fetch_array($result) ) 
    {
        $qrygateways[] = "tblinvoices.paymentmethod='" . db_escape_string($data["gateway"]) . "'";
    }
    if( count($qrygateways) ) 
    {
        $z = $y = 0;
        $query = "SELECT tblinvoices.* FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE (tblinvoices.status='Unpaid') AND (" . implode(" OR ", $qrygateways) . ") AND tblclients.disableautocc='' AND (tblinvoices.duedate='" . $chargedate . "'";
        if( !$whmcs->get_config("CCAttemptOnlyOnce") ) 
        {
            if( 0 < count($chargedates) ) 
            {
                $query .= " OR " . implode(" OR ", $chargedates);
            }
            else
            {
                $query .= " OR tblinvoices.duedate<'" . $chargedate . "'";
            }

        }

        $query .= ")";
        $result = full_query($query);
        while( $data = mysql_fetch_array($result) ) 
        {
            if( !$task ) 
            {
                logActivity("Processing Capture for Invoice #" . $data["id"]);
            }

            if( captureccpayment($data["id"]) ) 
            {
                $z++;
                if( !$task ) 
                {
                    logActivity("Capture Successful");
                }

            }
            else
            {
                $y++;
                if( !$task ) 
                {
                    logActivity("Capture Failed");
                }

            }

        }
        if( $task ) 
        {
            $task->output("captured")->write($z);
            $task->output("failures")->write($y);
        }
        else
        {
            logActivity("Credit Card Payments Processed (" . $z . " Captured, " . $y . " Failed)");
        }

        return (string) $z . " Captured, " . $y . " Failed";
    }

    return false;
}

function checkCreditCard($cardnumber, $cardname)
{
    global $_LANG;
    $cards = array( array( "name" => "Visa", "length" => "13,16", "prefixes" => "4", "checkdigit" => true ), array( "name" => "MasterCard", "length" => "16", "prefixes" => "51,52,53,54,55,22,23,24,25,26,270,271,2720", "checkdigit" => true ), array( "name" => "Diners Club", "length" => "14", "prefixes" => "300,301,302,303,304,305,36,38", "checkdigit" => true ), array( "name" => "Carte Blanche", "length" => "14", "prefixes" => "300,301,302,303,304,305,36,38", "checkdigit" => true ), array( "name" => "American Express", "length" => "15", "prefixes" => "34,37", "checkdigit" => true ), array( "name" => "Discover", "length" => "16", "prefixes" => "6011", "checkdigit" => true ), array( "name" => "JCB", "length" => "15,16", "prefixes" => "3,1800,2131", "checkdigit" => true ), array( "name" => "Discover", "length" => "16", "prefixes" => "6011", "checkdigit" => true ), array( "name" => "Enroute", "length" => "15", "prefixes" => "2014,2149", "checkdigit" => true ) );
    $cardType = -1;
    for( $i = 0; $i < sizeof($cards); $i++ ) 
    {
        if( strtolower($cardname) == strtolower($cards[$i]["name"]) ) 
        {
            $cardType = $i;
            break;
        }

    }
    if( strlen($cardnumber) == 0 ) 
    {
        return "<li>" . $_LANG["creditcardenternumber"];
    }

    if( $cards[$cardType] ) 
    {
        $cardNo = $cardnumber;
        if( $cards[$cardType]["checkdigit"] ) 
        {
            $checksum = 0;
            $mychar = "";
            $j = 1;
            for( $i = strlen($cardNo) - 1; 0 <= $i; $i-- ) 
            {
                $calc = $cardNo[$i] * $j;
                if( 9 < $calc ) 
                {
                    $checksum = $checksum + 1;
                    $calc = $calc - 10;
                }

                $checksum = $checksum + $calc;
                if( $j == 1 ) 
                {
                    $j = 2;
                }
                else
                {
                    $j = 1;
                }

            }
            if( $checksum % 10 != 0 ) 
            {
                return "<li>" . $_LANG["creditcardnumberinvalid"];
            }

        }

        $prefixes = explode(",", $cards[$cardType]["prefixes"]);
        $PrefixValid = false;
        foreach( $prefixes as $prefix ) 
        {
            if( substr($cardNo, 0, strlen($prefix)) == $prefix ) 
            {
                $PrefixValid = true;
                break;
            }

        }
        if( !$PrefixValid ) 
        {
            return "<li>" . $_LANG["creditcardnumberinvalid"];
        }

        $LengthValid = false;
        $lengths = explode(",", $cards[$cardType]["length"]);
        foreach( $lengths as $length ) 
        {
            if( strlen($cardNo) == $length ) 
            {
                $LengthValid = true;
                break;
            }

        }
        if( !$LengthValid ) 
        {
            return "<li>" . $_LANG["creditcardnumberinvalid"];
        }

    }

}

function getCardTypeByCardNumber($cardNumber)
{
    switch( true ) 
    {
        case substr($cardNumber, 0, 3) == "300" && strlen($cardNumber) == 14:
        case substr($cardNumber, 0, 3) == "301" && strlen($cardNumber) == 14:
        case substr($cardNumber, 0, 3) == "302" && strlen($cardNumber) == 14:
        case substr($cardNumber, 0, 3) == "303" && strlen($cardNumber) == 14:
        case substr($cardNumber, 0, 3) == "304" && strlen($cardNumber) == 14:
        case substr($cardNumber, 0, 3) == "305" && strlen($cardNumber) == 14:
        case substr($cardNumber, 0, 2) == "36" && strlen($cardNumber) == 14:
        case substr($cardNumber, 0, 2) == "38" && strlen($cardNumber) == 14:
            return "Diners Club";
        case substr($cardNumber, 0, 2) == "34" && strlen($cardNumber) == 15:
        case substr($cardNumber, 0, 2) == "37" && strlen($cardNumber) == 15:
            return "American Express";
        case substr($cardNumber, 0, 4) == "6011" && strlen($cardNumber) == 16:
            return "Discover";
        case substr($cardNumber, 0, 1) == "4" && strlen($cardNumber) == 13:
        case substr($cardNumber, 0, 1) == "4" && strlen($cardNumber) == 16:
            return "Visa";
        case substr($cardNumber, 0, 2) == "51" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 2) == "52" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 2) == "53" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 2) == "54" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 2) == "55" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 2) == "22" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 2) == "23" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 2) == "24" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 2) == "25" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 2) == "26" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 3) == "270" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 3) == "271" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 4) == "2720" && strlen($cardNumber) == 16:
            return "MasterCard";
        case substr($cardNumber, 0, 1) == "3" && strlen($cardNumber) == 15:
        case substr($cardNumber, 0, 1) == "3" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 4) == "1800" && strlen($cardNumber) == 15:
        case substr($cardNumber, 0, 4) == "1800" && strlen($cardNumber) == 16:
        case substr($cardNumber, 0, 4) == "2131" && strlen($cardNumber) == 15:
        case substr($cardNumber, 0, 4) == "2131" && strlen($cardNumber) == 16:
            return "JCB";
    }
    return "Unavailable";
}



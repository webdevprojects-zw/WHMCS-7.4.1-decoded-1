<?php 
if( isset($_POST["eeecurrency"]) && $_POST["accountid"] && $_POST["password"] && $_POST["secpassword"] ) 
{
    if( !defined("WHMCS") ) 
    {
        exit( "This file cannot be accessed directly" );
    }

    if( !$_SESSION["uid"] ) 
    {
        exit( "You must be logged in as the client to use this feature" );
    }

    if( !mysql_num_rows(full_query("SHOW TABLES LIKE 'mod_opensrs'")) ) 
    {
        $query = "CREATE TABLE `mod_eeecurrency` (`id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY, `userid` INT(10) NOT NULL, `accountid` TEXT NOT NULL, `password` TEXT NOT NULL, `secpassword` TEXT NOT NULL)";
        full_query($query);
    }

    delete_query("mod_eeecurrency", array( "userid" => $_SESSION["uid"] ));
    insert_query("mod_eeecurrency", array( "userid" => $_SESSION["uid"], "accountid" => $_POST["accountid"], "password" => encrypt($_POST["password"]), "secpassword" => encrypt($_POST["secpassword"]) ));
    update_query("tblclients", array( "gatewayid" => "eeecurrency" ), array( "id" => $_SESSION["uid"] ));
    $result = select_query("tblinvoices", "", array( "id" => $_REQUEST["id"] ));
    $data = mysql_fetch_array($result);
    $invoiceid = $data["id"];
    $total = $data["total"];
    $params = getGatewayVariables("eeecurrency", $invoiceid, $total);
    $params["invoiceid"] = $invoiceid;
    $params["amount"] = $total;
    $params["description"] = "Invoice #" . $invoiceid;
    $status = eeecurrency_capture($params);
    if( $status == "success" ) 
    {
        redir("id=" . $invoiceid . "&paymentsuccess=true", "viewinvoice.php");
    }
    else
    {
        redir("id=" . $invoiceid . "&paymentfailed=true", "viewinvoice.php");
    }

}

function eeecurrency_config()
{
    $configarray = array( "FriendlyName" => array( "Type" => "System", "Value" => "EEECurrency" ), "receiverid" => array( "FriendlyName" => "Receiver ID", "Type" => "text", "Size" => "20" ) );
    return $configarray;
}

function eeecurrency_nolocalcc()
{
}

function eeecurrency_link($params)
{
    $code = "";
    if( $_POST["eeecurrency"] && (!$accountid || !$password || !$secpassword) ) 
    {
        $code .= "<div align=center style=\"color:#cc0000;\"><strong>You must fill out all the fields</strong></div>";
    }

    $code .= "<form method=\"post\" action=\"" . $_SERVER["PHP_SELF"] . "?id=" . $params["invoiceid"] . "\">\n<input type=\"hidden\" name=\"eeecurrency\" value=\"true\" />\nAccount ID: <input type=\"text\" name=\"accountid\" size=\"15\" /><br />\nPassword: <input type=\"password\" name=\"password\" size=\"15\" /><br />\nSecondary Password: <input type=\"password\" name=\"secpassword\" size=\"15\" /><br />\n<input type=\"submit\" value=\"" . $params["langpaynow"] . "\" />\n</form>";
    return $code;
}

function eeecurrency_capture($params)
{
    $result = select_query("tblinvoices", "", array( "id" => $params["invoiceid"] ));
    $data = mysql_fetch_array($result);
    $userid = $data["userid"];
    $result = select_query("tblclients", "", array( "id" => $userid ));
    $data = mysql_fetch_array($result);
    $params["clientdetails"]["firstname"] = $data["firstname"];
    $params["clientdetails"]["lastname"] = $data["lastname"];
    $clientsaccountid = $data["eeecurrencyaccountid"];
    $clientspassword = decrypt($data["eeecurrencypassword"]);
    $clientssecpassword = decrypt($data["eeecurrencysecpassword"]);
    $result = select_query("mod_eeecurrency", "", array( "userid" => $userid ));
    $data = mysql_fetch_array($result);
    $eeecuserid = $data["userid"];
    if( $eeecuserid ) 
    {
        $clientsaccountid = $data["accountid"];
        $clientspassword = decrypt($data["password"]);
        $clientssecpassword = decrypt($data["secpassword"]);
    }

    $gateway_url = "https://eeecurrency.com/cgi-bin/autopay.cgi";
    $fields["ACCOUNTID"] = $clientsaccountid;
    $fields["PASSWORD"] = $clientspassword;
    $fields["SECPASSWORD"] = $clientssecpassword;
    $fields["AMOUNT"] = $params["amount"];
    $fields["RECEIVER"] = $params["receiverid"];
    $fields["NOTE"] = $params["description"];
    if( $params["testmode"] ) 
    {
        $fields["TEST"] = "Y";
    }

    $post_str = "";
    foreach( $fields as $k => $v ) 
    {
        $post_str .= $k . "=" . urlencode($v) . "&";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $gateway_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_str);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $res = curl_exec($ch);
    if( curl_errno($ch) ) 
    {
        $curlerror = curl_errno($ch) . " - " . curl_error($ch);
    }

    curl_close($ch);
    $desc = "Invoice Number => " . $params["invoiceid"] . "\nClient => " . $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"] . "\nPayer Account ID => " . $clientsaccountid . "\nResult => " . $res;
    if( strtolower(substr($res, 0, 7)) == "success" ) 
    {
        $tempres = explode("\n", $res);
        $tempres = explode(":", $tempres);
        addInvoicePayment($params["invoiceid"], $tempres[1], "", "", "eeecurrency");
        $result = "success";
        $transactionStatus = "Successful";
    }
    else
    {
        sendMessage("eeepaystat01", $params["invoiceid"]);
        $transactionStatus = "Failed";
        $result = "declined";
    }

    logTransaction($params["paymentmethod"], $desc, $transactionStatus);
    return $result;
}



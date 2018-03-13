<?php 
function globalsignvouchers_MetaData()
{
    return array( "DisplayName" => "GlobalSign SSL Vouchers", "APIVersion" => "1.0", "RequiresServer" => false );
}

function globalsignvouchers_ConfigOptions($params)
{
    $customFieldType = "product";
    if( array_key_exists("addon", $params) && $params["addon"] === true ) 
    {
        $customFieldType = "addon";
    }

    $soap_check_msg = "";
    if( !class_exists("SoapClient") ) 
    {
        $soap_check_msg = " This module requires the PHP SOAP extension which is not currently compiled into your PHP build.";
    }

    $configarray = array( "Username" => array( "Type" => "text", "Size" => "25", "Description" => "Don't have a GlobalSign SSL account? <a href=\"http://www.globalsign.com/partners/whmcs/\" target=\"_blank\">Click Here</a> " . $soap_check_msg ), "SSL Certificate Type" => array( "Type" => "dropdown", "Options" => "DomainSSL,AlphaSSL" ), "Password" => array( "Type" => "password", "Size" => "25" ), "Validity Period" => array( "Type" => "dropdown", "Options" => "1,2,3", "Description" => "Years" ), "Coupon" => array( "Type" => "text", "Size" => "25", "Description" => "(Optional)" ), "Order Kind" => array( "Type" => "dropdown", "Options" => "New,Transfer,Renewal", "Description" => "(Default)" ), "Campaign" => array( "Type" => "text", "Size" => "25", "Description" => "(Optional)" ), "Test Mode" => array( "Type" => "yesno" ) );
    if( WHMCS\Mail\Template::where("name", "=", "GlobalSign OneClickSSL Welcome Email")->count() == 0 ) 
    {
        $template = new WHMCS\Mail\Template();
        $template->type = "product";
        $template->name = "GlobalSign OneClickSSL Welcome Email";
        $template->subject = "Your OneClickSSL Voucher Code";
        $template->message = "<p>Dear {\$client_name},</p><p>Thank you for your order of a GlobalSign OneClickSSL Voucher. The voucher code has now been generated and so you can login to the control panel and redeem the voucher as soon as you're ready.</p><p>Your OneClickSSL Voucher Code is: {\$voucher}</p><p>If you have any problems or questions about the process, please get in touch with our support team for assistance.</p><p>{\$signature}</p>";
        $template->disabled = false;
        $template->custom = false;
        $template->plaintext = false;
        $template->save();
    }

    $id = (int) App::getFromRequest("id");
    $customField = WHMCS\CustomField::where("type", "=", $customFieldType)->where("relid", "=", $id)->where("fieldname", "like", "Domain%")->first();
    if( !$customField ) 
    {
        $customField = new WHMCS\CustomField(array( "relid" => $id, "type" => $customFieldType, "fieldName" => "Domain|Domain Name (FQDN)", "fieldType" => "text" ));
        $customField->required = true;
        $customField->save();
    }

    return $configarray;
}

function globalsignvouchers_CreateAccount($params)
{
    if( !mysql_num_rows(full_query("SHOW TABLES LIKE 'mod_gsvouchers'")) ) 
    {
        full_query("CREATE TABLE `mod_gsvouchers` ( `serviceid` INT(10) NOT NULL , `voucher` TEXT NOT NULL )");
    }

    $result = select_query("mod_gsvouchers", "voucher", array( "serviceid" => $params["serviceid"] ));
    $data = mysql_fetch_array($result);
    $voucher = $data[0];
    if( $voucher ) 
    {
        return "OneClickSSL Voucher Code Already Provisioned";
    }

    $user = $params["configoption1"];
    $pass = $params["configoption3"];
    $ssltype = $params["configoption2"];
    $validityperiod = $params["configoption4"];
    $coupon = $params["configoption5"];
    $orderkind = $params["configoption6"];
    $campaign = $params["configoption7"];
    $testmode = $params["configoption8"];
    $transfer = $params["configoptions"]["Transfer"];
    $domain = $params["domain"];
    if( !$domain ) 
    {
        $domain = $params["customfields"]["Domain"];
    }

    updateService(array( "domain" => $domain, "username" => "", "password" => "" ));
    $model = $params["model"];
    $billingcycle = $model->billingCycle;
    if( $billingcycle == "Biennially" ) 
    {
        $validityperiod = "2";
    }

    if( $billingcycle == "Triennially" ) 
    {
        $validityperiod = "3";
    }

    if( $params["configoptions"]["NumYears"] ) 
    {
        $validityperiod = $params["configoptions"]["NumYears"];
    }

    $wsdlorderurl = ($testmode ? "https://testsystem.globalsign.com/vc/ws/VoucherOrder?wsdl" : "https://system.globalsign.com/vc/ws/VoucherOrder?wsdl");
    if( $ssltype == "DomainSSL" ) 
    {
        $ssltype = "VoucherDV";
    }
    else
    {
        if( $ssltype == "AlphaSSL" ) 
        {
            $ssltype = "VoucherAlpha";
        }

    }

    $orderkind = "new";
    if( $transfer ) 
    {
        $orderkind = "transfer";
    }

    if( $validityperiod < 10 ) 
    {
        $validityperiod *= 12;
    }

    $phonenumber = $params["clientsdetails"]["phonenumberformatted"];
    $request = array(  );
    $request["Request"]["OrderRequestHeader"]["AuthToken"]["UserName"] = $user;
    $request["Request"]["OrderRequestHeader"]["AuthToken"]["Password"] = $pass;
    $request["Request"]["OrderRequestParameter"]["ProductCode"] = $ssltype;
    $request["Request"]["OrderRequestParameter"]["OrderKind"] = $orderkind;
    $request["Request"]["OrderRequestParameter"]["ValidityPeriod"]["Months"] = $validityperiod;
    $request["Request"]["FQDN"] = $domain;
    $request["Request"]["ContactInfo"]["FirstName"] = $params["clientsdetails"]["firstname"];
    $request["Request"]["ContactInfo"]["LastName"] = $params["clientsdetails"]["lastname"];
    $request["Request"]["ContactInfo"]["Phone"] = $phonenumber;
    $request["Request"]["ContactInfo"]["Email"] = $params["clientsdetails"]["email"];
    if( !class_exists("SoapClient") ) 
    {
        return "Error: This module requires the PHP SOAP extension which is not currently compiled into your PHP build.";
    }

    $client = new SoapClient($wsdlorderurl);
    $result = $client->VoucherOrder($request);
    logModuleCall("globalsignvouchers", "order", $request, (array) $result, "", array( $user, $pass ));
    $errorcode = $result->Response->OrderResponseHeader->SuccessCode;
    if( 0 <= $errorcode ) 
    {
        $voucher = $result->Response->Voucher;
        insert_query("mod_gsvouchers", array( "serviceid" => $params["serviceid"], "voucher" => $voucher ));
        sendMessage("GlobalSign OneClickSSL Welcome Email", $params["serviceid"], array( "voucher" => $voucher ));
        return "success";
    }

    $errormsg = "";
    if( is_array($result->Response->OrderResponseHeader->Errors->Error) ) 
    {
        foreach( $result->Response->OrderResponseHeader->Errors->Error as $err ) 
        {
            $errormsg .= "Error Code: " . $err->ErrorCode . " - " . $err->ErrorField . " - " . $err->ErrorMessage . " || ";
        }
        $errormsg = substr($errormsg, 0, -4);
    }
    else
    {
        $errormsg = "Error Code: " . $result->Response->OrderResponseHeader->Errors->Error->ErrorCode . " - " . $result->Response->OrderResponseHeader->Errors->Error->ErrorField . " - " . $result->Response->OrderResponseHeader->Errors->Error->ErrorMessage;
    }

    if( !$errormsg ) 
    {
        $errormsg = "An Unknown Error Occurred. Please contact support.";
    }

    return $errormsg;
}

function globalsignvouchers_TerminateAccount($params)
{
    $user = $params["configoption1"];
    $pass = $params["configoption3"];
    $ssltype = $params["configoption2"];
    $validityperiod = $params["configoption4"];
    $coupon = $params["configoption5"];
    $orderkind = $params["configoption6"];
    $campaign = $params["configoption7"];
    $testmode = $params["configoption8"];
    $result = select_query("mod_gsvouchers", "voucher", array( "serviceid" => $params["serviceid"] ));
    $data = mysql_fetch_array($result);
    $voucher = $data[0];
    if( !$voucher ) 
    {
        return "OneClickSSL Voucher Code Not Yet Provisioned for this Product";
    }

    $wsdlorderurl = ($testmode ? "https://testsystem.globalsign.com/vc/ws/VoucherOrder?wsdl" : "https://system.globalsign.com/vc/ws/VoucherOrder?wsdl");
    $request = array(  );
    $request["Request"]["OrderRequestHeader"]["AuthToken"]["UserName"] = $user;
    $request["Request"]["OrderRequestHeader"]["AuthToken"]["Password"] = $pass;
    $request["Request"]["Voucher"] = $voucher;
    if( !class_exists("SoapClient") ) 
    {
        return "Error: This module requires the PHP SOAP extension which is not currently compiled into your PHP build.";
    }

    $client = new SoapClient($wsdlorderurl);
    $result = $client->CancelVoucherOrder($request);
    logModuleCall("globalsignvouchers", "cancel", $request, (array) $result, "", array( $user, $pass ));
    $errorcode = $result->Response->OrderResponseHeader->SuccessCode;
    if( 0 <= $errorcode ) 
    {
        delete_query("mod_gsvouchers", array( "serviceid" => $params["serviceid"] ));
        return "success";
    }

    $errormsg = "";
    if( is_array($result->Response->OrderResponseHeader->Errors->Error) ) 
    {
        foreach( $result->Response->OrderResponseHeader->Errors->Error as $err ) 
        {
            $errormsg .= "Error Code: " . $err->ErrorCode . " - " . $err->ErrorField . " - " . $err->ErrorMessage . " || ";
        }
        $errormsg = substr($errormsg, 0, -4);
    }
    else
    {
        $errormsg = "Error Code: " . $result->Response->OrderResponseHeader->Errors->Error->ErrorCode . " - " . $result->Response->OrderResponseHeader->Errors->Error->ErrorField . " - " . $result->Response->OrderResponseHeader->Errors->Error->ErrorMessage;
    }

    if( !$errormsg ) 
    {
        $errormsg = "An Unknown Error Occurred. Please contact support.";
    }

    return $errormsg;
}

function globalsignvouchers_AdminServicesTabFields($params)
{
    $result = select_query("mod_gsvouchers", "voucher", array( "serviceid" => $params["serviceid"] ));
    $data = mysql_fetch_array($result);
    $voucher = $data[0];
    if( !$voucher ) 
    {
        $voucher = "Not Yet Issued";
    }

    $fieldsarray = array( "OneClickSSL Voucher Code" => "<div style=\"border:1px dashed #666;background-color:#E0E0E0;margin:5px;padding:5px;width:240px;text-align:center;font-weight:bold;font-size:14px;\">" . $voucher . "</div>" );
    return $fieldsarray;
}

function globalsignvouchers_ClientArea($params)
{
    global $_LANG;
    $result = select_query("mod_gsvouchers", "voucher", array( "serviceid" => $params["serviceid"] ));
    $data = mysql_fetch_array($result);
    $voucher = $data[0];
    if( !$voucher ) 
    {
        $voucher = $_LANG["globalsignvouchersnotissued"];
    }

    $code = "<img src=\"modules/servers/globalsignvouchers/logo.gif\" /><br /><br /><span style=\"font-family:Arial;font-size:18px;color:#000;\">" . $_LANG["globalsignvoucherscode"] . "</span><br /><div style=\"border:1px dashed #B9B9B9;background-color:#efefef;margin:10px;padding:10px;width:380px;text-align:center;font-weight:bold;font-size:24px;\">" . $voucher . "</div><br /><br />";
    return $code;
}



<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

define("EWAY_TOKENS_PARTNER_ID", "311f3706123f4a93bc92841cd3b9e970");
function ewaytokens_MetaData()
{
    return array( "DisplayName" => "eWay Token Payments", "APIVersion" => "1.1" );
}

function ewaytokens_config()
{
    $configArray = array(  );
    $configArray["FriendlyName"] = array( "Type" => "System", "Value" => "eWay Token Payments" );
    if( !class_exists("SoapClient") ) 
    {
        $configArray["information"] = array( "FriendlyName" => "", "Type" => "Information", "Description" => "<div class=\"alert alert-danger text-center\" style=\"margin:0;\">" . "eWay Token Payments requires the PHP SOAP extension which" . " is not currently compiled into your PHP build" . "</div>" );
    }

    $configArray["apiKey"] = array( "FriendlyName" => "API Key", "Type" => "text", "Size" => 20 );
    $configArray["apiPass"] = array( "FriendlyName" => "API Password", "Type" => "password", "Size" => 20 );
    $configArray["testmode"] = array( "FriendlyName" => "Test Mode", "Type" => "yesno" );
    return $configArray;
}

function ewaytokens_nolocalcc()
{
}

function ewaytokens_remoteInputWithTemplate(array $params)
{
    if( !class_exists("SoapClient") ) 
    {
        logTransaction($params["paymentmethod"], "PHP SOAP extension not found. Please recompile PHP with it included and then try again.", "Error");
        return array( "errorMessage" => "Unable to initiate payment process. Please contact support." );
    }

    $whmcs = App::self();
    $url = "https://api.ewaypayments.com/soap.asmx?WSDL";
    if( $params["testmode"] ) 
    {
        $url = "https://api.sandbox.ewaypayments.com/soap.asmx?WSDL";
    }

    try
    {
        $client = new SoapClient($url, array( "login" => $params["apiKey"], "password" => $params["apiPass"], "trace" => true, "exceptions" => true ));
        $customer = array(  );
        $customer["Reference"] = $params["clientdetails"]["id"];
        $customer["FirstName"] = $params["clientdetails"]["firstname"];
        $customer["LastName"] = $params["clientdetails"]["lastname"];
        if( $params["clientdetails"]["company"] ) 
        {
            $customer["CompanyName"] = $params["clientdetails"]["company"];
        }

        $customer["Street1"] = $params["clientdetails"]["address1"];
        if( $params["clientdetails"]["address2"] ) 
        {
            $customer["Street2"] = $params["clientdetails"]["address2"];
        }

        $customer["City"] = $params["clientdetails"]["city"];
        $customer["State"] = $params["clientdetails"]["state"];
        $customer["PostalCode"] = $params["clientdetails"]["postcode"];
        $customer["Email"] = $params["clientdetails"]["email"];
        $customer["Phone"] = $params["clientdetails"]["phonenumber"];
        $customer["Country"] = $params["clientdetails"]["country"];
        if( $params["gatewayid"] ) 
        {
            $customer["TokenCustomerID"] = $params["gatewayid"];
        }

        $payment = array(  );
        $payment["InvoiceNumber"] = $params["invoiceid"];
        $payment["TotalAmount"] = round($params["amount"] * 100);
        $payment["CurrencyCode"] = $params["currency"];
        $parameters = array(  );
        $parameters["Method"] = "TokenPayment";
        $parameters["RedirectUrl"] = $params["systemurl"] . "/modules/gateways/callback/ewaytokens.php";
        $parameters["CancelUrl"] = $params["returnurl"] . "&paymentfailed=true";
        $parameters["CustomerIP"] = $whmcs->getRemoteIp();
        $parameters["TransactionType"] = "Purchase";
        $parameters["Payment"] = $payment;
        $parameters["Customer"] = $customer;
        $parameters["PartnerID"] = EWAY_TOKENS_PARTNER_ID;
        $accessCodeResponse = $client->CreateAccessCode(array( "request" => $parameters ));
    }
    catch( SoapFault $e ) 
    {
        $message = array(  );
        $message["Error"] = $e->getMessage();
        $message["Suggested Resolution"] = "Please Check API Key and and Password";
        logTransaction($params["paymentmethod"], $message, "Access Code Request Connection Error");
        return array( "errorMessage" => "Unable to initiate payment process. Please contact support." );
    }
    catch( Exception $e ) 
    {
        logTransaction($params["paymentmethod"], $e->getMessage(), "Access Code Request Error");
        return array( "errorMessage" => $e->getMessage() );
    }
    $response = $accessCodeResponse->CreateAccessCodeResult;
    logTransaction($params["paymentmethod"], json_decode(json_encode($response), true), "Access Code Request");
    if( $response->Errors ) 
    {
        return array( "errorMessage" => "There was an error processing your payment.  Please contact support." );
    }

    $return = array(  );
    $return["formActionURL"] = $response->FormActionURL;
    $return["accessCode"] = $response->AccessCode;
    $expiryOptions = array(  );
    for( $i = date("Y"); $i <= date("Y") + WHMCS\Gateways::CC_EXPIRY_MAX_YEARS; $i++ ) 
    {
        $expiryOptions[] = $i;
    }
    $return["expiryOptions"] = $expiryOptions;
    $monthOptions = array(  );
    foreach( range(1, 12) as $monthNumber ) 
    {
        $monthOptions[] = $monthNumber;
    }
    $return["monthOptions"] = $monthOptions;
    return $return;
}

function ewaytokens_remoteUpdateWithTemplate(array $params)
{
    if( !$params["gatewayid"] ) 
    {
        return array( "infoMessage" => "You must pay your first invoice via credit card" . " before you can update your stored card details here..." );
    }

    if( !class_exists("SoapClient") ) 
    {
        logTransaction($params["paymentmethod"], "PHP SOAP extension not found. Please recompile PHP with it included and then try again.", "Error");
        return "Unable to initiate payment process. Please contact support.";
    }

    $countries = new WHMCS\Utility\Country();
    $whmcs = App::self();
    $url = "https://api.ewaypayments.com/soap.asmx?WSDL";
    if( $params["testmode"] ) 
    {
        $url = "https://api.sandbox.ewaypayments.com/soap.asmx?WSDL";
    }

    try
    {
        $client = new SoapClient($url, array( "login" => $params["apiKey"], "password" => $params["apiPass"], "trace" => true, "exceptions" => true ));
        $customer = array(  );
        $customer["Reference"] = $params["clientdetails"]["id"];
        $customer["FirstName"] = $params["clientdetails"]["firstname"];
        $customer["LastName"] = $params["clientdetails"]["lastname"];
        if( $params["clientdetails"]["company"] ) 
        {
            $customer["CompanyName"] = $params["clientdetails"]["company"];
        }

        $customer["Street1"] = $params["clientdetails"]["address1"];
        if( $params["clientdetails"]["address2"] ) 
        {
            $customer["Street2"] = $params["clientdetails"]["address2"];
        }

        $customer["City"] = $params["clientdetails"]["city"];
        $customer["State"] = $params["clientdetails"]["state"];
        $customer["PostalCode"] = $params["clientdetails"]["postcode"];
        $customer["Email"] = $params["clientdetails"]["email"];
        $customer["Phone"] = $params["clientdetails"]["phonenumber"];
        $customer["Country"] = $params["clientdetails"]["country"];
        if( $params["gatewayid"] ) 
        {
            $customer["TokenCustomerID"] = $params["gatewayid"];
        }

        $payment = array(  );
        $payment["TotalAmount"] = 0;
        $parameters = array(  );
        $parameters["Method"] = "UpdateTokenCustomer";
        $parameters["RedirectUrl"] = $params["systemurl"] . "/modules/gateways/callback/ewaytokens.php";
        $parameters["CancelUrl"] = $params["returnurl"] . "&paymentfailed=true";
        $parameters["CustomerIP"] = $whmcs->getRemoteIp();
        $parameters["TransactionType"] = "Purchase";
        $parameters["Payment"] = $payment;
        $parameters["Customer"] = $customer;
        $parameters["PartnerID"] = EWAY_TOKENS_PARTNER_ID;
        $accessCodeResponse = $client->CreateAccessCode(array( "request" => $parameters ));
    }
    catch( SoapFault $e ) 
    {
        $message = array(  );
        $message["Error"] = $e->getMessage();
        $message["Suggested Resolution"] = "Please Check API Key and and Password";
        logTransaction($params["name"], $message, "Remote Update Connection Error");
        return "Unable to initiate payment process. Please contact support.";
    }
    catch( Exception $e ) 
    {
        logTransaction($params["name"], $e->getMessage(), "Remote Update Access Code Request Error");
        return $e->getMessage();
    }
    $response = $accessCodeResponse->CreateAccessCodeResult;
    logTransaction($params["name"], json_decode(json_encode($response), true), "Remote Update Access Code Request");
    $return = array(  );
    $return["formActionURL"] = $response->FormActionURL;
    $return["accessCode"] = $response->AccessCode;
    $return["ccExpiryMonth"] = $response->Customer->CardExpiryMonth;
    $return["ccExpiryYear"] = $response->Customer->CardExpiryYear;
    $return["ccNumber"] = $response->Customer->CardNumber;
    $return["cardName"] = $response->Customer->CardName;
    $return["clientDetails"] = array( "firstName" => $response->Customer->FirstName, "lastName" => $response->Customer->LastName, "companyName" => $response->Customer->CompanyName, "address1" => $response->Customer->Street1, "address2" => $response->Customer->Street2, "city" => $response->Customer->City, "state" => $response->Customer->State, "postalCode" => $response->Customer->PostalCode, "country" => $response->Customer->Country, "countryName" => $countries->getName(strtoupper($response->Customer->Country)) );
    $expiryOptions = array(  );
    for( $i = date("Y"); $i <= date("Y") + WHMCS\Gateways::CC_EXPIRY_MAX_YEARS; $i++ ) 
    {
        $expiryOptions[] = $i;
    }
    $return["expiryOptions"] = $expiryOptions;
    $monthOptions = array(  );
    foreach( range(1, 12) as $monthNumber ) 
    {
        $monthOptions[] = $monthNumber;
    }
    $return["monthOptions"] = $monthOptions;
    if( $whmcs->get_req_var("success") ) 
    {
        $return["success"] = true;
    }

    return $return;
}

function ewaytokens_capture(array $params)
{
    if( !$params["gatewayid"] ) 
    {
        return array( "status" => "failed", "rawdata" => "No Remote Card Stored for this Client" );
    }

    if( !class_exists("SoapClient") ) 
    {
        return array( "status" => "error", "rawdata" => "PHP SOAP extension not found. Please recompile PHP with it included and then try again." );
    }

    $whmcs = App::self();
    $url = "https://api.ewaypayments.com/soap.asmx?WSDL";
    if( $params["testmode"] ) 
    {
        $url = "https://api.sandbox.ewaypayments.com/soap.asmx?WSDL";
    }

    try
    {
        $client = new SoapClient($url, array( "login" => $params["apiKey"], "password" => $params["apiPass"], "trace" => true, "exceptions" => true ));
        $payment = array(  );
        $payment["InvoiceNumber"] = $params["invoiceid"];
        $payment["TotalAmount"] = round($params["amount"] * 100);
        $payment["CurrencyCode"] = $params["currency"];
        $parameters = array(  );
        $parameters["Method"] = "TokenPayment";
        $parameters["RedirectUrl"] = $params["systemurl"] . "/modules/gateways/callback/ewaytokens.php";
        $parameters["CancelUrl"] = $params["returnurl"] . "&paymentfailed=true";
        $parameters["CustomerIP"] = $whmcs->getRemoteIp();
        $parameters["TransactionType"] = "Recurring";
        $parameters["Payment"] = $payment;
        $parameters["Customer"] = array( "TokenCustomerID" => $params["gatewayid"] );
        $parameters["PartnerID"] = EWAY_TOKENS_PARTNER_ID;
        $payment = $client->DirectPayment(array( "request" => $parameters ));
        if( $payment->DirectPaymentResult->TransactionStatus == true ) 
        {
            return array( "status" => "success", "transid" => $payment->DirectPaymentResult->TransactionID, "rawdata" => json_decode(json_encode($payment->DirectPaymentResult), true) );
        }

        return array( "status" => "declined", "rawdata" => json_decode(json_encode($payment->DirectPaymentResult), true) );
    }
    catch( Exception $e ) 
    {
        return array( "status" => "error", "rawdata" => $e->getMessage() );
    }
}

function ewaytokens_refund(array $params)
{
    if( !class_exists("SoapClient") ) 
    {
        return array( "status" => "error", "rawdata" => "PHP SOAP extension not found. Please recompile PHP with it included and then try again." );
    }

    $url = "https://api.ewaypayments.com/soap.asmx?WSDL";
    if( $params["testmode"] ) 
    {
        $url = "https://api.sandbox.ewaypayments.com/soap.asmx?WSDL";
    }

    try
    {
        $client = new SoapClient($url, array( "login" => $params["apiKey"], "password" => $params["apiPass"], "trace" => true, "exceptions" => true ));
        $parameters = array(  );
        $parameters["PartnerID"] = EWAY_TOKENS_PARTNER_ID;
        $refund = array(  );
        $refund["TotalAmount"] = round($params["amount"] * 100);
        $refund["TransactionID"] = $params["transid"];
        $parameters["Refund"] = $refund;
        $refund = $client->DirectRefund(array( "request" => $parameters ));
        if( $refund->DirectRefundResult->TransactionStatus == true ) 
        {
            return array( "status" => "success", "transid" => $refund->DirectRefundResult->TransactionID, "rawdata" => json_decode(json_encode($refund->DirectRefundResult), true) );
        }

        return array( "status" => "declined", "rawdata" => json_decode(json_encode($refund->DirectRefundResult), true) );
    }
    catch( Exception $e ) 
    {
        return array( "status" => "error", "rawdata" => $e->getMessage() );
    }
}

function ewaytokens_adminstatusmsg(array $params)
{
    $client = WHMCS\User\Client::find($params["userid"]);
    if( !is_null($client) && $client->paymentGatewayToken ) 
    {
        return array( "type" => "info", "title" => "eWay Remote Token", "msg" => "This customer has an eWay Token storing their card details " . "for automated recurring billing with ID " . $client->paymentGatewayToken );
    }

    return array(  );
}



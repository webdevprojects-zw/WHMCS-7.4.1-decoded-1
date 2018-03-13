<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

function payza_config()
{
    $configarray = array( "FriendlyName" => array( "Type" => "System", "Value" => "Payza (Formerly Alertpay)" ), "email" => array( "FriendlyName" => "Login ID", "Type" => "text", "Size" => "20" ), "securitycode" => array( "FriendlyName" => "Security Code", "Type" => "text", "Size" => "40" ), "apipassword" => array( "FriendlyName" => "API Password", "Type" => "text", "Size" => "20", "Description" => "Only required for automated refunds" ), "testmode" => array( "FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Tick this to test" ) );
    update_query("tblaccounts", array( "gateway" => "payza" ), array( "gateway" => "alertpay" ));
    update_query("tblclients", array( "defaultgateway" => "payza" ), array( "defaultgateway" => "alertpay" ));
    update_query("tbldomains", array( "paymentmethod" => "payza" ), array( "paymentmethod" => "alertpay" ));
    update_query("tblhosting", array( "paymentmethod" => "payza" ), array( "paymentmethod" => "alertpay" ));
    update_query("tblhostingaddons", array( "paymentmethod" => "payza" ), array( "paymentmethod" => "alertpay" ));
    update_query("tblinvoiceitems", array( "paymentmethod" => "payza" ), array( "paymentmethod" => "alertpay" ));
    update_query("tblinvoices", array( "paymentmethod" => "payza" ), array( "paymentmethod" => "alertpay" ));
    update_query("tblorders", array( "paymentmethod" => "payza" ), array( "paymentmethod" => "alertpay" ));
    full_query("UPDATE tblproductgroups SET disabledgateways = REPLACE(disabledgateways, 'alertpay', 'payza')");
    update_query("tblpaymentgateways", array( "gateway" => "payza" ), array( "gateway" => "alertpay", "setting" => "email" ));
    update_query("tblpaymentgateways", array( "gateway" => "payza" ), array( "gateway" => "alertpay", "setting" => "securitycode" ));
    update_query("tblpaymentgateways", array( "gateway" => "payza" ), array( "gateway" => "alertpay", "setting" => "apipassword" ));
    update_query("tblpaymentgateways", array( "gateway" => "payza" ), array( "gateway" => "alertpay", "setting" => "testmode" ));
    delete_query("tblpaymentgateways", array( "gateway" => "alertpay" ));
    return $configarray;
}

function payza_link($params)
{
    if( $params["testmode"] == "on" ) 
    {
        $code = "<form action=\"https://sandbox.payza.com/sandbox/payprocess.aspx\" method=\"post\">";
    }
    else
    {
        $code = "<form action=\"https://secure.payza.com/checkout\" method=\"post\">";
    }

    $code .= "<input type=\"hidden\" name=\"ap_purchasetype\" value=\"item\">\n<input type=\"hidden\" name=\"ap_merchant\" value=\"" . $params["email"] . "\">\n<input type=\"hidden\" name=\"ap_itemname\" value=\"" . $params["description"] . "\">\n<input type=\"hidden\" name=\"ap_currency\" value=\"" . $params["currency"] . "\">\n<input type=\"hidden\" name=\"ap_quantity\" value=\"1\">\n<input type=\"hidden\" name=\"ap_description\" value=\"" . $params["description"] . "\">\n<input type=\"hidden\" name=\"ap_amount\" value=\"" . $params["amount"] . "\">\n<input type=\"hidden\" name=\"apc_1\" value= \"" . $params["invoiceid"] . "\">\n<input type=\"hidden\" name=\"ap_returnurl\" value=\"" . $params["returnurl"] . "\">\n<input type=\"hidden\" name=\"ap_cancelurl\" value=\"" . $params["returnurl"] . "\">\n<input type=\"image\" name=\"ap_image\" src=\"https://www.payza.com/images/payza-buy-now.png\"/>\n</form>";
    return $code;
}

function payza_refund($params)
{
    if( $params["testmode"] == "on" ) 
    {
        $url = "https://sandbox.Payza.com/api/api.svc/RefundTransaction";
    }
    else
    {
        $url = "https://api.payza.com/svc/api.svc/RefundTransaction";
    }

    $username = $params["email"];
    $password = $params["apipassword"];
    $testmode = ($params["testmode"] ? "1" : "0");
    $results = "";
    $postdata = "USER=" . urlencode($username) . "&PASSWORD=" . urlencode($password) . "&TRANSACTIONREFERENCE=" . urlencode($params["transid"]) . "&AMOUNT=" . urlencode($params["amount"]) . "&TESTMODE=" . $testmode;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    $data = curl_exec($ch);
    curl_close($ch);
    parse_str($data, $results);
    if( $results["RETURNCODE"] == "100" ) 
    {
        return array( "status" => "success", "transid" => $results["REFERENCENUMBER"], "rawdata" => $results );
    }

    return array( "status" => "failed", "rawdata" => $results );
}



<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

$GATEWAYMODULE["paymexname"] = "paymex";
$GATEWAYMODULE["paymexvisiblename"] = "Paymex";
$GATEWAYMODULE["paymextype"] = "Invoices";
function paymex_activate()
{
    defineGatewayField("paymex", "text", "authcode", "", "Business ID", "40", "This your unique business ID given to you by Paymex.");
    defineGatewayField("paymex", "yesno", "testmode", "", "Test Mode", "", "");
}

function paymex_link($params)
{
    $code = "<form action=\"https://secure.paymex.co.nz/Process.aspx\" method=\"post\">";
    $code .= "<input type=\"hidden\" name=\"business\" value=\"" . $params["authcode"] . "\">";
    $code .= "<input type=\"hidden\" name=\"item_name\" value=\"" . $params["description"] . "\">";
    $code .= "<input type=\"hidden\" name=\"item_number\" value=\"" . $params["invoiceid"] . "\">";
    $code .= "<input type=\"hidden\" name=\"item_qty\" value=\"1\">";
    $code .= "<input type=\"hidden\" name=\"amount\" value=\"" . $params["amount"] . "\">";
    $code .= "<input type=\"hidden\" name=\"client_ref\" value=\"INV" . $params["invoiceid"] . "\">";
    $code .= "<input type=\"hidden\" name=\"retail_ref\" value=\"INV" . $params["invoiceid"] . "\">";
    $code .= "<input type=\"hidden\" name=\"return\" value=\"" . $params["systemurl"] . "/modules/gateways/callback/paymex.php?xresp=1&xinv=" . $params["invoiceid"] . "\">";
    $code .= "<input type=\"hidden\" name=\"return_cancel\" value=\"" . $params["systemurl"] . "\">";
    $code .= "<input type=\"hidden\" name=\"currency_code\" value=\"" . $params["currency"] . "\">";
    $code .= "<input type=\"hidden\" name=\"first_name\" value=\"" . $params["clientdetails"]["firstname"] . "\">";
    $code .= "<input type=\"hidden\" name=\"last_name\" value=\"" . $params["clientdetails"]["lastname"] . "\">";
    $code .= "<input type=\"hidden\" name=\"address1\" value=\"" . $params["clientdetails"]["address1"] . "\">";
    $code .= "<input type=\"hidden\" name=\"address2\" value=\"" . $params["clientdetails"]["address2"] . "\">";
    $code .= "<input type=\"hidden\" name=\"suburb\" value=\"" . $params["clientdetails"]["state"] . "\">";
    $code .= "<input type=\"hidden\" name=\"city\" value=\"" . $params["clientdetails"]["city"] . "\">";
    $code .= "<input type=\"hidden\" name=\"postcode\" value=\"" . $params["clientdetails"]["postcode"] . "\">";
    $code .= "<input type=\"hidden\" name=\"country\" value=\"" . $params["clientdetails"]["country"] . "\">";
    $code .= "<input type=\"hidden\" name=\"email\" value=\"" . $params["clientdetails"]["email"] . "\">";
    $code .= "<input type=\"hidden\" name=\"phone\" value=\"" . $params["clientdetails"]["phone"] . "\">";
    if( $params["testmode"] == "on" ) 
    {
        $code .= "<input type=\"hidden\" name=\"test_mode\" value=\"1\">";
    }

    $code .= "<input type=\"submit\" value=\"" . $params["langpaynow"] . "\">";
    $code .= "</form>";
    return $code;
}



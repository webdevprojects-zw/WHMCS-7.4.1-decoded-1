<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("getClientsDetails") ) 
{
    require(ROOTDIR . "/includes/clientfunctions.php");
}

$where = array(  );
if( $clientid ) 
{
    $where["id"] = $clientid;
}
else
{
    if( $email ) 
    {
        $where["email"] = $email;
    }
    else
    {
        $apiresults = array( "result" => "error", "message" => "Either clientid Or email Is Required" );
        return NULL;
    }

}

$result = select_query("tblclients", "id", $where);
$data = mysql_fetch_array($result);
$clientid = $data["id"];
if( !$clientid ) 
{
    $apiresults = array( "result" => "error", "message" => "Client Not Found" );
}
else
{
    $clientsdetails = getClientsDetails($clientid);
    unset($clientsdetails["model"]);
    $currency_result = full_query("SELECT code FROM tblcurrencies WHERE id=" . (int) $clientsdetails["currency"]);
    $currency = mysql_fetch_assoc($currency_result);
    $clientsdetails["currency_code"] = $currency["code"];
    $apiresults = array_merge(array( "result" => "success" ), $clientsdetails);
    $userRequestedResponseType = (is_object($request) ? $request->getResponseFormat() : NULL);
    if( is_null($userRequestedResponseType) || WHMCS\Api\ApplicationSupport\Http\ResponseFactory::isTypeHighlyStructured($userRequestedResponseType) ) 
    {
        $apiresults["client"] = $clientsdetails;
        if( $stats || $userRequestedResponseType == WHMCS\Api\ApplicationSupport\Http\ResponseFactory::RESPONSE_FORMAT_XML ) 
        {
            $apiresults["stats"] = getClientsStats($clientid);
        }

    }

}



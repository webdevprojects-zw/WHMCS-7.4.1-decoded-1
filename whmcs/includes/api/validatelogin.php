<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

$_SESSION["adminid"] = "";
$password2 = (string) App::getFromRequest("password2");
$email = (string) App::getFromRequest("email");
$password2 = WHMCS\Input\Sanitize::decode($password2);
$authentication = new WHMCS\Authentication\Client($email, $password2);
if( $authentication->verifyFirstFactor() ) 
{
    $user = $authentication->getUser();
    $apiresults = array( "result" => "success", "userid" => $user->id );
    if( $user instanceof WHMCS\User\Client\Contact ) 
    {
        $apiresults["contactid"] = $user->id;
        $apiresults["userid"] = $user->clientId;
    }

    if( !$authentication->needsSecondFactorToFinalize() ) 
    {
        $apiresults["passwordhash"] = WHMCS\Authentication\Client::generateClientLoginHash($user->clientId, $user->billingContactId, $user->passwordHash);
        $apiresults["twoFactorEnabled"] = false;
    }
    else
    {
        $apiresults["twoFactorEnabled"] = true;
    }

}
else
{
    $apiresults = array( "result" => "error", "message" => "Email or Password Invalid" );
}



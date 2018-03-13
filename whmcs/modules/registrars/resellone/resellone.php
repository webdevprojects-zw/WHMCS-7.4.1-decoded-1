<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("opensrs_GetConfigArray") ) 
{
    include_once(ROOTDIR . "/modules/registrars/opensrs/opensrs.php");
}

function resellone_getConfigArray()
{
    $configArray = opensrs_getConfigArray();
    $configArray["FriendlyName"]["Value"] = "ResellOne";
    return $configArray;
}

function resellone_GetNameservers(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_GetNameservers($params, $O, "resellone");
}

function resellone_SaveNameservers(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_SaveNameservers($params, $O, "resellone");
}

function resellone_GetRegistrarLock(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_GetRegistrarLock($params, $O, "resellone");
}

function resellone_SaveRegistrarLock(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_SaveRegistrarLock($params, $O, "resellone");
}

function resellone_RegisterDomain(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_RegisterDomain($params, $O, "resellone");
}

function resellone_TransferDomain(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_TransferDomain($params, $O, "resellone");
}

function resellone_RenewDomain(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_RenewDomain($params, $O, "resellone");
}

function resellone_GetContactDetails(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_GetContactDetails($params, $O, "resellone");
}

function resellone_SaveContactDetails(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_SaveContactDetails($params, $O, "resellone");
}

function resellone_GetEPPCode(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_GetEPPCode($params, $O, "resellone");
}

function resellone_RegisterNameserver(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_RegisterNameserver($params, $O, "resellone");
}

function resellone_DeleteNameserver(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_DeleteNameserver($params, $O, "resellone");
}

function resellone_ModifyNameserver(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_ModifyNameserver($params, $O, "resellone");
}

function resellone_Sync(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_Sync($params, $O, "resellone");
}

function resellone_TransferSync(array $params)
{
    try
    {
        $O = resellone_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
    }
    catch( Exception $e ) 
    {
        return array( "error" => $e->getMessage() );
    }
    return opensrs_TransferSync($params, $O, "resellone");
}

function resellone_AdminDomainsTabFields(array $params)
{
    return opensrs_AdminDomainsTabFields($params);
}

function resellone_AdminDomainsTabFieldsSave(array $params)
{
    return opensrs_AdminDomainsTabFieldsSave($params);
}

function resellone_Connect($username, $privateKey, $testMode = false)
{
    $mode = "live";
    if( $testMode ) 
    {
        $mode = "test";
    }

    require_once(dirname(__FILE__) . "/resellone_base.php");
    if( !class_exists("PEAR") ) 
    {
        $error = "OpenSRS/ResellOne Class Files Missing. Visit <a href=\"http://docs.whmcs.com/" . "OpenSRS#Additional_Registrar_Module_Files_Requirement\" target=\"_blank\">" . "http://docs.whmcs.com/OpenSRS#Additional_Registrar_Module_Files_Requirement</a> to resolve";
        throw new Exception($error);
    }

    $connection = new resellone_base($mode, "XCP", $username, $privateKey);
    return $connection;
}



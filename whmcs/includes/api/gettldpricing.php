<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("getTLDList") ) 
{
    require(ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "domainfunctions.php");
}

$currencyId = (int) App::getFromRequest("currencyid");
$userId = (int) App::getFromRequest("clientid");
$clientGroupId = 0;
if( $userId ) 
{
    $client = WHMCS\User\Client::find($userId);
    $userId = $client->id;
    $currencyId = $client->currencyId;
    $clientGroupId = $client->groupId;
}

$currency = getCurrency("", $currencyId);
$pricing = array(  );
$result = WHMCS\Database\Capsule::table("tblpricing")->whereIn("type", array( "domainregister", "domaintransfer", "domainrenew" ))->where("currency", $currency["id"])->where("tsetupfee", 0)->get();
foreach( $result as $data ) 
{
    $pricing[$data->relid][$data->type] = get_object_vars($data);
}
if( $clientGroupId ) 
{
    $result2 = WHMCS\Database\Capsule::table("tblpricing")->whereIn("type", array( "domainregister", "domaintransfer", "domainrenew" ))->where("currency", $currency["id"])->where("tsetupfee", $clientGroupId)->get();
    foreach( $result2 as $data ) 
    {
        $pricing[$data->relid][$data->type] = get_object_vars($data);
    }
}

$tldIds = array(  );
$tldGroups = array(  );
$tldAddons = array(  );
$result = WHMCS\Database\Capsule::table("tbldomainpricing")->get(array( "id", "extension", "dnsmanagement", "emailforwarding", "idprotection", "group" ));
foreach( $result as $data ) 
{
    $ext = ltrim($data->extension, ".");
    $tldIds[$ext] = $data->id;
    $tldGroups[$ext] = ($data->group != "" && $data->group != "none" ? $data->group : "");
    $tldAddons[$ext] = array( "dns" => (bool) $data->dnsmanagement, "email" => (bool) $data->emailforwarding, "idprotect" => (bool) $data->idprotection );
}
$tldList = getTLDList();
$periods = array( "msetupfee" => 1, "qsetupfee" => 2, "ssetupfee" => 3, "asetupfee" => 4, "bsetupfee" => 5, "monthly" => 6, "quarterly" => 7, "semiannually" => 8, "annually" => 9, "biennially" => 10 );
$tldList = array_map(function($value)
{
    return ltrim($value, ".");
}

, $tldList);
$categories = array(  );
$result = WHMCS\Database\Capsule::table("tbltlds")->join("tbltld_category_pivot", "tbltld_category_pivot.tld_id", "=", "tbltlds.id")->join("tbltld_categories", "tbltld_categories.id", "=", "tbltld_category_pivot.category_id")->whereIn("tld", $tldList)->get();
foreach( $result as $data ) 
{
    $categories[$data->tld][] = $data->category;
}
$usedTlds = array_keys($categories);
$missedTlds = array_values(array_filter($tldList, function($key) use ($usedTlds)
{
    return !in_array($key, $usedTlds);
}

));
if( $missedTlds ) 
{
    foreach( $missedTlds as $missedTld ) 
    {
        $categories[$missedTld][] = "Other";
    }
}

$apiresults = array( "result" => "success", "currency" => $currency );
foreach( $tldList as $tld ) 
{
    $tldId = $tldIds[$tld];
    $apiresults["pricing"][$tld]["categories"] = $categories[$tld];
    $apiresults["pricing"][$tld]["addons"] = $tldAddons[$tld];
    $apiresults["pricing"][$tld]["group"] = $tldGroups[$tld];
    foreach( array( "domainregister", "domaintransfer", "domainrenew" ) as $type ) 
    {
        foreach( $pricing[$tldId][$type] as $key => $price ) 
        {
            if( array_key_exists($key, $periods) && ($type == "domainregister" && 0 <= $price || $type == "domaintransfer" && 0 < $price || $type == "domainrenew" && 0 < $price) ) 
            {
                $apiresults["pricing"][$tld][str_replace("domain", "", $type)][$periods[$key]] = $price;
            }

        }
    }
}


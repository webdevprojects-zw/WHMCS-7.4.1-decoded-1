<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("getCustomFields") ) 
{
    require(ROOTDIR . "/includes/customfieldfunctions.php");
}

if( !function_exists("getCartConfigOptions") ) 
{
    require(ROOTDIR . "/includes/configoptionsfunctions.php");
}

global $currency;
$currency = getCurrency();
$pid = $whmcs->get_req_var("pid");
$gid = $whmcs->get_req_var("gid");
$module = $whmcs->get_req_var("module");
$where = array(  );
if( $pid ) 
{
    if( is_numeric($pid) ) 
    {
        $where[] = "id=" . (int) $pid;
    }
    else
    {
        $where[] = "id IN (" . db_escape_string($pid) . ")";
    }

}

if( $gid ) 
{
    $where[] = "gid=" . (int) $gid;
}

if( $module ) 
{
    $where[] = "servertype='" . db_escape_string($module) . "'";
}

$result = select_query("tblproducts", "", implode(" AND ", $where));
$apiresults = array( "result" => "success", "totalresults" => mysql_num_rows($result) );
while( $data = mysql_fetch_array($result) ) 
{
    $pid = $data["id"];
    $productarray = array( "pid" => $data["id"], "gid" => $data["gid"], "type" => $data["type"], "name" => $data["name"], "description" => $data["description"], "module" => $data["servertype"], "paytype" => $data["paytype"] );
    if( $language = $whmcs->get_req_var("language") ) 
    {
        $productarray["translated_name"] = WHMCS\Product\Product::getProductName($data["id"], $data["name"], $language);
        $productarray["translated_description"] = WHMCS\Product\Product::getProductDescription($data["id"], $data["description"], $language);
    }

    if( $data["stockcontrol"] ) 
    {
        $productarray["stockcontrol"] = "true";
        $productarray["stocklevel"] = $data["qty"];
    }

    $result2 = select_query("tblpricing", "tblcurrencies.code,tblcurrencies.prefix,tblcurrencies.suffix,tblpricing.msetupfee,tblpricing.qsetupfee,tblpricing.ssetupfee,tblpricing.asetupfee,tblpricing.bsetupfee,tblpricing.tsetupfee,tblpricing.monthly,tblpricing.quarterly,tblpricing.semiannually,tblpricing.annually,tblpricing.biennially,tblpricing.triennially", array( "type" => "product", "relid" => $pid ), "code", "ASC", "", "tblcurrencies ON tblcurrencies.id=tblpricing.currency");
    while( $data = mysql_fetch_assoc($result2) ) 
    {
        $code = $data["code"];
        unset($data["code"]);
        $productarray["pricing"][$code] = $data;
    }
    $customfieldsdata = array(  );
    $customfields = getCustomFields("product", $pid, "", "", "on");
    foreach( $customfields as $field ) 
    {
        $customfieldsdata[] = array( "id" => $field["id"], "name" => $field["name"], "description" => $field["description"], "required" => $field["required"] );
    }
    $productarray["customfields"]["customfield"] = $customfieldsdata;
    $configoptiondata = array(  );
    $configurableoptions = getCartConfigOptions($pid, "", "", "", true);
    foreach( $configurableoptions as $option ) 
    {
        $options = array(  );
        foreach( $option["options"] as $op ) 
        {
            $pricing = array(  );
            $result4 = select_query("tblpricing", "code,msetupfee,qsetupfee,ssetupfee,asetupfee,bsetupfee,tsetupfee,monthly,quarterly,semiannually,annually,biennially,triennially", array( "type" => "configoptions", "relid" => $op["id"] ), "", "", "", "tblcurrencies ON tblcurrencies.id=tblpricing.currency");
            while( $oppricing = mysql_fetch_assoc($result4) ) 
            {
                $currcode = $oppricing["code"];
                unset($oppricing["code"]);
                $pricing[$currcode] = $oppricing;
            }
            $options["option"][] = array( "id" => $op["id"], "name" => $op["name"], "rawName" => $op["rawName"], "recurring" => $op["recurring"], "required" => $op["required"], "pricing" => $pricing );
        }
        $configoptiondata[] = array( "id" => $option["id"], "name" => $option["optionname"], "type" => $option["optiontype"], "options" => $options );
    }
    $productarray["configoptions"]["configoption"] = $configoptiondata;
    $apiresults["products"]["product"][] = $productarray;
}
$responsetype = "xml";


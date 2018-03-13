<?php 
function affiliateActivate($userid)
{
    global $CONFIG;
    $result = select_query("tblclients", "currency", array( "id" => $userid ));
    $data = mysql_fetch_array($result);
    $clientcurrency = $data["currency"];
    $bonusdeposit = convertCurrency($CONFIG["AffiliateBonusDeposit"], 1, $clientcurrency);
    $result = select_query("tblaffiliates", "id", array( "clientid" => $userid ));
    $data = mysql_fetch_array($result);
    $affiliateid = $data["id"];
    if( !$affiliateid ) 
    {
        $affiliateid = insert_query("tblaffiliates", array( "date" => "now()", "clientid" => $userid, "balance" => $bonusdeposit ));
    }

    logActivity("Activated Affiliate Account - Affiliate ID: " . $affiliateid . " - User ID: " . $userid, $userid);
    run_hook("AffiliateActivation", array( "affid" => $affiliateid, "userid" => $userid ));
}



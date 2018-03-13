<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("WHM Import Script");
$aInt->title = "Import Domains from cPanel/WHM";
$aInt->sidebar = "utilities";
$aInt->icon = "import";
$aInt->helplink = "cPanel/WHM Import";
$server = (int) $whmcs->get_req_var("server");
ob_start();
$packagesArr = array(  );
$result = select_query("tblproducts", "id,configoption1", array( "servertype" => "cpanel" ));
while( $data = mysql_fetch_array($result) ) 
{
    list($pid, $pname) = $data;
    $packagesArr[$pname] = $pid;
    $result2 = select_query("tblservers", "username", array( "type" => "cpanel" ));
    while( $data = mysql_fetch_array($result2) ) 
    {
        $packagesArr[$data["username"] . "_" . $pname] = $pid;
    }
}
if( $step == "" ) 
{
    echo "\n<p>This WHM Import Script can save you hours of time.  It will automatically import domains and usernames from your cPanel Server to save you needing to enter them manually into WHMCS.</p>\n<p>You must make sure you have a package setup for each Package Name in use on the server by the accounts you are going to import before running the import as WHMCS will attempt to automatically assign accounts to a package based on this.  Any accounts where a package is not found for them will be reported at the end for entering manually.</p>\n<p>Begin by choosing the cPanel server you want to import from below, then click Get Account List.</p>\n\n<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "?step=2\">\n    <div class=\"well\">\n        <select name=\"server\" onChange=\"submit()\" class=\"form-control select-inline\">";
    $result = select_query("tblservers", "", array( "type" => "cpanel" ), "name", "ASC");
    while( $data = mysql_fetch_array($result) ) 
    {
        $serverid = $data["id"];
        $servername = $data["name"];
        $activeserver = $data["active"];
        $servermaxaccounts = $data["maxaccounts"];
        $disabled = $data["disabled"];
        if( $disabled ) 
        {
            $servername .= " (" . $aInt->lang("emailtpls", "disabled") . ")";
        }

        $result2 = select_query("tblhosting", "COUNT(*)", "server='" . $serverid . "' AND (domainstatus='Active' OR domainstatus='Suspended')");
        $data = mysql_fetch_array($result2);
        $servernumaccounts = $data[0];
        echo "<option value=\"" . $serverid . "\"";
        if( $server == $serverid ) 
        {
            echo " selected";
        }

        echo ">" . $servername . " (" . $servernumaccounts . "/" . $servermaxaccounts . " " . $aInt->lang("fields", "accounts") . ")</option>";
    }
    echo "</select>\n        <input type=\"submit\" value=\"Get Account List\" class=\"btn btn-primary\">\n    </div>\n</form>\n\n";
}
else
{
    if( $step == "2" ) 
    {
        check_token("WHMCS.admin.default");
        echo "\n<p>The following accounts were found on the server.  Tick the boxes next to the accounts you wish to import.</p>\n\n";
        $result = select_query("tblservers", "", array( "id" => $server ));
        $data = mysql_fetch_array($result);
        $servertype = $data["type"];
        $host = $data["ipaddress"];
        $user = WHMCS\Input\Sanitize::decode($data["username"]);
        $pass = WHMCS\Input\Sanitize::decode(decrypt($data["password"]));
        $accesshash = WHMCS\Input\Sanitize::decode($data["accesshash"]);
        $usessl = $data["secure"];
        $request = "/json-api/listaccts";
        $cleanaccesshash = preg_replace("'(\r|\n)'", "", $accesshash);
        if( $cleanaccesshash ) 
        {
            $authstr = "WHM " . $user . ":" . $cleanaccesshash;
        }
        else
        {
            $authstr = "Basic " . base64_encode($user . ":" . $pass);
        }

        $ch = curl_init();
        if( $usessl ) 
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_URL, "https://" . $host . ":2087" . $request);
        }
        else
        {
            curl_setopt($ch, CURLOPT_URL, "http://" . $host . ":2086" . $request);
        }

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $curlheaders[0] = "Authorization: " . $authstr;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlheaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, 150);
        $data = curl_exec($ch);
        if( curl_errno($ch) ) 
        {
            $curlerror = curl_error($ch) . " (Code " . curl_errno($ch) . ") - Check IP/Blocked Firewall Ports";
        }

        curl_close($ch);
        if( $debug_output ) 
        {
            echo "<textarea rows=10 cols=120>Request: " . $request . "\n\nData: " . $data . "</textarea>";
        }

        $accountData = json_decode($data, true);
        if( $curlerror ) 
        {
            infoBox("Connection Error", $curlerror);
            echo $infobox;
        }
        else
        {
            $jquerycode .= "\$(\"#checkall\").change(function () {\n    \$(\".checkall\").prop(\"checked\", this.checked);\n})";
            echo "\n<form method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?step=2\" id=\"frmMyUserOnly\">\n<input type=\"hidden\" name=\"server\" value=\"";
            echo $server;
            echo "\" />\n<input type=\"hidden\" name=\"useronly\" value=\"true\" />\n<input type=\"hidden\" name=\"hide\" value=\"";
            echo $hide;
            echo "\" />\n</form>\n\n<form method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?step=2\" id=\"frmHideExisting\">\n<input type=\"hidden\" name=\"server\" value=\"";
            echo $server;
            echo "\" />\n<input type=\"hidden\" name=\"hide\" value=\"true\" />\n</form>\n\n<form method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?step=2\" id=\"frmStatusMismatch\">\n<input type=\"hidden\" name=\"server\" value=\"";
            echo $server;
            echo "\" />\n<input type=\"hidden\" name=\"showcancelled\" value=\"true\" />\n</form>\n\n<form method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?step=3\" name=\"importForm\">\n\n<input type=\"hidden\" name=\"server\" value=\"";
            echo $server;
            echo "\" />\n\n<p class=\"text-center\">\n    <input type=\"button\" value=\"Show Domains for my Username Only\" onClick=\"\$('#frmMyUserOnly').submit()\" class=\"btn btn-default\">\n    <input type=\"button\" value=\"Hide Domains Already in WHMCS\" onClick=\"\$('#frmHideExisting').submit()\" class=\"btn btn-default\">\n    <input type=\"button\" value=\"Show only Domains with Status Mismatch\" onClick=\"\$('#frmStatusMismatch').submit()\" class=\"btn btn-default\">\n    <input type=\"submit\" value=\"Import\" class=\"btn btn-primary\">\n</p>\n\n<table cellspacing=1 bgcolor=#cccccc width=750 align=center>\n<tr bgcolor=#efefef style=\"font-weight:bold;text-align:center;\"><td width=20><input type=\"checkbox\" id=\"checkall\"></td><td>Domain</td><td>Username</td><td>Owner</td><td>Package</td><td>Created</td></tr>\n\n";
            $count = 0;
            foreach( $accountData["acct"] as $key => $account ) 
            {
                $domain = trim($account["domain"]);
                $email = $account["email"];
                $owner = $account["owner"];
                $plan = urldecode($account["plan"]);
                $username = $account["user"];
                $created = $account["unix_startdate"];
                if( trim($created) ) 
                {
                    $created = @date("d M Y", $created);
                }

                $result = select_query("tblhosting", "id,domainstatus,packageid,server", array( "domain" => $domain, "domainstatus" => "Active" ));
                $data = mysql_fetch_array($result);
                $domaincount = $data[0];
                $domainstatus = $data["domainstatus"];
                $packageid = $data["packageid"];
                $serverid = $data["server"];
                $bgcolor = ($domaincount ? "#ffff95" : "#ffffff");
                if( !$domaincount ) 
                {
                    $result = select_query("tblhosting", "id,domainstatus,packageid,server", array( "domain" => $domain ));
                    $data = mysql_fetch_array($result);
                    $domaincount = $data[0];
                    $domainstatus = $data["domainstatus"];
                    $packageid = $data["packageid"];
                    $serverid = $data["server"];
                    $bgcolor = ($domaincount ? "#FFFF95" : "#ffffff");
                }

                $pid = $packagesArr[$plan];
                if( !$pid ) 
                {
                    $bgcolor = "#CCFF66";
                }

                if( $pid && $packageid && $pid != $packageid ) 
                {
                    $bgcolor = "#e8e8e8";
                }

                if( $serverid && $serverid != $server ) 
                {
                    $bgcolor = "#A8C6F7";
                }

                if( $domainstatus && ($domainstatus == "Cancelled" || $domainstatus == "Terminated" || $domainstatus == "Fraud") ) 
                {
                    $bgcolor = "#FF9797";
                }

                if( $hide && !$domaincount || !$hide ) 
                {
                    if( $useronly ) 
                    {
                        if( $owner == $user ) 
                        {
                            echo "<tr bgcolor=" . $bgcolor . "><td width=\"20\" align=\"center\"><input type=\"checkbox\" name=\"selectedaccounts[]\" value=\"" . $username . "\" class=\"checkall\"></td><td>" . $domain . "</td><td align=center>" . $username . "</td><td align=center>" . $owner . "</td><td align=center>" . $plan . "</td><td align=center>" . $created . "</td></tr>";
                        }

                    }
                    else
                    {
                        if( $showcancelled ) 
                        {
                            if( $bgcolor == "#FF9797" ) 
                            {
                                echo "<tr bgcolor=" . $bgcolor . "><td></td><td>" . $domain . "</td><td align=center>" . $username . "</td><td align=center>" . $owner . "</td><td align=center>" . $plan . "</td><td align=center>" . $created . "</td></tr>";
                            }

                        }
                        else
                        {
                            echo "<tr bgcolor=" . $bgcolor . "><td width=\"20\" align=\"center\"><input type=\"checkbox\" name=\"selectedaccounts[]\" value=\"" . $username . "\" class=\"checkall\"></td><td>" . $domain . " " . $domaincount . "</td><td align=center>" . $username . "</td><td align=center>" . $owner . "</td><td align=center>" . $plan . "</td><td align=center>" . $created . "</td></tr>";
                        }

                    }

                }

                $count++;
            }
            if( !$count ) 
            {
                echo "<tr bgcolor=#ffffff><td colspan=6 align=center>No Accounts Found - Check Server Login Details if there should be some</td></tr>";
            }

            echo "</table>\n\n<br />\n\n<div class=\"text-center\">\n\n    <div style=\"display:inline-block;margin:0 5px;width:14px;height:14px;border:1px solid #000;background-color:#FFFF95;\"></div>\n    Indicates a Domain Already in WHMCS and in Sync\n\n    <div style=\"display:inline-block;margin:0 5px;width:14px;height:14px;border:1px solid #000;background-color:#e8e8e8;\"></div>\n    Indicates in WHMCS but set to a different product\n\n    <div style=\"display:inline-block;margin:0 5px;width:14px;height:14px;border:1px solid #000;background-color:#A8C6F7;\"></div>\n    Indicates in WHMCS but set to a different server\n\n    <div style=\"display:inline-block;margin:0 5px;width:14px;height:14px;border:1px solid #000;background-color:#FF9797;\"></div>\n    Indicates in WHMCS but set to Cancelled\n\n    <div style=\"display:inline-block;margin:0 5px;width:14px;height:14px;border:1px solid #000;background-color:#CCFF66;\"></div>\n    Indicates a Package that doesn't exist in WHMCS so cannot be imported\n\n</div>\n\n<br />\n\n<p class=\"text-center\">\n    <input type=\"button\" value=\"Show Domains for my Username Only\" onClick=\"\$('#frmMyUserOnly').submit()\" class=\"btn btn-default\">\n    <input type=\"button\" value=\"Hide Domains Already in WHMCS\" onClick=\"\$('#frmHideExisting').submit()\" class=\"btn btn-default\">\n    <input type=\"button\" value=\"Show only Domains with Status Mismatch\" onClick=\"\$('#frmStatusMismatch').submit()\" class=\"btn btn-default\">\n    <input type=\"submit\" value=\"Import\" class=\"btn btn-primary\">\n</p>\n\n<p class=\"text-center\">\n    <label class=\"checkbox-inline\">\n        <input type=\"checkbox\" name=\"createdomains\" />\n        Tick this box to create domain entries for imported accounts (this assumes all clients domains are registered through you)\n    </label>\n</p>\n\n</form>\n\n";
        }

    }
    else
    {
        if( $step == "3" ) 
        {
            check_token("WHMCS.admin.default");
            $result = select_query("tblpaymentgateways", "gateway", "", "gateway", "ASC");
            $data = mysql_fetch_array($result);
            $defaultgateway = $data["gateway"];
            $result = select_query("tblservers", "", array( "id" => $server ));
            $data = mysql_fetch_array($result);
            $servertype = $data["type"];
            $host = $data["ipaddress"];
            $user = WHMCS\Input\Sanitize::decode($data["username"]);
            $pass = WHMCS\Input\Sanitize::decode(decrypt($data["password"]));
            $accesshash = WHMCS\Input\Sanitize::decode($data["accesshash"]);
            $usessl = $data["secure"];
            $request = "/json-api/listaccts";
            $cleanaccesshash = preg_replace("'(\r|\n)'", "", $accesshash);
            if( $cleanaccesshash ) 
            {
                $authstr = "WHM " . $user . ":" . $cleanaccesshash;
            }
            else
            {
                $authstr = "Basic " . base64_encode($user . ":" . $pass);
            }

            $ch = curl_init();
            if( $usessl ) 
            {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_URL, "https://" . $host . ":2087" . $request);
            }
            else
            {
                curl_setopt($ch, CURLOPT_URL, "http://" . $host . ":2086" . $request);
            }

            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $curlheaders[0] = "Authorization: " . $authstr;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $curlheaders);
            curl_setopt($ch, CURLOPT_TIMEOUT, 150);
            $data = curl_exec($ch);
            if( curl_errno($ch) ) 
            {
                $curlerror = curl_error($ch) . " (Code " . curl_errno($ch) . ") - Check IP/Blocked Firewall Ports";
            }

            curl_close($ch);
            if( $debug_output ) 
            {
                echo "<textarea rows=10 cols=120>Request: " . $request . "\n\nData: " . $data . "</textarea>";
            }

            $accountData = json_decode($data, true);
            if( $curlerror ) 
            {
                infoBox("Connection Error", $curlerror);
                echo $infobox;
            }
            else
            {
                $successcount = 0;
                $failcount = 0;
                foreach( $accountData["acct"] as $account ) 
                {
                    $domain = $account["domain"];
                    $email = $account["email"];
                    $owner = $account["owner"];
                    $plan = urldecode($account["plan"]);
                    $user = $account["user"];
                    $created = $account["unix_startdate"];
                    if( $created ) 
                    {
                        $created = @date("Y-m-d", $created);
                    }

                    if( in_array($user, $selectedaccounts) ) 
                    {
                        $pid = $packagesArr[$plan];
                        if( !$pid ) 
                        {
                            $failcount++;
                            $faillist .= (string) $failcount . ". " . $domain . "<br>";
                        }
                        else
                        {
                            $hasher = new WHMCS\Security\Hash\Password();
                            $str = $hasher->hash(generateFriendlyPassword());
                            $userid = insert_query("tblclients", array( "firstname" => $user, "lastname" => "Owner", "email" => $email, "password" => $str, "status" => "Active", "datecreated" => $created ));
                            insert_query("tblhosting", array( "userid" => $userid, "regdate" => $created, "domain" => $domain, "server" => $server, "paymentmethod" => $defaultgateway, "billingcycle" => "Free Account", "domainstatus" => "Active", "username" => $user, "packageid" => $pid, "notes" => "Imported using WHM Import Script - Payment Method, Pricing, Billing Cycle & Next Due Date may be incorrect" ));
                            if( $createdomains ) 
                            {
                                $domainparts = explode(".", $arr[1], 2);
                                $tld = $domainparts[1];
                                $result = select_query("tbldomainpricing", "", array( "extension" => "." . $tld ), "registrationperiod", "ASC");
                                $data = mysql_fetch_array($result);
                                $regperiod = $data["registrationperiod"];
                                $firstpaymentamount = $data["register"];
                                $recurringamount = $data["renew"];
                                insert_query("tbldomains", array( "userid" => $userid, "registrationdate" => $created, "domain" => $domain, "firstpaymentamount" => $firstpaymentamount, "recurringamount" => $recurringamount, "registrationperiod" => $regperiod, "paymentmethod" => $defaultgateway, "expirydate" => $nextdue, "status" => "Active", "additionalnotes" => "Imported using WHM Import Script - Payment Method, Amount, Expiry & Next Due Date may be incorrect" ));
                            }

                            $successcount++;
                            $successlist .= (string) $successcount . ". " . $domain . "<br>";
                        }

                    }

                }
                echo "\n<p><b>Import Results</b></p>\n<p style=\"color:#66CC00;\"><b>";
                echo $successcount;
                echo " Succeeded</b></p>\n<p>";
                echo $successlist;
                echo "</p>\n<p style=\"color:#cc0000;\"><b>";
                echo $failcount;
                echo " Failed</b> (Due to Package Name Not Found in WHMCS)</p>\n<p>";
                echo $faillist;
                echo "</p>\n\n";
            }

        }

    }

}

$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();


<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

if( !function_exists("getClientsDetails") ) 
{
    require(ROOTDIR . "/includes/clientfunctions.php");
}

if( !function_exists("saveCustomFields") ) 
{
    require(ROOTDIR . "/includes/customfieldfunctions.php");
}

$whmcs = App::self();
$skipValidation = $whmcs->get_req_var("skipvalidation");
$customFields = $whmcs->get_req_var("customfields");
if( !empty($_POST["clientid"]) ) 
{
    $clientid = $whmcs->get_req_var("clientid");
}
else
{
    $clientid = "";
}

if( $clientemail ) 
{
    $result = select_query("tblclients", "id", array( "email" => $clientemail ));
}
else
{
    $result = select_query("tblclients", "id", array( "id" => $clientid ));
}

$data = mysql_fetch_array($result);
$clientid = $data["id"];
if( !$clientid ) 
{
    $apiresults = array( "result" => "error", "message" => "Client ID Not Found" );
}
else
{
    if( $_POST["email"] ) 
    {
        $result = select_query("tblclients", "id", array( "email" => $_POST["email"], "id" => array( "sqltype" => "NEQ", "value" => $clientid ) ));
        $data = mysql_fetch_array($result);
        $result = select_query("tblcontacts", "id", array( "email" => $_POST["email"], "subaccount" => "1" ));
        $data2 = mysql_fetch_array($result);
        if( $data["id"] || $data2["id"] ) 
        {
            $apiresults = array( "result" => "error", "message" => "Duplicate Email Address" );
            return NULL;
        }

    }

    if( ($whmcs->get_req_var("clearcreditcard") || $whmcs->get_req_var("cardtype")) && !function_exists("updateCCDetails") ) 
    {
        require(ROOTDIR . "/includes/ccfunctions.php");
    }

    $passwordChanged = false;
    if( $_POST["cardtype"] && !$whmcs->get_req_var("clearcreditcard") ) 
    {
        $errorMessage = updateCCDetails(0, $_POST["cardtype"], $_POST["cardnum"], $_POST["cvv"], $_POST["expdate"], $_POST["startdate"], $_POST["issuenumber"]);
        if( $errorMessage ) 
        {
            $apiresults = array( "result" => "error", "message" => strip_tags($errorMessage) );
            return NULL;
        }

    }

    $oldClientsDetails = getClientsDetails($clientid);
    if( isset($_POST["taxexempt"]) ) 
    {
        $_POST["taxexempt"] = ($_POST["taxexempt"] ? 1 : 0);
    }

    if( isset($_POST["latefeeoveride"]) ) 
    {
        $_POST["latefeeoveride"] = ($_POST["latefeeoveride"] ? 1 : 0);
    }

    if( isset($_POST["overideduenotices"]) ) 
    {
        $_POST["overideduenotices"] = ($_POST["overideduenotices"] ? 1 : 0);
    }

    if( isset($_POST["separateinvoices"]) ) 
    {
        $_POST["separateinvoices"] = ($_POST["separateinvoices"] ? 1 : 0);
    }

    if( isset($_POST["disableautocc"]) ) 
    {
        $_POST["disableautocc"] = ($_POST["disableautocc"] ? 1 : 0);
    }

    $updatequery = "";
    $fieldsarray = array( "firstname", "lastname", "companyname", "email", "address1", "address2", "city", "state", "postcode", "country", "phonenumber", "credit", "taxexempt", "notes", "status", "language", "currency", "groupid", "taxexempt", "latefeeoveride", "overideduenotices", "billingcid", "separateinvoices", "disableautocc", "datecreated", "securityqid", "bankname", "banktype", "lastlogin", "ip", "host", "gatewayid" );
    foreach( $fieldsarray as $fieldname ) 
    {
        if( isset($_POST[$fieldname]) ) 
        {
            $updatequery .= (string) $fieldname . "='" . db_escape_string($_POST[$fieldname]) . "',";
        }

    }
    if( $_POST["password2"] ) 
    {
        $hasher = new WHMCS\Security\Hash\Password();
        $updatequery .= sprintf("password='%s',", $hasher->hash(WHMCS\Input\Sanitize::decode($_POST["password2"])));
        $passwordChanged = true;
    }

    if( $_POST["securityqans"] ) 
    {
        $updatequery .= "securityqans='" . encrypt($_POST["securityqans"]) . "',";
    }

    if( $_POST["cardtype"] ) 
    {
        updateCCDetails($clientid, $_POST["cardtype"], $_POST["cardnum"], $_POST["cvv"], $_POST["expdate"], $_POST["startdate"], $_POST["issuenumber"]);
    }

    if( $whmcs->get_req_var("clearcreditcard") ) 
    {
        updateCCDetails($clientid, "", "", "", "", "", "", "", true);
    }

    $fieldsarray = array( "bankcode", "bankacct" );
    foreach( $fieldsarray as $fieldname ) 
    {
        if( isset($_POST[$fieldname]) ) 
        {
            $updatequery .= (string) $fieldname . "=AES_ENCRYPT('" . db_escape_string($_POST[$fieldname]) . "','" . $cchash . "'),";
        }

    }
    $query = "UPDATE tblclients SET " . substr($updatequery, 0, -1) . " WHERE id=" . (int) $clientid;
    $result = full_query($query);
    if( $customFields ) 
    {
        $customFields = safe_unserialize(base64_decode($customFields));
        if( !$skipValidation ) 
        {
            $validate = new WHMCS\Validate();
            $validate->validateCustomFields("client", "", false, $customFields);
            $customFieldsErrors = $validate->getErrors();
            if( count($customFieldsErrors) ) 
            {
                $error = implode(", ", $customFieldsErrors);
                $apiresults = array( "result" => "error", "message" => $error );
                return NULL;
            }

        }

        saveCustomFields($clientid, $customFields, "client", true);
    }

    if( $paymentmethod ) 
    {
        clientChangeDefaultGateway($clientid, $paymentmethod);
    }

    $newClientsDetails = getClientsDetails($clientid);
    $hookValues = array_merge(array( "userid" => $clientid, "olddata" => $oldClientsDetails ), $newClientsDetails);
    run_hook("ClientEdit", $hookValues);
    $updateFieldsArray = array( "firstname" => "First Name", "lastname" => "Last Name", "companyname" => "Company Name", "email" => "Email Address", "address1" => "Address 1", "address2" => "Address 2", "city" => "City", "state" => "State", "postcode" => "Postcode", "country" => "Country", "phonenumber" => "Phone Number", "securityqid" => "Security Question", "securityqans" => "Security Question Answer", "billingcid" => "Billing Contact", "groupid" => "Client Group", "language" => "Language", "currency" => "Currency", "status" => "Status", "defaultgateway" => "Default Payment Method" );
    $updatedTickBoxArray = array( "latefeeoveride" => "Late Fees Override", "overideduenotices" => "Overdue Notices", "taxexempt" => "Tax Exempt", "separateinvoices" => "Separate Invoices", "disableautocc" => "Disable CC Processing", "emailoptout" => "Marketing Emails Opt-out", "overrideautoclose" => "Auto Close" );
    $changeList = array(  );
    foreach( $newClientsDetails as $key => $value ) 
    {
        if( !in_array($key, array_merge(array_keys($updateFieldsArray), array_keys($updatedTickBoxArray))) ) 
        {
            continue;
        }

        if( in_array($key, array( "securityqans" )) && $oldClientsDetails[$key] != $value ) 
        {
            $changeList[] = $updateFieldsArray[$key] . " Changed";
            continue;
        }

        if( $key == "securityqid" && $oldClientsDetails[$key] != $value ) 
        {
            if( !$value ) 
            {
                $changeList[] = "Security Question Removed";
            }
            else
            {
                $changeList[] = "Security Question Changed";
            }

            continue;
        }

        if( in_array($key, array_keys($updateFieldsArray)) && $value != $oldClientsDetails[$key] ) 
        {
            $oldValue = $oldClientsDetails[$key];
            $newValue = $value;
            $log = true;
            if( $key == "groupid" ) 
            {
                $oldValue = ($oldValue ? get_query_val("tblclientgroups", "groupname", array( "id" => $oldValue )) : AdminLang::trans("global.none"));
                $newValue = ($newValue ? get_query_val("tblclientgroups", "groupname", array( "id" => $newValue )) : AdminLang::trans("global.none"));
            }
            else
            {
                if( $key == "currency" ) 
                {
                    $oldValue = get_query_val("tblcurrencies", "code", array( "id" => $oldValue ));
                    $newValue = get_query_val("tblcurrencies", "code", array( "id" => $newValue ));
                }
                else
                {
                    if( $key == "securityqid" ) 
                    {
                        $oldValue = decrypt(get_query_val("tbladminsecurityquestions", "question", array( "id" => $oldValue )));
                        $newValue = decrypt(get_query_val("tbladminsecurityquestions", "question", array( "id" => $newValue )));
                        if( $oldValue == $newValue ) 
                        {
                            $log = false;
                        }

                    }

                }

            }

            if( $log ) 
            {
                $changeList[] = $updateFieldsArray[$key] . ": '" . $oldValue . "' to '" . $newValue . "'";
            }

            continue;
        }

        if( in_array($key, array_keys($updatedTickBoxArray)) ) 
        {
            if( $key == "overideduenotices" ) 
            {
                $oldField = ($oldClientsDetails[$key] ? "Disabled" : "Enabled");
                $newField = ($value ? "Disabled" : "Enabled");
            }
            else
            {
                $oldField = ($oldClientsDetails[$key] ? "Enabled" : "Disabled");
                $newField = ($value ? "Enabled" : "Disabled");
            }

            if( $oldField != $newField ) 
            {
                $changeList[] = $updatedTickBoxArray[$key] . ": '" . $oldField . "' to '" . $newField . "'";
            }

            continue;
        }

    }
    if( $passwordChanged ) 
    {
        $changeList[] = "Password Changed";
    }

    if( !count($changeList) ) 
    {
        $changeList[] = "No Changes";
    }

    $changes = implode(", ", $changeList);
    logActivity("Client Profile Modified - " . $changes . " - User ID: " . $clientid, $clientid);
    $apiresults = array( "result" => "success", "clientid" => $clientid );
}



<?php 
require("../../../init.php");
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$GATEWAY = getGatewayVariables("tco");
if( !$GATEWAY["type"] ) 
{
    exit( "Module Not Activated" );
}

if( $GATEWAY["secretword"] ) 
{
    $string_to_hash = $GATEWAY["secretword"] . $GATEWAY["vendornumber"] . $_REQUEST["x_trans_id"] . $_REQUEST["x_amount"];
    $check_key = strtoupper(md5($string_to_hash));
    if( $check_key != $_REQUEST["x_MD5_Hash"] ) 
    {
        logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "MD5 Hash Failure");
        redirSystemURL("action=invoices", "clientarea.php");
    }

}

echo "<html>\n<head>\n<title>" . $CONFIG["CompanyName"] . "</title>\n</head>\n<body>\n<p>Payment Processing Completed. However it may take a while for 2CheckOut fraud verification to complete and the payment to be reflected on your account. Please wait while you are redirected back to the client area...</p>\n";
if( $_POST["x_response_code"] == "1" ) 
{
    $invoiceid = checkCbInvoiceID($_POST["x_invoice_num"], $GATEWAY["paymentmethod"]);
    if( $GATEWAY["skipfraudcheck"] ) 
    {
        echo "<meta http-equiv=\"refresh\" content=\"2;url=" . $CONFIG["SystemURL"] . "/viewinvoice.php?id=" . $invoiceid . "&paymentsuccess=true\">";
    }
    else
    {
        echo "<meta http-equiv=\"refresh\" content=\"2;url=" . $CONFIG["SystemURL"] . "/viewinvoice.php?id=" . $invoiceid . "&pendingreview=true\">";
    }

}
else
{
    logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Unsuccessful");
    echo "<meta http-equiv=\"refresh\" content=\"2;url=" . $CONFIG["SystemURL"] . "/clientarea.php?action=invoices\">";
}

echo "\n</body>\n</html>";


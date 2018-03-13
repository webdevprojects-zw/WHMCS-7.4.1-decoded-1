<?php 
require("../../../init.php");
$whmcs = App::self();
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$invoiceId = (int) App::getFromRequest("invoice_id");
$merchantId = App::getFromRequest("merchant_id");
$transactionId = App::getFromRequest("transaction_id");
$status = App::getFromRequest("status");
$md5sig = App::getFromRequest("md5sig");
$recTransactionId = App::getFromRequest("rec_payment_id");
if( $invoiceId ) 
{
    try
    {
        $invoice = new WHMCS\Invoice($invoiceId);
        $params = $invoice->getGatewayInvoiceParams();
        $payToEmail = App::getFromRequest("pay_to_email");
        $customerEmail = App::getFromRequest("pay_from_email");
        $paymentAmount = App::getFromRequest("mb_amount");
        $paymentCurrency = App::getFromRequest("mb_currency");
        $failedCode = App::getFromRequest("failed_reason_code");
        $amount = App::getFromRequest("amount");
        $currency = App::getFromRequest("currency");
        $md5Secret = strtoupper(md5($params["secretWord"]));
        $validateSig = md5($merchantId . $transactionId . $md5Secret . $paymentAmount . $paymentCurrency . $status);
        if( $status == "-1" ) 
        {
            $validateSig = md5($merchantId . $transactionId . $md5Secret . $status . $recTransactionId);
        }

        if( strtoupper($validateSig) != $md5sig ) 
        {
            throw new WHMCS\Exception\Module\InvalidConfiguration("MD5 Signature Failure");
        }

        $paymentCurrencyId = WHMCS\Database\Capsule::table("tblcurrencies")->where("code", $paymentCurrency)->first();
        if( !$paymentCurrencyId || $paymentCurrencyId != $params["currencyId"] ) 
        {
            throw new WHMCS\Exception\Module\InvalidConfiguration("Unrecognised Currency");
        }

        $model = $params["clientdetails"]["model"];
        if( $model instanceof WHMCS\User\Client\Contact ) 
        {
            $model = $model->client;
        }

        switch( $status ) 
        {
            case -3:
                paymentReversed("Reverse" . $transactionId, $transactionId, $invoiceId, "skrill");
                logTransaction("skrill", $_REQUEST, "Payment Reversed");
                break;
            case -2:
                logTransaction("skrill", $_REQUEST, "Payment Declined", $params);
                break;
            case -1:
                $model->paymentGatewayToken = "";
                $model->save();
                logTransaction("skrill", $_REQUEST, "1-Tap Recurring Cancelled", $params);
                break;
            case 2:
                if( $recTransactionId ) 
                {
                    if( $model->paymentGatewayToken && $model->paymentGatewayToken != $recTransactionId ) 
                    {
                        $postFields = array( "email" => $params["emailAddress"], "password" => md5($params["apiMqiPassword"]), "action" => "cancel_od", "amount" => 0, "trn_id" => $model->paymentGatewayToken );
                        $url = "https://www.skrill.com/app/query.pl";
                        $rawResponse = curlCall($url, $postFields);
                        logTransaction("skrill", array( "response" => $rawResponse, "request" => $postFields ), "Cancel Old 1-Tap", $params);
                    }

                    $model->paymentGatewayToken = $recTransactionId;
                    $model->save();
                }

                $clientCurrency = $params["clientdetails"]["currency"];
                $paymentCurrency = $params["currencyId"];
                if( $paymentCurrency && $clientCurrency != $paymentCurrency ) 
                {
                    $amount = convertCurrency($amount, $paymentCurrency, $clientCurrency);
                    $total = (array_key_exists("baseamount", $params) ? $params["baseamount"] : $params["amount"]);
                    if( $total < $amount + 1 && $amount - 1 < $total ) 
                    {
                        $amount = $total;
                    }

                }

                addInvoicePayment($params["invoiceid"], $transactionId, $amount, 0, "skrill");
                logTransaction("skrill", $_REQUEST, "Success", $params);
                break;
        }
    }
    catch( WHMCS\Exception\Fatal $e ) 
    {
        WHMCS\Terminus::getInstance()->doDie("Module Not Activated");
    }
    catch( WHMCS\Exception\Module\InvalidConfiguration $e ) 
    {
        logTransaction("skrill", $_REQUEST, $e->getMessage());
    }
    catch( Exception $e ) 
    {
        logTransaction("skrill", $_REQUEST, "Error");
    }
    WHMCS\Terminus::getInstance()->doExit();
}



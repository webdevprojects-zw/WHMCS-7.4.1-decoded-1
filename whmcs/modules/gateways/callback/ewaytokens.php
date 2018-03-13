<?php 
require_once(dirname(dirname(dirname(__DIR__))) . "/init.php");
$whmcs->load_function("cc");
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$gateway = getGatewayVariables("ewaytokens");
$accessCode = $whmcs->get_req_var("AccessCode");
$url = "https://api.ewaypayments.com/soap.asmx?WSDL";
if( $gateway["testmode"] ) 
{
    $url = "https://api.sandbox.ewaypayments.com/soap.asmx?WSDL";
}

$systemUrl = App::getSystemUrl();
try
{
    $soapClient = new SoapClient($url, array( "login" => $gateway["apiKey"], "password" => $gateway["apiPass"], "trace" => true, "exceptions" => true ));
    $parameters = array(  );
    $parameters["AccessCode"] = $accessCode;
    $accessCodeResult = $soapClient->GetAccessCodeResult(array( "request" => $parameters ));
    $responseCode = (string) $accessCodeResult->GetAccessCodeResultResult->ResponseCode;
    $transactionStatus = (bool) (int) $accessCodeResult->GetAccessCodeResultResult->TransactionStatus;
    $returnedAccessCode = $accessCodeResult->GetAccessCodeResultResult->AccessCode;
    $transactionAmount = $accessCodeResult->GetAccessCodeResultResult->TotalAmount;
    if( $accessCode == $returnedAccessCode ) 
    {
        $userId = $accessCodeResult->GetAccessCodeResultResult->Customer->Reference;
        $invoiceId = $accessCodeResult->GetAccessCodeResultResult->InvoiceNumber;
        $tokenCustomerId = $accessCodeResult->GetAccessCodeResultResult->TokenCustomerID;
        $transactionId = $accessCodeResult->GetAccessCodeResultResult->TransactionID;
        logTransaction($gateway["paymentmethod"], json_decode(json_encode($accessCodeResult->GetAccessCodeResultResult), true), "GetAccessCodeResult");
        $gateway = getGatewayVariables("ewaytokens", $invoiceId);
        $clientData = $soapClient->DirectCustomerSearch(array( "request" => array( "TokenCustomerID" => $tokenCustomerId ) ));
        logTransaction($gateway["paymentmethod"], json_decode(json_encode($clientData), true), "DirectCustomerSearch");
        $clientData = $clientData->DirectCustomerSearchResult->Customers->DirectTokenCustomer;
        $clientId = $clientData->Reference;
        $cardExpiryDate = (string) $clientData->CardDetails->ExpiryMonth . (string) $clientData->CardDetails->ExpiryYear;
        $client = WHMCS\User\Client::findOrFail($clientId);
        $client->paymentGatewayToken = $tokenCustomerId;
        $client->creditCardLastFourDigits = substr($clientData->CardDetails->Number, -4);
        $client->creditCardType = getCardTypeByCardNumber($clientData->CardDetails->Number);
        $client->creditCardExpiryDate = $client->generateCreditCardEncryptedField($cardExpiryDate);
        $client->save();
        if( $transactionStatus === true ) 
        {
            logTransaction($gateway["paymentmethod"], json_decode(json_encode($accessCodeResult->GetAccessCodeResultResult), true), "Success");
            addInvoicePayment($invoiceId, $transactionId, "", "", "ewaytokens");
            callback3DSecureRedirect($invoiceId, true);
        }
        else
        {
            if( !$transactionAmount ) 
            {
                logTransaction($gateway["paymentmethod"], json_decode(json_encode($accessCodeResult->GetAccessCodeResultResult), true), "Card Updated");
                redir("action=creditcard&success=true", $systemUrl . "/clientarea.php");
            }
            else
            {
                logTransaction($gateway["paymentmethod"], json_decode(json_encode($accessCodeResult->GetAccessCodeResultResult), true), "Declined");
                callback3DSecureRedirect($invoiceId, false);
            }

        }

    }

    logTransaction($gateway["paymentmethod"], json_decode(json_encode($accessCodeResult->GetAccessCodeResultResult), true), "Declined");
}
catch( Exception $e ) 
{
    logTransaction($gateway["paymentmethod"], $e->getMessage(), "Error");
    if( is_object($soapClient) ) 
    {
        logTransaction($gateway["paymentmethod"], $soapClient->__getLastRequest(), "Error");
    }

}
redir("", $systemUrl . "/clientarea.php");


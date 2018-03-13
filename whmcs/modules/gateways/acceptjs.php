<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

function acceptjs_config()
{
    return array( "FriendlyName" => array( "Type" => "System", "Value" => "Authorize.net Accept.js" ), "apiLoginId" => array( "FriendlyName" => "API Login ID", "Type" => "text", "Size" => "20", "Description" => "This can be found by navigating to Account > Security Settings > API Credentials & Keys within your Authorize.net account." ), "transactionKey" => array( "FriendlyName" => "Transaction Key", "Type" => "text", "Size" => "20", "Description" => "This can be found by navigating to Account > Security Settings > API Credentials & Keys within your Authorize.net account." ), "publicKey" => array( "FriendlyName" => "Public Client Key", "Type" => "text", "Size" => "40", "Description" => "This can be found by navigating to Account > Security Settings > Manage Public Client Key within your Authorize.net account." ), "testMode" => array( "FriendlyName" => "Test Mode", "Type" => "yesno" ), "noAccount" => array( "Type" => "info", "Description" => "<div class=\"alert alert-info\" style=\"margin-bottom: 0;\">Don't have an account? <a href=\"https://www.whmcs.com/start-accepting-credit-cards\" class=\"alert-link autoLinked\">Apply for an account for free</a></div>" ) );
}

function acceptjs_capture(array $params = array(  ))
{
    $gatewayId = json_decode(WHMCS\Input\Sanitize::decode($params["gatewayid"]), true);
    $apiUrl = net\authorize\api\constants\ANetEnvironment::PRODUCTION;
    if( $params["testMode"] ) 
    {
        $apiUrl = net\authorize\api\constants\ANetEnvironment::SANDBOX;
    }

    if( (!is_array($gatewayId) || json_last_error() !== JSON_ERROR_NONE) && !$params["cardnum"] && $params["gatewayid"] ) 
    {
        $gatewayId = explode(",", $params["gatewayid"]);
        if( count($gatewayId) == 3 ) 
        {
            list($customerProfileId, $customerPaymentProfileId) = $gatewayId;
            $gatewayId = array(  );
            $gatewayId["customer"] = $customerProfileId;
            $gatewayId["payment"] = $customerPaymentProfileId;
            $model = $params["clientdetails"]["model"];
            if( $model instanceof WHMCS\User\Client\Contact ) 
            {
                $model->client->paymentGatewayToken = json_encode($gatewayId);
                $model->client->save();
            }
            else
            {
                $model->paymentGatewayToken = json_encode($gatewayId);
                $model->save();
            }

        }
        else
        {
            $gatewayId = "";
        }

    }
    else
    {
        if( (!is_array($gatewayId) || json_last_error() !== JSON_ERROR_NONE) && $params["cardnum"] ) 
        {
            try
            {
                $merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
                $merchantAuthentication->setName($params["apiLoginId"]);
                $merchantAuthentication->setTransactionKey($params["transactionKey"]);
                $refId = "ref" . time();
                $creditCard = new net\authorize\api\contract\v1\CreditCardType();
                $creditCard->setCardNumber($params["cardnum"]);
                $cardExpiry = "20" . substr($params["cardexp"], 2, 2) . "-" . substr($params["cardexp"], 0, 2);
                $creditCard->setExpirationDate($cardExpiry);
                if( $params["cccvv"] ) 
                {
                    $creditCard->setCardCode($params["cccvv"]);
                }

                $paymentCreditCard = new net\authorize\api\contract\v1\PaymentType();
                $paymentCreditCard->setCreditCard($creditCard);
                $billTo = new net\authorize\api\contract\v1\CustomerAddressType();
                $billTo->setFirstName($params["clientdetails"]["firstname"]);
                $billTo->setLastName($params["clientdetails"]["lastname"]);
                $billTo->setCompany($params["clientdetails"]["companyname"]);
                $billTo->setAddress($params["clientdetails"]["address1"]);
                $billTo->setCity($params["clientdetails"]["city"]);
                $billTo->setState($params["clientdetails"]["state"]);
                $billTo->setZip($params["clientdetails"]["postcode"]);
                $billTo->setCountry($params["clientdetails"]["countryName"]);
                $billTo->setPhoneNumber($params["clientdetails"]["phonenumber"]);
                $paymentProfile = new net\authorize\api\contract\v1\CustomerPaymentProfileType();
                $paymentProfile->setCustomerType("individual");
                $paymentProfile->setBillTo($billTo);
                $paymentProfile->setPayment($paymentCreditCard);
                $paymentProfile->setDefaultpaymentProfile(true);
                $paymentProfiles[] = $paymentProfile;
                $customerProfile = new net\authorize\api\contract\v1\CustomerProfileType();
                $customerProfile->setDescription($params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"]);
                $customerProfile->setMerchantCustomerId("M_" . time());
                $customerProfile->setEmail($params["clientdetails"]["email"]);
                $customerProfile->setpaymentProfiles($paymentProfiles);
                $request = new net\authorize\api\contract\v1\CreateCustomerProfileRequest();
                $request->setMerchantAuthentication($merchantAuthentication);
                $request->setRefId($refId);
                $request->setProfile($customerProfile);
                $controller = new net\authorize\api\controller\CreateCustomerProfileController($request);
                $response = $controller->executeWithApiResponse($apiUrl);
                if( $response != NULL && $response->getMessages()->getResultCode() == "Ok" ) 
                {
                    $gatewayId = array(  );
                    $gatewayId["customer"] = $response->getCustomerProfileId();
                    $paymentProfiles = $response->getCustomerPaymentProfileIdList();
                    $gatewayId["payment"] = $paymentProfiles[0];
                    $model = $params["clientdetails"]["model"];
                    if( $model instanceof WHMCS\User\Client\Contact ) 
                    {
                        $model->client->paymentGatewayToken = json_encode($gatewayId);
                        $model->client->cardnum = "";
                        $model->client->save();
                    }
                    else
                    {
                        $model->paymentGatewayToken = json_encode($gatewayId);
                        $model->cardnum = "";
                        $model->save();
                    }

                }
                else
                {
                    $errorMessages = $response->getMessages()->getMessage();
                    $errorMessage = "Response : " . $errorMessages[0]->getCode() . " " . $errorMessages[0]->getText();
                    return array( "status" => "error", "rawdata" => "Invalid Response: " . $errorMessage );
                }

            }
            catch( Exception $e ) 
            {
                return array( "status" => "error", "rawdata" => "Invalid Response: " . $e->getMessage() );
            }
        }

    }

    if( !is_array($gatewayId) || !($gatewayId["customer"] && $gatewayId["payment"]) ) 
    {
        return array( "status" => "error", "rawdata" => "No Data Stored for Authorize.net Accept JS" );
    }

    try
    {
        $merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
        $merchantAuthentication->setName($params["apiLoginId"]);
        $merchantAuthentication->setTransactionKey($params["transactionKey"]);
        $refId = "ref" . time();
        $profileToCharge = new net\authorize\api\contract\v1\CustomerProfilePaymentType();
        $profileToCharge->setCustomerProfileId($gatewayId["customer"]);
        $paymentProfile = new net\authorize\api\contract\v1\PaymentProfileType();
        $paymentProfile->setPaymentProfileId($gatewayId["payment"]);
        $profileToCharge->setPaymentProfile($paymentProfile);
        $transactionRequestType = new net\authorize\api\contract\v1\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($params["amount"]);
        $transactionRequestType->setProfile($profileToCharge);
        $request = new net\authorize\api\contract\v1\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setRefId($refId);
        $request->setTransactionRequest($transactionRequestType);
        $controller = new net\authorize\api\controller\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse($apiUrl);
        if( $response != NULL ) 
        {
            if( $response->getMessages()->getResultCode() == "Ok" ) 
            {
                $tresponse = $response->getTransactionResponse();
                if( $tresponse != NULL && $tresponse->getMessages() != NULL ) 
                {
                    $client = WHMCS\User\Client::find($params["clientdetails"]["id"]);
                    if( $client ) 
                    {
                        $cardLastFour = substr($tresponse->getAccountNumber(), -4);
                        $cardType = $tresponse->getAccountType();
                        if( strtolower($cardType) == "americanexpress" ) 
                        {
                            $cardType = "American Express";
                        }

                        $client->creditCardLastFourDigits = $cardLastFour;
                        $client->creditCardType = $cardType;
                        $client->save();
                    }

                    $rawData = (array) $tresponse;
                    foreach( $rawData as $key => $value ) 
                    {
                        unset($rawData[$key]);
                        $key = str_replace("net\\authorize\\api\\contract\\v1\\TransactionResponseType", "", $key);
                        if( is_object($value) ) 
                        {
                            $value = (array) $value;
                        }

                        $rawData[$key] = $value;
                    }
                    return array( "status" => "success", "transid" => $tresponse->getTransId(), "amount" => $params["amount"], "rawdata" => (array) $rawData );
                }
                else
                {
                    return array( "status" => "declined", "rawdata" => array( "Error Code" => $tresponse->getErrors()[0]->getErrorCode(), "Error Message" => $tresponse->getErrors()[0]->getErrorText() ) );
                }

            }
            else
            {
                $tresponse = $response->getTransactionResponse();
                if( $tresponse != NULL && $tresponse->getErrors() != NULL ) 
                {
                    $errorCode = $tresponse->getErrors()[0]->getErrorCode();
                    $errorMessage = $tresponse->getErrors()[0]->getErrorText();
                }
                else
                {
                    $errorCode = $response->getMessages()->getMessage()[0]->getCode();
                    $errorMessage = $response->getMessages()->getMessage()[0]->getText();
                }

                return array( "status" => "error", "rawdata" => array( "Error Code" => $errorCode, "Error Message" => $errorMessage ) );
            }

        }
        else
        {
            return array( "status" => "error", "rawdata" => array( "Error" => "No response returned \n" ) );
        }

    }
    catch( Exception $e ) 
    {
        return array( "status" => "error", "rawdata" => array( "File Error" => $e->getFile(), "File Number" => $e->getLine(), "Error Message" => $e->getMessage() ) );
    }
}

function acceptjs_storeremote(array $params = array(  ))
{
    if( WHMCS\Session::get("cartccdetail") ) 
    {
        return "";
    }

    $apiUrl = net\authorize\api\constants\ANetEnvironment::PRODUCTION;
    if( $params["testMode"] ) 
    {
        $apiUrl = net\authorize\api\constants\ANetEnvironment::SANDBOX;
    }

    $dataDescriptor = WHMCS\Session::getAndDelete("dataDescriptor");
    if( !$dataDescriptor && App::isInRequest("dataDescriptor") ) 
    {
        $dataDescriptor = (string) App::getFromRequest("dataDescriptor");
    }

    $dataValue = WHMCS\Session::getAndDelete("dataValue");
    if( !$dataValue && App::isInRequest("dataValue") ) 
    {
        $dataValue = (string) App::getFromRequest("dataValue");
    }

    $gatewayId = json_decode($params["gatewayid"], true);
    $client = $params["clientdetails"];
    if( !$gatewayId || !is_array($gatewayId) || json_last_error() !== JSON_ERROR_NONE ) 
    {
        if( $dataValue && $dataDescriptor ) 
        {
            try
            {
                $merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
                $merchantAuthentication->setName($params["apiLoginId"]);
                $merchantAuthentication->setTransactionKey($params["transactionKey"]);
                $refId = "ref" . time();
                $opaqueData = new net\authorize\api\contract\v1\OpaqueDataType();
                $opaqueData->setDataDescriptor($dataDescriptor);
                $opaqueData->setDataValue($dataValue);
                $paymentOne = new net\authorize\api\contract\v1\PaymentType();
                $paymentOne->setOpaqueData($opaqueData);
                $customerAddress = new net\authorize\api\contract\v1\CustomerAddressType();
                $customerAddress->setFirstName($client["firstName"]);
                $customerAddress->setLastName($client["lastName"]);
                $customerAddress->setCompany($client["companyName"]);
                $customerAddress->setAddress($client["address1"]);
                $customerAddress->setCity($client["city"]);
                $customerAddress->setState($client["state"]);
                $customerAddress->setZip($client["postcode"]);
                $customerAddress->setCountry($client["countryName"]);
                $customerData = new net\authorize\api\contract\v1\CustomerPaymentProfileType();
                $customerData->setCustomerType("individual");
                $customerData->setBillTo($customerAddress);
                $customerData->setPayment($paymentOne);
                $customerData->setDefaultPaymentProfile(true);
                $paymentProfiles[] = $customerData;
                $customerProfile = new net\authorize\api\contract\v1\CustomerProfileType();
                $customerProfile->setDescription($client["fullName"]);
                $customerProfile->setMerchantCustomerId("M_" . time());
                $customerProfile->setEmail($client["email"]);
                $customerProfile->setpaymentProfiles($paymentProfiles);
                $paymentProfileRequest = new net\authorize\api\contract\v1\CreateCustomerProfileRequest();
                $paymentProfileRequest->setMerchantAuthentication($merchantAuthentication);
                $paymentProfileRequest->setRefId($refId);
                $paymentProfileRequest->setProfile($customerProfile);
                $controller = new net\authorize\api\controller\CreateCustomerProfileController($paymentProfileRequest);
                $response = $controller->executeWithApiResponse($apiUrl);
                if( $response != NULL && $response->getMessages()->getResultCode() == "Ok" ) 
                {
                    $customerProfileId = $response->getCustomerProfileId();
                    $paymentProfiles = $response->getCustomerPaymentProfileIdList();
                    $customerPaymentProfileId = $paymentProfiles[0];
                    $clientModel = WHMCS\User\Client::findOrFail($client["id"]);
                    $clientModel->paymentGatewayToken = json_encode(array( "customer" => $customerProfileId, "payment" => $customerPaymentProfileId ));
                    $clientModel->save();
                    $gatewayId = json_decode($client->paymentGatewayToken, true);
                    $rawData = (array) $response;
                    $customerProfileId = $gatewayId["customer"];
                    $customerPaymentProfileId = $gatewayId["payment"];
                    $request = new net\authorize\api\contract\v1\GetCustomerPaymentProfileRequest();
                    $request->setMerchantAuthentication($merchantAuthentication);
                    $request->setRefId($refId);
                    $request->setCustomerProfileId($customerProfileId);
                    $request->setCustomerPaymentProfileId($customerPaymentProfileId);
                    $controller = new net\authorize\api\controller\GetCustomerPaymentProfileController($request);
                    $response = $controller->executeWithApiResponse($apiUrl);
                    if( $response != NULL ) 
                    {
                        if( $response->getMessages()->getResultCode() == "Ok" ) 
                        {
                            $cardType = $response->getPaymentProfile()->getPayment()->getCreditCard()->getCardType();
                            $cardLastFour = $response->getPaymentProfile()->getPayment()->getCreditCard()->getCardNumber();
                            if( strtolower($cardType) == "americanexpress" ) 
                            {
                                $cardType = "American Express";
                            }

                            foreach( $rawData as $key => $value ) 
                            {
                                unset($rawData[$key]);
                                $key = str_replace("net\\authorize\\api\\contract\\v1\\CreateCustomerProfileResponse", "", $key);
                                if( is_object($value) ) 
                                {
                                    $value = (array) $value;
                                }

                                $rawData[$key] = $value;
                            }
                            $rawData2 = (array) $response;
                            foreach( $rawData2 as $key => $value ) 
                            {
                                unset($rawData2[$key]);
                                $key = str_replace("net\\authorize\\api\\contract\\v1\\ANetApiResponseType", "", $key);
                                if( is_object($value) ) 
                                {
                                    $value = (array) $value;
                                }

                                $rawData2[$key] = $value;
                            }
                            $rawData["setProfile"] = $rawData2;
                            return array( "noDelete" => true, "cardNumber" => $cardLastFour, "cardType" => $cardType, "gatewayid" => $gatewayId, "status" => "success", "rawdata" => $rawData );
                        }
                        else
                        {
                            $errorMessages = $response->getMessages()->getMessage();
                            $error = "Response : " . $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText() . "\n";
                            throw new WHMCS\Exception($error);
                        }

                    }
                    else
                    {
                        throw new WHMCS\Exception("Null Response");
                    }

                }
                else
                {
                    $errorMessages = $response->getMessages()->getMessage();
                    throw new WHMCS\Exception("Response : " . $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText());
                }

            }
            catch( Exception $e ) 
            {
                return array( "status" => "error", "rawdata" => array( "dataDescriptor" => $dataDescriptor, "dataValue" => $dataValue, "error" => $e->getMessage() ) );
            }
        }

    }
    else
    {
        if( $gatewayId && is_array($gatewayId) && $dataValue && $dataDescriptor ) 
        {
            try
            {
                $customerProfileId = $gatewayId["customer"];
                $customerPaymentProfileId = $gatewayId["payment"];
                $merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
                $merchantAuthentication->setName($params["apiLoginId"]);
                $merchantAuthentication->setTransactionKey($params["transactionKey"]);
                $refId = "ref" . time();
                if( $customerPaymentProfileId ) 
                {
                    $request = new net\authorize\api\contract\v1\DeleteCustomerPaymentProfileRequest();
                    $request->setMerchantAuthentication($merchantAuthentication);
                    $request->setCustomerProfileId($customerProfileId);
                    $request->setCustomerPaymentProfileId($customerPaymentProfileId);
                    $controller = new net\authorize\api\controller\DeleteCustomerPaymentProfileController($request);
                    $response = $controller->executeWithApiResponse($apiUrl);
                    if( $response == NULL || $response->getMessages()->getResultCode() !== "Ok" ) 
                    {
                        $errorMessages = $response->getMessages()->getMessage();
                        if( $errorMessages[0]->getCode() !== "E00040" ) 
                        {
                            $error = $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText();
                            throw new WHMCS\Exception($error);
                        }

                    }

                }

                $opaqueData = new net\authorize\api\contract\v1\OpaqueDataType();
                $opaqueData->setDataDescriptor($dataDescriptor);
                $opaqueData->setDataValue($dataValue);
                $customerAddress = new net\authorize\api\contract\v1\CustomerAddressType();
                $customerAddress->setFirstName($client["firstName"]);
                $customerAddress->setLastName($client["lastName"]);
                $customerAddress->setCompany($client["companyName"]);
                $customerAddress->setAddress($client["address1"]);
                $customerAddress->setCity($client["city"]);
                $customerAddress->setState($client["state"]);
                $customerAddress->setZip($client["postcode"]);
                $customerAddress->setCountry($client["countryName"]);
                $paymentOne = new net\authorize\api\contract\v1\PaymentType();
                $paymentOne->setOpaqueData($opaqueData);
                $paymentProfile = new net\authorize\api\contract\v1\CustomerPaymentProfileType();
                $paymentProfile->setCustomerType("individual");
                $paymentProfile->setBillTo($customerAddress);
                $paymentProfile->setPayment($paymentOne);
                $paymentProfile->setDefaultPaymentProfile(true);
                $paymentProfiles[] = $paymentProfile;
                $paymentProfileRequest = new net\authorize\api\contract\v1\CreateCustomerPaymentProfileRequest();
                $paymentProfileRequest->setMerchantAuthentication($merchantAuthentication);
                $paymentProfileRequest->setCustomerProfileId($customerProfileId);
                $paymentProfileRequest->setPaymentProfile($paymentProfile);
                $paymentProfileRequest->setValidationMode(($params["testMode"] ? "testMode" : "liveMode"));
                $controller = new net\authorize\api\controller\CreateCustomerPaymentProfileController($paymentProfileRequest);
                $response = $controller->executeWithApiResponse($apiUrl);
                if( $response != NULL && $response->getMessages()->getResultCode() == "Ok" ) 
                {
                    $customerPaymentProfileId = $response->getCustomerPaymentProfileId();
                    $clientModel = WHMCS\User\Client::findOrFail($client["id"]);
                    $clientModel->paymentGatewayToken = json_encode(array( "customer" => $customerProfileId, "payment" => $customerPaymentProfileId ));
                    $clientModel->save();
                    $rawData = (array) $response;
                    foreach( $rawData as $key => $value ) 
                    {
                        unset($rawData[$key]);
                        $key = str_replace("net\\authorize\\api\\contract\\v1\\ANetApiResponseType", "", $key);
                        if( is_object($value) ) 
                        {
                            $value = (array) $value;
                        }

                        $rawData[$key] = $value;
                    }
                    $gatewayId = json_decode($clientModel->paymentGatewayToken, true);
                    $customerProfileId = $gatewayId["customer"];
                    $customerPaymentProfileId = $gatewayId["payment"];
                    $request = new net\authorize\api\contract\v1\GetCustomerPaymentProfileRequest();
                    $request->setMerchantAuthentication($merchantAuthentication);
                    $request->setRefId($refId);
                    $request->setCustomerProfileId($customerProfileId);
                    $request->setCustomerPaymentProfileId($customerPaymentProfileId);
                    $controller = new net\authorize\api\controller\GetCustomerPaymentProfileController($request);
                    $response = $controller->executeWithApiResponse($apiUrl);
                    $cardLastFour = "UNKN";
                    $cardType = "Visa";
                    $rawData2 = (array) $response;
                    foreach( $rawData2 as $key => $value ) 
                    {
                        unset($rawData2[$key]);
                        $key = str_replace("net\\authorize\\api\\contract\\v1\\ANetApiResponseType", "", $key);
                        if( is_object($value) ) 
                        {
                            $value = (array) $value;
                        }

                        $rawData2[$key] = $value;
                    }
                    $rawData["paymentProfile"] = $rawData2;
                    if( $response != NULL && $response->getMessages()->getResultCode() == "Ok" ) 
                    {
                        $cardType = $response->getPaymentProfile()->getPayment()->getCreditCard()->getCardType();
                        $cardLastFour = $response->getPaymentProfile()->getPayment()->getCreditCard()->getCardNumber();
                        if( strtolower($cardType) == "americanexpress" ) 
                        {
                            $cardType = "American Express";
                        }

                    }

                    return array( "noDelete" => true, "cardNumber" => $cardLastFour, "cardType" => $cardType, "gatewayid" => $gatewayId, "status" => "success", "rawdata" => $rawData );
                }
                else
                {
                    $errorMessages = $response->getMessages()->getMessage();
                    throw new WHMCS\Exception("Response : " . $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText());
                }

            }
            catch( Exception $e ) 
            {
                return array( "status" => "error", "rawdata" => array( "dataDescriptor" => $dataDescriptor, "dataValue" => $dataValue, "error" => $e->getMessage() ) );
            }
        }
        else
        {
            if( $gatewayId && is_array($gatewayId) ) 
            {
                try
                {
                    $customerProfileId = $gatewayId["customer"];
                    $customerPaymentProfileId = $gatewayId["payment"];
                    $merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
                    $merchantAuthentication->setName($params["apiLoginId"]);
                    $merchantAuthentication->setTransactionKey($params["transactionKey"]);
                    $refId = "ref" . time();
                    $request = new net\authorize\api\contract\v1\DeleteCustomerPaymentProfileRequest();
                    $request->setMerchantAuthentication($merchantAuthentication);
                    $request->setCustomerProfileId($customerProfileId);
                    $request->setCustomerPaymentProfileId($customerPaymentProfileId);
                    $controller = new net\authorize\api\controller\DeleteCustomerPaymentProfileController($request);
                    $response = $controller->executeWithApiResponse($apiUrl);
                    $clientModel = WHMCS\User\Client::findOrFail($client["id"]);
                    $clientModel->paymentGatewayToken = json_encode(array( "customer" => $customerProfileId, "payment" => 0 ));
                    $clientModel->save();
                    $rawData = (array) $response;
                    foreach( $rawData as $key => $value ) 
                    {
                        unset($rawData[$key]);
                        $key = str_replace("net\\authorize\\api\\contract\\v1\\ANetApiResponseType", "", $key);
                        if( is_object($value) ) 
                        {
                            $value = (array) $value;
                        }

                        $rawData[$key] = $value;
                    }
                    return array( "status" => "success", "rawdata" => $rawData );
                }
                catch( Exception $e ) 
                {
                    return array( "status" => "error", "rawdata" => $e->getMessage() );
                }
            }

        }

    }

    return array( "status" => "error", "rawdata" => array( "dataDescriptor" => $dataDescriptor, "dataValue" => $dataValue, "error" => "An unknown Error Occurred" ) );
}

function acceptjs_orderformcheckout(array $params = array(  ))
{
    try
    {
        $dataDescriptor = WHMCS\Session::getAndDelete("dataDescriptor");
        $dataValue = WHMCS\Session::getAndDelete("dataValue");
        $apiUrl = net\authorize\api\constants\ANetEnvironment::PRODUCTION;
        if( $params["testMode"] ) 
        {
            $apiUrl = net\authorize\api\constants\ANetEnvironment::SANDBOX;
        }

        WHMCS\Session::delete("cartccdetail");
        $client = WHMCS\User\Client::find($params["clientdetails"]["id"]);
        $gatewayId = json_decode($client->paymentGatewayToken, true);
        if( !$gatewayId || !is_array($gatewayId) || json_last_error() !== JSON_ERROR_NONE ) 
        {
            $merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
            $merchantAuthentication->setName($params["apiLoginId"]);
            $merchantAuthentication->setTransactionKey($params["transactionKey"]);
            $refId = "ref" . time();
            $opaqueData = new net\authorize\api\contract\v1\OpaqueDataType();
            $opaqueData->setDataDescriptor($dataDescriptor);
            $opaqueData->setDataValue($dataValue);
            $paymentOne = new net\authorize\api\contract\v1\PaymentType();
            $paymentOne->setOpaqueData($opaqueData);
            $order = new net\authorize\api\contract\v1\OrderType();
            $order->setInvoiceNumber($params["invoiceid"]);
            $order->setDescription($params["description"]);
            $contact = $client;
            if( $client->billingContactId ) 
            {
                $contact = $client->contacts->find($client->billingContactId);
            }

            $customerAddress = new net\authorize\api\contract\v1\CustomerAddressType();
            $customerAddress->setFirstName($contact->firstName);
            $customerAddress->setLastName($contact->lastName);
            $customerAddress->setCompany($contact->companyName);
            $customerAddress->setAddress($contact->address1);
            $customerAddress->setCity($contact->city);
            $customerAddress->setState($contact->state);
            $customerAddress->setZip($contact->postcode);
            $customerAddress->setCountry($contact->countryName);
            $customerData = new net\authorize\api\contract\v1\CustomerPaymentProfileType();
            $customerData->setCustomerType("individual");
            $customerData->setBillTo($customerAddress);
            $customerData->setPayment($paymentOne);
            $customerData->setDefaultPaymentProfile(true);
            $paymentProfiles[] = $customerData;
            $customerProfile = new net\authorize\api\contract\v1\CustomerProfileType();
            $customerProfile->setDescription($contact->fullName);
            $customerProfile->setMerchantCustomerId("M_" . time());
            $customerProfile->setEmail($contact->email);
            $customerProfile->setpaymentProfiles($paymentProfiles);
            $paymentProfileRequest = new net\authorize\api\contract\v1\CreateCustomerProfileRequest();
            $paymentProfileRequest->setMerchantAuthentication($merchantAuthentication);
            $paymentProfileRequest->setRefId($refId);
            $paymentProfileRequest->setProfile($customerProfile);
            $controller = new net\authorize\api\controller\CreateCustomerProfileController($paymentProfileRequest);
            $response = $controller->executeWithApiResponse($apiUrl);
            if( $response != NULL && $response->getMessages()->getResultCode() == "Ok" ) 
            {
                $customerProfileId = $response->getCustomerProfileId();
                $paymentProfiles = $response->getCustomerPaymentProfileIdList();
                $customerPaymentProfileId = $paymentProfiles[0];
                $client->paymentGatewayToken = json_encode(array( "customer" => $customerProfileId, "payment" => $customerPaymentProfileId ));
                $client->save();
                $gatewayId = json_decode($client->paymentGatewayToken, true);
            }
            else
            {
                $errorMessages = $response->getMessages()->getMessage();
                throw new WHMCS\Exception("Response : " . $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText());
            }

        }

        if( $client && is_array($gatewayId) ) 
        {
            if( $dataValue && $dataDescriptor ) 
            {
                $customerProfileId = $gatewayId["customer"];
                $customerPaymentProfileId = $gatewayId["payment"];
                $merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
                $merchantAuthentication->setName($params["apiLoginId"]);
                $merchantAuthentication->setTransactionKey($params["transactionKey"]);
                $refId = "ref" . time();
                $request = new net\authorize\api\contract\v1\DeleteCustomerPaymentProfileRequest();
                $request->setMerchantAuthentication($merchantAuthentication);
                $request->setCustomerProfileId($customerProfileId);
                $request->setCustomerPaymentProfileId($customerPaymentProfileId);
                $controller = new net\authorize\api\controller\DeleteCustomerPaymentProfileController($request);
                $response = $controller->executeWithApiResponse($apiUrl);
                if( $response == NULL || $response->getMessages()->getResultCode() !== "Ok" ) 
                {
                    $errorMessages = $response->getMessages()->getMessage();
                    $error = $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText();
                    throw new WHMCS\Exception($error);
                }

                $opaqueData = new net\authorize\api\contract\v1\OpaqueDataType();
                $opaqueData->setDataDescriptor($dataDescriptor);
                $opaqueData->setDataValue($dataValue);
                $customerAddress = new net\authorize\api\contract\v1\CustomerAddressType();
                $customerAddress->setFirstName($client["firstName"]);
                $customerAddress->setLastName($client["lastName"]);
                $customerAddress->setCompany($client["companyName"]);
                $customerAddress->setAddress($client["address1"]);
                $customerAddress->setCity($client["city"]);
                $customerAddress->setState($client["state"]);
                $customerAddress->setZip($client["postcode"]);
                $customerAddress->setCountry($client["countryName"]);
                $paymentOne = new net\authorize\api\contract\v1\PaymentType();
                $paymentOne->setOpaqueData($opaqueData);
                $paymentProfile = new net\authorize\api\contract\v1\CustomerPaymentProfileType();
                $paymentProfile->setCustomerType("individual");
                $paymentProfile->setBillTo($customerAddress);
                $paymentProfile->setPayment($paymentOne);
                $paymentProfile->setDefaultPaymentProfile(true);
                $paymentProfiles[] = $paymentProfile;
                $paymentProfileRequest = new net\authorize\api\contract\v1\CreateCustomerPaymentProfileRequest();
                $paymentProfileRequest->setMerchantAuthentication($merchantAuthentication);
                $paymentProfileRequest->setCustomerProfileId($customerProfileId);
                $paymentProfileRequest->setPaymentProfile($paymentProfile);
                $paymentProfileRequest->setValidationMode(($params["testMode"] ? "testMode" : "liveMode"));
                $controller = new net\authorize\api\controller\CreateCustomerPaymentProfileController($paymentProfileRequest);
                $response = $controller->executeWithApiResponse($apiUrl);
                if( $response != NULL && $response->getMessages()->getResultCode() == "Ok" ) 
                {
                    $customerPaymentProfileId = $response->getCustomerPaymentProfileId();
                    $client->paymentGatewayToken = json_encode(array( "customer" => $customerProfileId, "payment" => $customerPaymentProfileId ));
                    $client->save();
                    $gatewayId = json_decode($client->paymentGatewayToken, true);
                    $customerProfileId = $gatewayId["customer"];
                    $customerPaymentProfileId = $gatewayId["payment"];
                    $request = new net\authorize\api\contract\v1\GetCustomerPaymentProfileRequest();
                    $request->setMerchantAuthentication($merchantAuthentication);
                    $request->setRefId($refId);
                    $request->setCustomerProfileId($customerProfileId);
                    $request->setCustomerPaymentProfileId($customerPaymentProfileId);
                    $controller = new net\authorize\api\controller\GetCustomerPaymentProfileController($request);
                    $response = $controller->executeWithApiResponse($apiUrl);
                    $cardLastFour = "UNKN";
                    $cardType = "Visa";
                    if( $response != NULL && $response->getMessages()->getResultCode() == "Ok" ) 
                    {
                        $cardType = $response->getPaymentProfile()->getPayment()->getCreditCard()->getCardType();
                        $cardLastFour = $response->getPaymentProfile()->getPayment()->getCreditCard()->getCardNumber();
                        if( strtolower($cardType) == "americanexpress" ) 
                        {
                            $cardType = "American Express";
                        }

                    }

                    $client->creditCardType = $cardType;
                    $client->creditCardLastFourDigits = $cardLastFour;
                    $client->save();
                }

            }

            $merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
            $merchantAuthentication->setName($params["apiLoginId"]);
            $merchantAuthentication->setTransactionKey($params["transactionKey"]);
            $refId = "ref" . time();
            $profileToCharge = new net\authorize\api\contract\v1\CustomerProfilePaymentType();
            $profileToCharge->setCustomerProfileId($gatewayId["customer"]);
            $paymentProfile = new net\authorize\api\contract\v1\PaymentProfileType();
            $paymentProfile->setPaymentProfileId($gatewayId["payment"]);
            $profileToCharge->setPaymentProfile($paymentProfile);
            $transactionRequestType = new net\authorize\api\contract\v1\TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount($params["amount"]);
            $transactionRequestType->setProfile($profileToCharge);
            $request = new net\authorize\api\contract\v1\CreateTransactionRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setRefId($refId);
            $request->setTransactionRequest($transactionRequestType);
            $controller = new net\authorize\api\controller\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse($apiUrl);
            if( $response != NULL ) 
            {
                if( $response->getMessages()->getResultCode() == "Ok" ) 
                {
                    $tresponse = $response->getTransactionResponse();
                    if( $tresponse != NULL && $tresponse->getMessages() != NULL ) 
                    {
                        $client->creditCardLastFourDigits = substr($tresponse->getAccountNumber(), 4);
                        $client->creditCardType = $tresponse->getAccountType();
                        $client->save();
                        $rawData = (array) $tresponse;
                        foreach( $rawData as $key => $value ) 
                        {
                            unset($rawData[$key]);
                            $key = str_replace("net\\authorize\\api\\contract\\v1\\TransactionResponseType", "", $key);
                            if( is_object($value) ) 
                            {
                                $value = (array) $value;
                            }

                            $rawData[$key] = $value;
                        }
                        $amount = $params["amount"];
                        if( array_key_exists("convertto", $params) ) 
                        {
                            $amount = $params["basecurrencyamount"];
                        }

                        return array( "status" => "success", "transid" => $tresponse->getTransId(), "amount" => $amount, "rawdata" => $rawData );
                    }
                    else
                    {
                        WHMCS\Session::set("AcceptJsDeclined" . $params["invoiceid"], true);
                        return array( "status" => "declined", "rawdata" => array( "Error Code" => $tresponse->getErrors()[0]->getErrorCode(), "Error Message" => $tresponse->getErrors()[0]->getErrorText() ) );
                    }

                }
                else
                {
                    $tresponse = $response->getTransactionResponse();
                    if( $tresponse != NULL && $tresponse->getErrors() != NULL ) 
                    {
                        $errorCode = $tresponse->getErrors()[0]->getErrorCode();
                        $errorMessage = $tresponse->getErrors()[0]->getErrorText();
                    }
                    else
                    {
                        $errorCode = $response->getMessages()->getMessage()[0]->getCode();
                        $errorMessage = $response->getMessages()->getMessage()[0]->getText();
                    }

                    return array( "status" => "error", "rawdata" => array( "Error Code" => $errorCode, "Error Message" => $errorMessage ) );
                }

            }

        }

    }
    catch( Exception $e ) 
    {
        return array( "status" => "error", "rawdata" => array( "Error" => $e->getMessage() ) );
    }
    return array( "status" => "error", "rawdata" => array( "Error" => "No response returned \n" ) );
}

function acceptjs_cc_validation(array $params = array(  ))
{
    if( App::isInRequest("dataDescriptor") ) 
    {
        WHMCS\Session::set("dataDescriptor", (string) App::getFromRequest("dataDescriptor"));
    }

    if( App::isInRequest("dataValue") ) 
    {
        WHMCS\Session::set("dataValue", (string) App::getFromRequest("dataValue"));
    }

    return "";
}

function acceptjs_credit_card_input(array $params = array(  ))
{
    $assetHelper = DI::make("asset");
    $now = time();
    $jsUrl = "https://js.authorize.net/v1/Accept.js";
    if( $params["testMode"] ) 
    {
        $jsUrl = "https://jstest.authorize.net/v1/Accept.js";
    }

    $additional = "";
    if( $error = WHMCS\Session::getAndDelete("AcceptJsDeclined" . $params["invoiceid"]) ) 
    {
        $error = Lang::trans("creditcarddeclined");
        $additional .= "\njQuery('.gateway-errors').html('" . $error . "').removeClass('hidden');";
    }

    return "<script type=\"text/javascript\" src=\"" . $jsUrl . "\" charset=\"utf-8\"></script>\n<script type=\"text/javascript\">\n    var clientKey = '" . $params["publicKey"] . "',\n        apiLoginId = '" . $params["apiLoginId"] . "';" . $additional . "\n</script>\n<script type=\"text/javascript\" src=\"" . $assetHelper->getJsPath() . "/jquery.payment.js\"></script>\n<script type=\"text/javascript\" src=\"" . $assetHelper->getWebRoot() . "/modules/gateways/acceptjs/acceptjs.js?a=" . $now . "\"></script>";
}



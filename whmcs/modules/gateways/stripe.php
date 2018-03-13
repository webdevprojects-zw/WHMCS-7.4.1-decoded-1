<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

function _stripe_formatValue($value)
{
    return ($value !== "" ? $value : NULL);
}

function _stripe_formatAmount($amount, $currencyCode)
{
    $currenciesWithoutDecimals = array( "BIF", "CLP", "DJF", "GNF", "JPY", "KMF", "KRW", "MGA", "PYG", "RWF", "VND", "VUV", "XAF", "XOF", "XPF" );
    $currencyCode = strtoupper($currencyCode);
    $isNoDecimalCurrency = in_array($currencyCode, $currenciesWithoutDecimals);
    $amount = str_replace(array( ",", "." ), "", $amount);
    if( $isNoDecimalCurrency ) 
    {
        $amount = round($amount / 100);
    }

    return $amount;
}

function stripe_config()
{
    $config = array( "FriendlyName" => array( "Type" => "System", "Value" => "Stripe" ), "secretKey" => array( "FriendlyName" => "Stripe Secret API Key", "Type" => "text", "Size" => "30", "Description" => "Your secret API Key ensures only communications from Stripe are validated." ), "publishableKey" => array( "FriendlyName" => "Stripe Publishable API Key", "Type" => "text", "Size" => "30", "Description" => "Your publishable API key identifies your website to Stripe during communications. " . "This can be obtained from <a href=\"https://dashboard.stripe.com/account/apikeys\" class=\"autoLinked\">here</a>" ), "statementDescriptor" => array( "FriendlyName" => "Statement Descriptor", "Type" => "text", "Size" => 25, "Default" => "{CompanyName}", "Description" => "Available merge field tags: <strong>{CompanyName} {InvoiceNumber}</strong>\n<div class=\"alert alert-info top-margin-5 bottom-margin-5\">\n    Displayed on your customer's credit card statement.<br />\n    <strong>Maximum of 22 characters</strong>.<br />\n</div>" ), "applePay" => array( "FriendlyName" => "Allow Apple Pay", "Type" => "yesno", "Description" => "Tick to enable showing the Apple Pay option on supported devices" ) );
    $hooksPath = ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "hooks" . DIRECTORY_SEPARATOR;
    if( file_exists($hooksPath . "stripe.php") ) 
    {
        $config["existingHook"] = array( "FriendlyName" => "Existing Hook", "Description" => "<div class=\"alert alert-danger top-margin-5 bottom-margin-5\">\n    We have detected the presence of a stripe.php hook file in " . $hooksPath . ".<br />\n    This is a file commonly present when using a third party Stripe module.<br />\n    To use the official WHMCS module, any previous third party modules must be fully uninstalled/removed.\n</div>" );
    }

    $systemTemplate = ROOTDIR . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . WHMCS\Config\Setting::getValue("Template") . DIRECTORY_SEPARATOR;
    $orderTemplate = WHMCS\View\Template\OrderForm::factory();
    $searchText = "gateway-errors";
    $mainTemplateFiles = array( "clientareacreditcard.tpl", "creditcard.tpl" );
    $templatesToUpdate = array(  );
    foreach( $mainTemplateFiles as $templateFile ) 
    {
        $templateContents = file_get_contents($systemTemplate . $templateFile);
        if( stristr($templateContents, $searchText) === false ) 
        {
            $templatesToUpdate[] = $systemTemplate . $templateFile;
        }

    }
    $templateContents = "";
    if( $orderTemplate->hasTemplate("checkout", false) ) 
    {
        $templateContents = file_get_contents($orderTemplate->getTemplatePath() . "checkout.tpl");
        $orderTemplate = $orderTemplate->getTemplatePath() . "checkout.tpl";
    }
    else
    {
        if( $orderTemplate->hasTemplate("checkout") ) 
        {
            $templateContents = file_get_contents($orderTemplate->getParent()->getTemplatePath() . "checkout.tpl");
            $orderTemplate = $orderTemplate->getParent()->getTemplatePath() . "checkout.tpl";
        }
        else
        {
            if( $orderTemplate->hasTemplate("viewcart", false) ) 
            {
                $templateContents = file_get_contents($orderTemplate->getTemplatePath() . "viewcart.tpl");
                $orderTemplate = $orderTemplate->getTemplatePath() . "viewcart.tpl";
            }
            else
            {
                if( $orderTemplate->hasTemplate("viewcart") ) 
                {
                    $templateContents = file_get_contents($orderTemplate->getParent()->getTemplatePath() . "viewcart.tpl");
                    $orderTemplate = $orderTemplate->getParent()->getTemplatePath() . "viewcart.tpl";
                }
                else
                {
                    $orderTemplate = NULL;
                }

            }

        }

    }

    if( $orderTemplate && stristr($templateContents, $searchText) === false ) 
    {
        $templatesToUpdate[] = $orderTemplate;
    }

    if( $templatesToUpdate ) 
    {
        $config["templateChanges"] = array( "FriendlyName" => "Template Changes", "Description" => "<div class=\"alert alert-danger top-margin-5 bottom-margin-5\">\n    Required Template Changes Not Found: We were unable to detect the presence of the required WHMCS 7.1 template changes for Stripe compatibility in your active order form or client area template. Please ensure the changes itemised in the 7.1 upgrade here have been applied. Please see <a href=\"http://docs.whmcs.com/Version_7.1_Release_Notes#Template_Changes\" class=\"autoLinked\">Template Changes</a> for more information.\n</div>" );
    }

    return $config;
}

function stripe_capture(array $params = array(  ))
{
    $stripeCustomer = $params["gatewayid"];
    if( substr($stripeCustomer, 0, 3) != "cus" ) 
    {
        $stripeCustomer = "";
    }

    $amount = _stripe_formatamount($params["amount"], $params["currency"]);
    Stripe\Stripe::setApiKey($params["secretKey"]);
    $client = WHMCS\User\Client::find($params["clientdetails"]["userid"]);
    if( $params["cardnum"] ) 
    {
        try
        {
            $card = array( "number" => $params["cardnum"], "exp_month" => substr($params["cardexp"], 0, 2), "exp_year" => substr($params["cardexp"], 2), "address_country" => $params["clientdetails"]["country"], "name" => $params["clientdetails"]["fullname"] );
            if( array_key_exists("address1", $params["clientdetails"]) ) 
            {
                $card["address_line1"] = _stripe_formatvalue($params["clientdetails"]["address1"]);
            }

            if( array_key_exists("address2", $params["clientdetails"]) ) 
            {
                $card["address_line2"] = _stripe_formatvalue($params["clientdetails"]["address2"]);
            }

            if( array_key_exists("city", $params["clientdetails"]) ) 
            {
                $card["address_city"] = _stripe_formatvalue($params["clientdetails"]["city"]);
            }

            if( array_key_exists("state", $params["clientdetails"]) ) 
            {
                $card["address_state"] = _stripe_formatvalue($params["clientdetails"]["state"]);
            }

            if( array_key_exists("postcode", $params["clientdetails"]) ) 
            {
                $card["address_zip"] = _stripe_formatvalue($params["clientdetails"]["postcode"]);
            }

            if( $params["cccvv"] ) 
            {
                $card["cvc"] = $params["cccvv"];
            }

            $token = Stripe\Token::create(array( "card" => $card ))->id;
        }
        catch( Exception $e ) 
        {
            return array( "status" => "error", "rawdata" => $e->getMessage() );
        }
    }

    if( isset($token) && $token ) 
    {
        if( !$stripeCustomer ) 
        {
            try
            {
                $stripeCustomer = Stripe\Customer::create(array( "source" => $token, "description" => "Customer for " . $client->fullName . " (" . $client->email . ")", "email" => $client->email, "metadata" => array( "id" => $client->id, "fullName" => $client->fullName, "email" => $client->email ) ));
                $card = $stripeCustomer->sources->jsonSerialize()["data"][0];
                $cardLastFour = $card["last4"];
                $cardExpiry = $client->generateCreditCardEncryptedField(str_pad($card["exp_month"], 2, "0", STR_PAD_LEFT) . substr($card["exp_year"], 2));
                $cardType = $card["brand"];
                $client->creditCardExpiryDate = $cardExpiry;
                $client->creditCardLastFourDigits = $cardLastFour;
                $client->creditCardType = $cardType;
                $client->paymentGatewayToken = $stripeCustomer->id;
                $client->save();
                if( $client->billingContactId ) 
                {
                    $client = $client->contacts->find($client->billingContactId);
                }

                try
                {
                    $card = $stripeCustomer->sources->retrieve($card["id"]);
                    $card->address_line1 = _stripe_formatvalue($client->address1);
                    $card->address_line2 = _stripe_formatvalue($client->address2);
                    $card->address_city = _stripe_formatvalue($client->city);
                    $card->address_state = _stripe_formatvalue($client->state);
                    $card->address_zip = _stripe_formatvalue($client->postcode);
                    $card->address_country = _stripe_formatvalue($client->country);
                    $card->name = _stripe_formatvalue($client->fullName);
                    $card->save();
                }
                catch( Exception $e ) 
                {
                }
            }
            catch( Exception $e ) 
            {
                return array( "status" => "error", "rawdata" => $e->getMessage() );
            }
        }
        else
        {
            try
            {
                $stripeCustomer = Stripe\Customer::retrieve($stripeCustomer);
                $stripeCustomer->source = $token;
                $stripeCustomer = $stripeCustomer->save();
            }
            catch( Exception $e ) 
            {
                return array( "status" => "error", "rawdata" => $e->getMessage() );
            }
        }

        $stripeCustomer = $stripeCustomer->id;
    }

    try
    {
        $charge = Stripe\Charge::create(array( "amount" => $amount, "currency" => strtolower($params["currency"]), "customer" => $stripeCustomer, "description" => $params["description"], "metadata" => array( "id" => $params["invoiceid"], "invoiceNumber" => $params["invoicenum"] ), "statement_descriptor" => stripe_statement_descriptor($params) ));
        $transaction = Stripe\BalanceTransaction::retrieve($charge->balance_transaction);
        $transactionFeeCurrency = WHMCS\Database\Capsule::table("tblcurrencies")->where("code", "=", strtoupper($transaction->fee_details[0]->currency))->first(array( "id" ));
        $transactionId = $transaction->id;
        $transactionFee = 0;
        if( $transactionFeeCurrency ) 
        {
            $transactionFee = convertCurrency($transaction->fee / 100, $transactionFeeCurrency->id, ($params["convertto"] ?: $client->currencyId));
        }

        return array( "status" => "success", "transid" => $transactionId, "amount" => $params["amount"], "fee" => $transactionFee, "rawdata" => array( "charge" => $charge->jsonSerialize(), "transaction" => $transaction->jsonSerialize() ) );
    }
    catch( Stripe\Error\Card $e ) 
    {
        return array( "status" => "declined", "rawdata" => $e->getMessage() );
    }
    catch( Exception $e ) 
    {
        return array( "status" => "error", "rawdata" => $e->getMessage() );
    }
}

function stripe_orderformcheckout(array $params = array(  ))
{
    $amount = _stripe_formatamount($params["amount"], $params["currency"]);
    $token = WHMCS\Session::getAndDelete("stripeToken");
    WHMCS\Session::delete("cartccdetail");
    Stripe\Stripe::setApiKey($params["secretKey"]);
    $client = WHMCS\User\Client::find($params["clientdetails"]["id"]);
    try
    {
        $stripeCustomer = $params["gatewayid"];
        if( substr($stripeCustomer, 0, 3) != "cus" ) 
        {
            $stripeCustomer = "";
        }

        if( $token ) 
        {
            if( !$stripeCustomer ) 
            {
                try
                {
                    $stripeCustomer = Stripe\Customer::create(array( "source" => $token, "description" => "Customer for " . $client->firstName . " " . $client->lastName . " (" . $client->email . ")", "email" => $client->email, "metadata" => array( "id" => $client->id, "fullName" => $client->fullName, "email" => $client->email ) ));
                }
                catch( Exception $e ) 
                {
                    throw $e;
                }
            }
            else
            {
                $stripeCustomer = Stripe\Customer::retrieve($stripeCustomer);
                $stripeCustomer->source = $token;
                $stripeCustomer->save();
            }

            if( $stripeCustomer ) 
            {
                $card = $stripeCustomer->sources->jsonSerialize()["data"][0];
                $cardLastFour = $card["last4"];
                $cardExpiry = $client->generateCreditCardEncryptedField(str_pad($card["exp_month"], 2, "0", STR_PAD_LEFT) . substr($card["exp_year"], 2));
                $cardType = $card["brand"];
                $client->creditCardExpiryDate = $cardExpiry;
                $client->creditCardLastFourDigits = $cardLastFour;
                $client->creditCardType = $cardType;
                $client->paymentGatewayToken = $stripeCustomer->id;
                $client->save();
                if( $client->billingContactId ) 
                {
                    $client = $client->contacts->find($client->billingContactId);
                }

                try
                {
                    $card = $stripeCustomer->sources->retrieve($card["id"]);
                    $card->address_line1 = _stripe_formatvalue($client->address1);
                    $card->address_line2 = _stripe_formatvalue($client->address2);
                    $card->address_city = _stripe_formatvalue($client->city);
                    $card->address_state = _stripe_formatvalue($client->state);
                    $card->address_zip = _stripe_formatvalue($client->postcode);
                    $card->address_country = _stripe_formatvalue($client->country);
                    $card->name = _stripe_formatvalue($client->firstName . " " . $client->lastName);
                    $card->save();
                }
                catch( Stripe\Error\ApiConnection $e ) 
                {
                    throw $e;
                }
                catch( Exception $e ) 
                {
                }
                $stripeCustomer = $stripeCustomer->id;
            }

        }

        if( !$stripeCustomer ) 
        {
            return array( "status" => "error", "rawdata" => "No Stripe Customer Details Found" );
        }

        $charge = Stripe\Charge::create(array( "amount" => $amount, "currency" => strtolower($params["currency"]), "customer" => $stripeCustomer, "description" => $params["description"], "metadata" => array( "id" => $params["invoiceid"], "invoiceNumber" => $params["invoicenum"] ), "statement_descriptor" => stripe_statement_descriptor($params) ));
        $transaction = Stripe\BalanceTransaction::retrieve($charge->balance_transaction);
        $transactionFeeCurrency = WHMCS\Database\Capsule::table("tblcurrencies")->where("code", "=", strtoupper($transaction->fee_details[0]->currency))->first(array( "id" ));
        $transactionId = $transaction->id;
        $transactionFee = 0;
        if( $transactionFeeCurrency ) 
        {
            $transactionFee = convertCurrency($transaction->fee / 100, $transactionFeeCurrency->id, ($params["convertto"] ?: $client->currencyId));
        }

        $amount = $params["amount"];
        if( array_key_exists("convertto", $params) ) 
        {
            $amount = $params["basecurrencyamount"];
        }

        return array( "status" => "success", "transid" => $transactionId, "amount" => $amount, "fee" => $transactionFee, "rawdata" => array( "charge" => $charge->jsonSerialize(), "transaction" => $transaction->jsonSerialize() ) );
    }
    catch( Stripe\Error\Card $e ) 
    {
        WHMCS\Session::set("StripeDeclined" . $params["invoiceid"], true);
        return array( "status" => "declined", "rawdata" => array( "error" => $e->getMessage() ) );
    }
    catch( Exception $e ) 
    {
        return array( "status" => "error", "rawdata" => array( "error" => $e->getMessage() ) );
    }
}

function stripe_storeremote(array $params = array(  ))
{
    if( WHMCS\Session::get("cartccdetail") ) 
    {
        return "";
    }

    $token = WHMCS\Session::getAndDelete("stripeToken");
    if( !$token && App::isInRequest("stripeToken") ) 
    {
        $token = (string) App::getFromRequest("stripeToken");
    }

    Stripe\Stripe::setApiKey($params["secretKey"]);
    $stripeCustomer = $params["gatewayid"];
    if( substr($stripeCustomer, 0, 3) != "cus" ) 
    {
        $stripeCustomer = "";
    }

    $client = $params["clientdetails"];
    if( $token ) 
    {
        if( !$stripeCustomer ) 
        {
            try
            {
                $stripeCustomer = Stripe\Customer::create(array( "source" => $token, "description" => "Customer for " . $client["fullname"] . " (" . $client["email"] . ")", "email" => $client["email"], "metadata" => array( "id" => $client["userid"], "fullName" => $client["fullname"], "email" => $client["email"] ) ));
            }
            catch( Exception $e ) 
            {
                return array( "status" => "error", "rawdata" => array( "token" => $token, "error" => $e->getMessage() ) );
            }
        }
        else
        {
            try
            {
                $stripeCustomer = Stripe\Customer::retrieve($stripeCustomer);
                if( $token != $stripeCustomer->source ) 
                {
                    $stripeCustomer->source = $token;
                    $stripeCustomer = $stripeCustomer->save();
                }

            }
            catch( Exception $e ) 
            {
                return array( "status" => "error", "rawdata" => array( "token" => $token, "error" => $e->getMessage() ) );
            }
        }

        $card = $stripeCustomer->sources->jsonSerialize()["data"][0];
        $cardLastFour = $card["last4"];
        $cardExpiry = str_pad($card["exp_month"], 2, "0", STR_PAD_LEFT) . substr($card["exp_year"], 2);
        $cardType = $card["brand"];
        try
        {
            $card = $stripeCustomer->sources->retrieve($card["id"]);
            if( array_key_exists("address1", $client) ) 
            {
                $card->address_line1 = _stripe_formatvalue($client["address1"]);
            }

            if( array_key_exists("address2", $client) ) 
            {
                $card->address_line2 = _stripe_formatvalue($client["address2"]);
            }

            if( array_key_exists("city", $client) ) 
            {
                $card->address_city = _stripe_formatvalue($client["city"]);
            }

            if( array_key_exists("state", $client) ) 
            {
                $card->address_state = _stripe_formatvalue($client["state"]);
            }

            if( array_key_exists("postcode", $client) ) 
            {
                $card->address_zip = _stripe_formatvalue($client["postcode"]);
            }

            $card->address_country = _stripe_formatvalue($client["country"]);
            $card->name = _stripe_formatvalue($client["fullname"]);
            $card->save();
        }
        catch( Stripe\Error\ApiConnection $e ) 
        {
            return array( "status" => "error", "rawdata" => array( "token" => $token, "error" => $e->getMessage() ) );
        }
        catch( Exception $e ) 
        {
        }
        return array( "noDelete" => true, "cardNumber" => $cardLastFour, "cardExpiry" => $cardExpiry, "cardType" => $cardType, "gatewayid" => $stripeCustomer->id, "status" => "success", "rawdata" => $stripeCustomer->jsonSerialize() );
    }

    if( $stripeCustomer ) 
    {
        try
        {
            $stripeCustomer = Stripe\Customer::retrieve($stripeCustomer)->delete();
            return array( "status" => "success", "rawdata" => $stripeCustomer->jsonSerialize() );
        }
        catch( Exception $e ) 
        {
            return array( "status" => "error", "rawdata" => array( "customer" => $stripeCustomer, "error" => $e->getMessage() ) );
        }
    }

    return array( "status" => "error", "rawdata" => "No Stripe Details Found for Update" );
}

function stripe_refund(array $params = array(  ))
{
    $amount = _stripe_formatamount($params["amount"], $params["currency"]);
    Stripe\Stripe::setApiKey($params["secretKey"]);
    $client = WHMCS\User\Client::find($params["clientdetails"]["userid"]);
    try
    {
        $transaction = Stripe\BalanceTransaction::retrieve($params["transid"]);
        $refund = Stripe\Refund::create(array( "charge" => $transaction->source, "amount" => $amount ));
        $refundTransaction = Stripe\BalanceTransaction::retrieve($refund->balance_transaction);
        $transactionFeeCurrency = WHMCS\Database\Capsule::table("tblcurrencies")->where("code", "=", strtoupper($refundTransaction->fee_details[0]->currency))->first(array( "id" ));
        $refundTransactionFee = 0;
        if( $transactionFeeCurrency ) 
        {
            $refundTransactionFee = convertCurrency($refundTransaction->fee / -100, $transactionFeeCurrency->id, ($params["convertto"] ?: $client->currencyId));
        }

        return array( "transid" => $refundTransaction->id, "rawdata" => array_merge($refund->jsonSerialize(), $refundTransaction->jsonSerialize()), "status" => "success", "fees" => $refundTransactionFee );
    }
    catch( Exception $e ) 
    {
        return array( "status" => "error", "rawdata" => $e->getMessage() );
    }
}

function stripe_cc_validation(array $params = array(  ))
{
    if( App::isInRequest("stripeToken") ) 
    {
        WHMCS\Session::set("stripeToken", (string) App::getFromRequest("stripeToken"));
    }

    return "";
}

function stripe_credit_card_input(array $params = array(  ))
{
    $assetHelper = DI::make("asset");
    $now = time();
    $existingSubmittedToken = App::getFromRequest("stripeToken");
    $additional = "    \n    var existingToken = '" . $existingSubmittedToken . "';";
    if( $params["applePay"] && array_key_exists("rawtotal", $params) ) 
    {
        $currencyData = getCurrency(WHMCS\Session::get("uid"), WHMCS\Session::get("currency"));
        $description = stripe_statement_descriptor($params) . " " . Lang::trans("carttitle");
        $additional .= "\n    var applePay = true,\n        applePayAmountDue = '" . $params["rawtotal"] . "',\n        applePayCurrency = '" . $currencyData["code"] . "',\n        applePayDescription = '" . $description . "';";
    }
    else
    {
        if( $params["applePay"] && array_key_exists("amount", $params) ) 
        {
            $description = stripe_statement_descriptor($params);
            $additional .= "\n    var applePay = true,\n        applePayAmountDue = '" . $params["amount"] . "',\n        applePayCurrency = '" . $params["currency"] . "',\n        applePayDescription = '" . $description . "';";
        }
        else
        {
            $additional .= "\n    var applePay = false;";
        }

    }

    if( $error = WHMCS\Session::getAndDelete("StripeDeclined" . $params["invoiceid"]) ) 
    {
        $error = Lang::trans("creditcarddeclined");
        $additional .= "\njQuery('.gateway-errors').html('" . $error . "').removeClass('hidden');";
    }

    return "<script type=\"text/javascript\" src=\"https://js.stripe.com/v2/\"></script>\n<script type=\"text/javascript\">\n    Stripe.setPublishableKey('" . $params["publishableKey"] . "');" . $additional . "\n</script>\n<script type=\"text/javascript\" src=\"" . $assetHelper->getJsPath() . "/jquery.payment.js\"></script>\n<script type=\"text/javascript\" src=\"" . $assetHelper->getWebRoot() . "/modules/gateways/stripe/stripe.js?a=" . $now . "\"></script>\n<link href=\"" . $assetHelper->getWebRoot() . "/modules/gateways/stripe/stripe.css?a=" . $now . "\" rel=\"stylesheet\">";
}

function stripe_statement_descriptor(array $params)
{
    $invoiceNumber = (array_key_exists("invoicenum", $params) && $params["invoicenum"] ? $params["invoicenum"] : $params["invoiceid"]);
    return substr(str_replace(array( "{CompanyName}", "{InvoiceNumber}", ">", "<", "'", "\"" ), array( WHMCS\Config\Setting::getValue("CompanyName"), $invoiceNumber, "", "", "", "" ), $params["statementDescriptor"]), -22);
}



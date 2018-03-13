<?php 
namespace WHMCS;


class Invoice
{
    public $pdf = "";
    private $invoiceid = "";
    private $data = array(  );
    private $output = array(  );
    private $totalbalance = 0;
    protected $gateway = NULL;
    protected $gatewayModulesWhereCallbacksMightBeDelayed = array( "paypal" );

    public function __construct($invoiceid = "")
    {
        if( $invoiceid ) 
        {
            $this->setID($invoiceid);
        }

    }

    public function setID($invoiceid)
    {
        $this->invoiceid = (int) $invoiceid;
        return true;
    }

    public function getID()
    {
        return $this->invoiceid;
    }

    public function loadData($force = true)
    {
        if( !$force && count($this->data) ) 
        {
            return false;
        }

        $result = select_query("tblinvoices", "tblinvoices.*,(SELECT value FROM tblpaymentgateways WHERE gateway=tblinvoices.paymentmethod AND setting='name' LIMIT 1) AS gateway,IFNULL((SELECT SUM(amountin-amountout) FROM tblaccounts WHERE invoiceid=tblinvoices.id),0) as amountpaid", array( "id" => $this->invoiceid ));
        $data = mysql_fetch_assoc($result);
        if( !$data["id"] ) 
        {
            return false;
        }

        $data["invoiceid"] = $data["id"];
        $data["invoicenumorig"] = $data["invoicenum"];
        if( !$data["invoicenum"] ) 
        {
            $data["invoicenum"] = $data["id"];
        }

        $data["paymentmodule"] = $data["paymentmethod"];
        $data["paymentmethod"] = $data["gateway"];
        $data["balance"] = sprintf("%01.2f", $data["total"] - $data["amountpaid"]);
        $this->data = $data;
        return true;
    }

    public function getData($var = "")
    {
        $this->loadData(false);
        return (isset($this->data[$var]) ? $this->data[$var] : $this->data);
    }

    public function getStatuses()
    {
        return array( "Unpaid", "Paid", "Cancelled", "Refunded", "Collections", "Payment Pending" );
    }

    public function isAllowed($uid = "")
    {
        $this->loadData(false);
        if( !$uid ) 
        {
            $uid = Session::get("uid");
        }

        if( !$uid || $this->data["status"] == "Draft" || isset($this->data["userid"]) && $this->data["userid"] != $uid ) 
        {
            return false;
        }

        return true;
    }

    public function formatForOutput()
    {
        global $currency;
        $whmcs = \DI::make("app");
        $this->output = $this->data;
        $array = array( "date", "duedate", "datepaid" );
        foreach( $array as $v ) 
        {
            $this->output[$v] = (substr($this->output[$v], 0, 10) != "0000-00-00" ? fromMySQLDate($this->output[$v], ($v == "datepaid" ? "1" : "0"), 1) : "");
        }
        $this->output["datecreated"] = $this->output["date"];
        $this->output["datedue"] = $this->output["duedate"];
        $currency = getCurrency($this->getData("userid"));
        $array = array( "subtotal", "credit", "tax", "tax2", "total", "balance", "amountpaid" );
        foreach( $array as $v ) 
        {
            $this->output[$v] = formatCurrency($this->output[$v]);
        }
        if( $snapshotData = $this->getClientSnapshotData() ) 
        {
            $clientsdetails = $snapshotData["clientsdetails"];
            $customfields = array(  );
            foreach( $snapshotData["customfields"] as $data ) 
            {
                $data["fieldname"] = CustomField::getFieldName($data["id"], $data["fieldname"], $clientsdetails["language"]);
                $customfields[] = $data;
            }
        }
        else
        {
            if( !function_exists("getClientsDetails") ) 
            {
                require(ROOTDIR . "/includes/clientfunctions.php");
            }

            $clientsdetails = getClientsDetails($this->getData("userid"), "billing");
            $customfields = array(  );
            $result = select_query("tblcustomfields", "tblcustomfields.id,tblcustomfields.fieldname,(SELECT value FROM tblcustomfieldsvalues WHERE tblcustomfieldsvalues.fieldid=tblcustomfields.id AND tblcustomfieldsvalues.relid=" . (int) $this->getData("userid") . ") AS value", array( "type" => "client", "showinvoice" => "on" ));
            while( $data = mysql_fetch_assoc($result) ) 
            {
                if( $data["value"] ) 
                {
                    $data["fieldname"] = CustomField::getFieldName($data["id"], $data["fieldname"], $clientsdetails["language"]);
                    $customfields[] = $data;
                }

            }
        }

        $clientsdetails["country"] = $clientsdetails["countryname"];
        $this->output["clientsdetails"] = $clientsdetails;
        $this->output["customfields"] = $customfields;
        if( (0 < $this->getData("taxrate") || 0 < $this->getData("taxrate2")) && !function_exists("getTaxRate") ) 
        {
            $whmcs->load_function("invoice");
        }

        if( 0 < $this->getData("taxrate") ) 
        {
            $taxname = getTaxRate(1, $clientsdetails["state"], $clientsdetails["countrycode"]);
            $this->output["taxname"] = $taxname["name"];
        }
        else
        {
            $this->output["taxname"] = "";
            $this->output["taxrate"] = "0";
        }

        if( 0 < $this->getData("taxrate2") ) 
        {
            $taxname = getTaxRate(2, $clientsdetails["state"], $clientsdetails["countrycode"]);
            $this->output["taxname2"] = $taxname["name"];
        }
        else
        {
            $this->output["taxname2"] = "";
            $this->output["taxrate2"] = "0";
        }

        $this->output["statuslocale"] = $whmcs->get_lang("invoices" . strtolower($this->output["status"]));
        if( $this->output["status"] == "Payment Pending" ) 
        {
            $this->output["statuslocale"] = $whmcs->get_lang("invoicesPaymentPending");
        }

        if( $this->isProformaInvoice() ) 
        {
            $this->output["pagetitle"] = $whmcs->get_lang("proformainvoicenumber") . $this->getData("invoicenum");
        }
        else
        {
            $this->output["pagetitle"] = $whmcs->get_lang("invoicenumber") . $this->getData("invoicenum");
        }

        $this->output["payto"] = nl2br(Config\Setting::getValue("InvoicePayTo"));
        $this->output["notes"] = nl2br($this->output["notes"]);
        $this->output["subscrid"] = get_query_val("tblinvoiceitems", "tblhosting.subscriptionid", "tblinvoiceitems.type='Hosting' AND tblinvoiceitems.invoiceid=" . $this->getData("id") . " AND tblhosting.subscriptionid!=''", "tblhosting`.`id", "ASC", "", "tblhosting ON tblhosting.id=tblinvoiceitems.relid");
        $clienttotals = get_query_vals("tblinvoices", "SUM(credit),SUM(total)", array( "userid" => $this->getData("userid"), "status" => "Unpaid" ));
        $unpaidInvoiceIds = \Illuminate\Database\Capsule\Manager::table("tblinvoices")->where("status", "Unpaid")->where("userid", (int) $this->getData("userid"))->lists("id");
        $alldueinvoicespayments = 0;
        if( $unpaidInvoiceIds ) 
        {
            $alldueinvoicespayments = get_query_val("tblaccounts", "SUM(amountin-amountout)", "tblaccounts.invoiceid IN (" . db_build_in_array($unpaidInvoiceIds) . ")");
        }

        $this->output["clienttotaldue"] = formatCurrency($clienttotals[0] + $clienttotals[1]);
        $this->output["clientpreviousbalance"] = formatCurrency($clienttotals[1] - $this->getData("total"));
        $this->output["clientbalancedue"] = formatCurrency($clienttotals[1] - $alldueinvoicespayments);
        $lastpayment = get_query_vals("tblaccounts", "(amountin-amountout),transid", array( "invoiceid" => $this->getData("id") ), "id", "DESC");
        $this->output["lastpaymentamount"] = formatCurrency($lastpayment[0]);
        $this->output["lastpaymenttransid"] = $lastpayment[1];
    }

    public function getOutput($pdf = false)
    {
        $this->loadData(false);
        $existingLanguage = getUsersLang($this->data["userid"]);
        $this->formatForOutput();
        if( $existingLanguage ) 
        {
            swapLang($existingLanguage);
        }

        if( $pdf ) 
        {
            $this->makePDFFriendly();
        }

        return $this->output;
    }

    public function initialiseGatewayAndParams($passedInGatewayModuleName = "")
    {
        global $_LANG;
        $this->gateway = new Module\Gateway();
        if( $passedInGatewayModuleName ) 
        {
            $gatewaymodule = $passedInGatewayModuleName;
        }
        else
        {
            $gatewaymodule = $this->getData("paymentmodule");
        }

        if( !$this->gateway->isActiveGateway($gatewaymodule) ) 
        {
            if( $passedInGatewayModuleName ) 
            {
                throw new Exception\Module\NotActivated("Gateway Module '" . Input\Sanitize::makeSafeForOutput($gatewaymodule) . "' Not Activated");
            }

            $gatewaymodule = $this->gateway->getFirstAvailableGateway();
            if( !$gatewaymodule ) 
            {
                throw new Exception\Information("No Gateway Modules are Currently Active");
            }

            update_query("tblinvoices", array( "paymentmethod" => $gatewaymodule ), array( "id" => $this->getID() ));
        }

        if( !$this->gateway->load($gatewaymodule) ) 
        {
            logActivity("Gateway Module '" . $gatewaymodule . "' is Missing");
            throw new Exception\Module\NotServicable("Gateway Module '" . Input\Sanitize::makeSafeForOutput($gatewaymodule) . "' is Missing or Invalid");
        }

        $params = $this->gateway->loadSettings();
        if( !$params ) 
        {
            throw new Exception\Module\InvalidConfiguration("No Gateway Settings Found");
        }

        $params["companyname"] = Config\Setting::getValue("CompanyName");
        $params["systemurl"] = \App::getSystemURL();
        $params["langpaynow"] = $_LANG["invoicespaynow"];
        return $params;
    }

    public function getGatewayInvoiceParams(array $params = array(  ))
    {
        global $whmcs;
        global $_LANG;
        if( count($params) < 1 ) 
        {
            try
            {
                $params = $this->initialiseGatewayAndParams();
            }
            catch( Exception $e ) 
            {
                logActivity("Failed to initialise payment gateway module: " . $e->getMessage());
                throw new Exception\Fatal("Could not initialise payment gateway. Please contact support.");
            }
        }

        $invoiceid = $this->getID();
        $userid = $this->getData("userid");
        $invoicenum = $this->getData("invoicenum");
        $balance = $this->getData("balance");
        $result = select_query("tblclients", "tblclients.currency,tblcurrencies.code", array( "tblclients.id" => $userid ), "", "", "", "tblcurrencies ON tblcurrencies.id=tblclients.currency");
        $data = mysql_fetch_array($result);
        $invoice_currency_id = $data["currency"];
        $invoice_currency_code = $data["code"];
        $params["invoiceid"] = $invoiceid;
        $params["invoicenum"] = $invoicenum;
        $params["amount"] = $balance;
        $params["description"] = $params["companyname"] . " - " . $_LANG["invoicenumber"] . (($invoicenum ? $invoicenum : $invoiceid));
        $params["returnurl"] = $params["systemurl"] . "viewinvoice.php?id=" . $invoiceid;
        $params["dueDate"] = $this->getData("duedate");
        $client = new Client($userid);
        $clientsdetails = $client->getDetails("billing");
        $clientsdetails["state"] = $clientsdetails["statecode"];
        $params["clientdetails"] = $clientsdetails;
        if( isset($params["convertto"]) && $params["convertto"] ) 
        {
            $result = select_query("tblcurrencies", "code", array( "id" => (int) $params["convertto"] ));
            $data = mysql_fetch_array($result);
            $converto_currency_code = $data["code"];
            $converto_amount = convertCurrency($balance, $invoice_currency_id, $params["convertto"]);
            $params["amount"] = format_as_currency($converto_amount);
            $params["currency"] = $converto_currency_code;
            $params["currencyId"] = (int) $params["convertto"];
            $params["basecurrencyamount"] = format_as_currency($balance);
            $params["basecurrency"] = $invoice_currency_code;
            $params["baseCurrencyId"] = $invoice_currency_id;
        }

        if( !isset($params["currency"]) || !$params["currency"] ) 
        {
            $params["amount"] = format_as_currency($balance);
            $params["currency"] = $invoice_currency_code;
            $params["currencyId"] = $invoice_currency_id;
        }

        return $params;
    }

    public function getPaymentLink()
    {
        try
        {
            $params = $this->initialiseGatewayAndParams();
        }
        catch( Exception $e ) 
        {
            logActivity("Failed to initialise payment gateway module: " . $e->getMessage());
            return false;
        }
        $params = $this->getGatewayInvoiceParams($params);
        if( !$this->gateway->functionExists("link") ) 
        {
            eval("function " . $this->gateway->getLoadedModule() . "_link(\$params) { return '<form method=\"post\" action=\"'.\$params['systemurl'].'creditcard.php\" name=\"paymentfrm\"><input type=\"hidden\" name=\"invoiceid\" value=\"'.\$params['invoiceid'].'\"><button type=\"submit\" class=\"btn btn-success btn-sm\" id=\"btnPayNow\"><i class=\"fa fa-credit-card\"></i>&nbsp; ' . \$params['langpaynow'] . '</button></form>'; }");
        }

        $paymentbutton = $this->gateway->call("link", $params);
        return $paymentbutton;
    }

    public function getLineItems($entitydecode = false)
    {
        $whmcs = \DI::make("app");
        getUsersLang($this->getData("userid"));
        $invoiceid = $this->getID();
        $invoiceitems = array(  );
        if( Config\Setting::getValue("GroupSimilarLineItems") ) 
        {
            $result = full_query("SELECT COUNT(*) as qty,id,type,relid,description,amount,taxed FROM tblinvoiceitems WHERE invoiceid=" . (int) $invoiceid . " GROUP BY `description`,`amount` ORDER BY id ASC");
        }
        else
        {
            $result = select_query("tblinvoiceitems", "0 as qty,id,type,relid,description,amount,taxed", array( "invoiceid" => $invoiceid ), "id", "ASC");
        }

        while( $data = mysql_fetch_array($result) ) 
        {
            $qty = $data["qty"];
            $description = $data["description"];
            $amount = $data["amount"];
            $taxed = ($data["taxed"] ? true : false);
            if( 1 < $qty ) 
            {
                $description = $qty . " x " . $description . " @ " . $amount . $whmcs->get_lang("invoiceqtyeach");
                $amount *= $qty;
            }

            if( $entitydecode ) 
            {
                $description = htmlspecialchars(Input\Sanitize::decode($description));
            }
            else
            {
                $description = nl2br($description);
            }

            $invoiceitems[] = array( "id" => (int) $data["id"], "type" => $data["type"], "relid" => (int) $data["relid"], "description" => $description, "rawamount" => $amount, "amount" => formatCurrency($amount), "taxed" => $taxed );
        }
        return $invoiceitems;
    }

    public function getTransactions()
    {
        $invoiceid = $this->invoiceid;
        $transactions = array(  );
        $result = select_query("tblaccounts", "id,date,transid,amountin,amountout,(SELECT value FROM tblpaymentgateways WHERE gateway=tblaccounts.gateway AND setting='name' LIMIT 1) AS gateway", array( "invoiceid" => $invoiceid ), "date` ASC,`id", "ASC");
        while( $data = mysql_fetch_array($result) ) 
        {
            $tid = $data["id"];
            $date = $data["date"];
            $gateway = $data["gateway"];
            $amountin = $data["amountin"];
            $amountout = $data["amountout"];
            $transid = $data["transid"];
            $date = fromMySQLDate($date, 0, 1);
            if( !$gateway ) 
            {
                $gateway = "-";
            }

            $transactions[] = array( "id" => $tid, "date" => $date, "gateway" => $gateway, "transid" => $transid, "amount" => formatCurrency($amountin - $amountout) );
        }
        return $transactions;
    }

    public function pdfCreate()
    {
        $this->pdf = new PDF();
        return $this->pdf;
    }

    public function makePDFFriendly()
    {
        $this->output["companyname"] = Config\Setting::getValue("CompanyName");
        $this->output["companyurl"] = Config\Setting::getValue("Domain");
        $companyAddress = Config\Setting::getValue("InvoicePayTo");
        $this->output["companyaddress"] = explode("\n", $companyAddress);
        if( trim($this->output["notes"]) ) 
        {
            $this->output["notes"] = str_replace("<br />", "", $this->output["notes"]) . "\n";
        }

        $this->output = Input\Sanitize::decode($this->output);
        return true;
    }

    public function pdfInvoicePage($invoiceid = "")
    {
        global $currency;
        $whmcs = \DI::make("app");
        if( $invoiceid ) 
        {
            $this->setID($invoiceid);
            $invoiceexists = $this->loadData();
            if( !$invoiceexists ) 
            {
                return false;
            }

        }

        $tplvars = $this->getOutput(true);
        $invoiceitems = $this->getLineItems(true);
        $tplvars["invoiceitems"] = $invoiceitems;
        $transactions = $this->getTransactions();
        $tplvars["transactions"] = $transactions;
        $assetHelper = new View\Asset("");
        $tplvars["imgpath"] = $assetHelper->getFilesystemImgPath();
        $tplvars["pdfFont"] = Config\Setting::getValue("TCPDFFont");
        $this->pdfAddPage("invoicepdf.tpl", $tplvars);
        return true;
    }

    public function pdfAddPage($tplfile, $tplvars)
    {
        global $_LANG;
        $whmcs = \DI::make("app");
        $templateName = $whmcs->getClientAreaTemplate()->getName();
        if( !isValidforPath($templateName) ) 
        {
            throw new Exception\Fatal("Invalid System Template Name");
        }

        $tplFileExtension = "." . pathinfo($tplfile, PATHINFO_EXTENSION);
        $baseTplFilename = preg_replace("/" . $tplFileExtension . "\$/", "", $tplfile);
        $headerTplFile = ROOTDIR . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . $templateName . DIRECTORY_SEPARATOR . $baseTplFilename . "header" . $tplFileExtension;
        $footerTplFile = ROOTDIR . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . $templateName . DIRECTORY_SEPARATOR . $baseTplFilename . "footer" . $tplFileExtension;
        if( file_exists($headerTplFile) ) 
        {
            $this->pdf->setHeaderTplFile($headerTplFile);
        }

        if( file_exists($footerTplFile) ) 
        {
            $this->pdf->setFooterTplFile($footerTplFile);
        }

        $this->pdf->setTemplateVars($tplvars);
        $this->pdf->setPrintHeader(true);
        $this->pdf->setPrintFooter(true);
        $this->pdf->AddPage();
        $this->pdf->SetFont(Config\Setting::getValue("TCPDFFont"), "", 10);
        $this->pdf->SetTextColor(0);
        foreach( $tplvars as $k => $v ) 
        {
            ${$k} = $v;
        }
        $pdf =& $this->pdf;
        include(ROOTDIR . "/templates/" . $templateName . "/" . $tplfile);
        return true;
    }

    public function pdfOutput()
    {
        return $this->pdf->Output("", "S");
    }

    public function getInvoices($status = "", $userid = "", $orderby = "id", $sort = "DESC", $limit = "", $excludeDraftInvoices = true)
    {
        if( !function_exists("getInvoiceStatusColour") ) 
        {
            require(ROOTDIR . "/includes/invoicefunctions.php");
        }

        $whmcs = \DI::make("app");
        $where = array(  );
        if( $status ) 
        {
            $where[] = "status = '" . db_escape_string($status) . "'";
        }

        if( $userid ) 
        {
            $where[] = "userid = " . (int) $userid;
        }

        if( $excludeDraftInvoices ) 
        {
            $where[] = "status != 'Draft'";
        }

        $where[] = "(select count(id) from tblinvoiceitems where invoiceid=tblinvoices.id and type='Invoice')<=0";
        $invoices = array(  );
        $result = select_query("tblinvoices", "tblinvoices.*,total-IFNULL((SELECT SUM(amountin-amountout) FROM tblaccounts WHERE tblaccounts.invoiceid=tblinvoices.id),0) AS balance", implode(" AND ", $where), $orderby, $sort, $limit);
        while( $data = mysql_fetch_array($result) ) 
        {
            $id = $data["id"];
            $invoicenum = $data["invoicenum"];
            $date = $data["date"];
            $normalisedDate = $date;
            $duedate = $data["duedate"];
            $normalisedDueDate = $duedate;
            $credit = $data["credit"];
            $total = $data["total"];
            $balance = $data["balance"];
            $status = $data["status"];
            if( $status == "Unpaid" ) 
            {
                $this->totalbalance += $balance;
            }

            $date = fromMySQLDate($date, 0, 1);
            $duedate = fromMySQLDate($duedate, 0, 1);
            $rawstatus = strtolower($status);
            if( !$invoicenum ) 
            {
                $invoicenum = $id;
            }

            $totalnum = $credit + $total;
            $statusText = $whmcs->get_lang("invoices" . $rawstatus);
            if( $rawstatus == "payment pending" ) 
            {
                $statusText = $whmcs->get_lang("invoicesPayment Pending");
            }

            $invoices[] = array( "id" => $id, "invoicenum" => $invoicenum, "datecreated" => $date, "normalisedDateCreated" => $normalisedDate, "datedue" => $duedate, "normalisedDateDue" => $normalisedDueDate, "totalnum" => $totalnum, "total" => formatCurrency($totalnum), "balance" => formatCurrency($balance), "status" => getInvoiceStatusColour($status), "statusClass" => View\Helper::generateCssFriendlyClassName($status), "rawstatus" => $rawstatus, "statustext" => $statusText );
        }
        return $invoices;
    }

    public function getTotalBalance()
    {
        return $this->totalbalance;
    }

    public function getTotalBalanceFormatted()
    {
        return formatCurrency($this->getTotalBalance());
    }

    public function getEmailTemplates()
    {
        $names = array( "Invoice Created", "Credit Card Invoice Created", "Invoice Payment Reminder", "First Invoice Overdue Notice", "Second Invoice Overdue Notice", "Third Invoice Overdue Notice", "Credit Card Payment Due", "Credit Card Payment Failed", "Invoice Payment Confirmation", "Credit Card Payment Confirmation", "Invoice Refund Confirmation" );
        switch( $this->getData("status") ) 
        {
            case "Paid":
                $extraNames = array( "Invoice Payment Confirmation", "Credit Card Payment Confirmation" );
                break;
            case "Refunded":
                $extraNames = array( "Invoice Refund Confirmation" );
                break;
            default:
                $extraNames = array(  );
                break;
        }
        $sortedTemplates = array(  );
        $names = array_merge($extraNames, $names);
        $templates = Mail\Template::where("type", "=", "invoice")->where("language", "=", "")->whereIn("name", $names)->get();
        foreach( $names as $name ) 
        {
            foreach( $templates as $i => $template ) 
            {
                if( $template->name == $name ) 
                {
                    $sortedTemplates[] = $template;
                    unset($templates[$i]);
                    continue;
                }

            }
        }
        return $sortedTemplates;
    }

    public function getFriendlyPaymentMethod()
    {
        global $aInt;
        $credit = $this->getData("credit");
        $result = select_query("tblaccounts", "COUNT(id),SUM(amountin)-SUM(amountout)", array( "invoiceid" => $this->getData("id") ));
        $data = mysql_fetch_array($result);
        list($transcount, $amountpaid) = $data;
        if( $this->getData("status") == "Unpaid" ) 
        {
            $paymentmethodfriendly = $this->getData("paymentmethod");
        }
        else
        {
            if( $transcount == 0 ) 
            {
                $paymentmethodfriendly = $aInt->lang("invoices", "notransapplied");
            }
            else
            {
                $paymentmethodfriendly = $this->getData("paymentmethod");
            }

        }

        if( 0 < $credit ) 
        {
            if( $total == 0 ) 
            {
                $paymentmethodfriendly = $aInt->lang("invoices", "fullypaidcredit");
            }
            else
            {
                $paymentmethodfriendly .= " + " . $aInt->lang("invoices", "partialcredit");
            }

        }

        return $paymentmethodfriendly;
    }

    public function getBalanceFormatted()
    {
        global $currency;
        $userid = $this->getData("userid");
        $currency = getCurrency($userid);
        $balance = $this->getData("balance");
        return "<span class=\"" . ((0 < $balance ? "textred" : "textgreen")) . "\">" . formatCurrency($balance) . "</span>";
    }

    public function sendEmailTpl($tplname)
    {
        return sendMessage($tplname, (int) $this->getData("id"));
    }

    public function isAddFundsInvoice()
    {
        $numaddfunditems = get_query_val("tblinvoiceitems", "COUNT(id)", array( "invoiceid" => $this->getID(), "type" => "AddFunds" ));
        $numtotalitems = get_query_val("tblinvoiceitems", "COUNT(id)", array( "invoiceid" => $this->getID() ));
        return ($numaddfunditems == $numtotalitems ? true : false);
    }

    public static function isValidCustomInvoiceNumberFormat($format)
    {
        $replaceValues = array( "{YEAR}", "{MONTH}", "{DAY}", "{NUMBER}" );
        $replaceData = array( date("Y"), date("m"), date("d"), "1" );
        $format = str_replace($replaceValues, $replaceData, $format);
        $cleanedPopulatedFormat = preg_replace("/[^[:word:] {}!@€#£\$&()-=+\\[\\]]/", "", $format);
        if( $cleanedPopulatedFormat == $format ) 
        {
            return true;
        }

        return false;
    }

    public function isProformaInvoice()
    {
        if( Config\Setting::getValue("EnableProformaInvoicing") && $this->getData("status") != "Paid" ) 
        {
            return true;
        }

        return false;
    }

    public static function saveClientSnapshotData($invoiceId)
    {
        if( !Config\Setting::getValue("StoreClientDataSnapshotOnInvoiceCreation") ) 
        {
            return false;
        }

        try
        {
            $invoice = Billing\Invoice::findOrFail($invoiceId);
        }
        catch( \Exception $e ) 
        {
            \Log::debug("Invoice Save Client Data Snapshot: Got invalid invoice id or client missing");
            return false;
        }
        $client = new Client($invoice->client);
        $clientsDetails = $client->getDetails("billing");
        unset($clientsDetails["model"]);
        $customFields = array(  );
        $result = select_query("tblcustomfields", "tblcustomfields.id,tblcustomfields.fieldname,(SELECT value FROM tblcustomfieldsvalues WHERE tblcustomfieldsvalues.fieldid=tblcustomfields.id AND tblcustomfieldsvalues.relid=" . (int) $invoice->userId . ") AS value", array( "type" => "client", "showinvoice" => "on" ));
        while( $data = mysql_fetch_assoc($result) ) 
        {
            if( $data["value"] ) 
            {
                $customFields[] = $data;
            }

        }
        Billing\Invoice\Data::firstOrCreate(array( "invoiceid" => $invoiceId, "clientsdetails" => $clientsDetails, "customfields" => $customFields ));
        return true;
    }

    protected function getClientSnapshotData()
    {
        if( !Config\Setting::getValue("StoreClientDataSnapshotOnInvoiceCreation") ) 
        {
            return null;
        }

        try
        {
            $snapshotData = Billing\Invoice\Data::findOrFail($this->getID());
            return array( "clientsdetails" => $snapshotData->clientsDetails, "customfields" => $snapshotData->customFields );
        }
        catch( \Exception $e ) 
        {
            return null;
        }
    }

    public static function getUserIdByInvoiceId($invoiceId)
    {
        return \Illuminate\Database\Capsule\Manager::table("tblinvoices")->where("id", "=", (int) $invoiceId)->value("userid");
    }

    public function isAssignedGatewayWithDelayedCallbacks()
    {
        return in_array($this->getData("paymentmodule"), $this->gatewayModulesWhereCallbacksMightBeDelayed);
    }

    public function showPaymentSuccessAwaitingNotificationMsg($paymentSuccessful = false)
    {
        return $paymentSuccessful == true && $this->getData("status") == "Unpaid" && $this->isAssignedGatewayWithDelayedCallbacks();
    }

}



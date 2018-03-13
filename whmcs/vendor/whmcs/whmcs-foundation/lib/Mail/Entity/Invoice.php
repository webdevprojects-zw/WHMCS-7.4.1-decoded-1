<?php 
namespace WHMCS\Mail\Entity;


class Invoice extends \WHMCS\Mail\Emailer
{
    protected function getEntitySpecificMergeData($invoiceId)
    {
        $invoice = new \WHMCS\Invoice($invoiceId);
        $valid = $invoice->loadData();
        if( !$valid ) 
        {
            throw new \WHMCS\Exception("Invalid invoice id provided");
        }

        $sysurl = \App::getSystemURL();
        $data = $invoice->getOutput();
        $userid = $data["userid"];
        $this->setRecipient($userid);
        $invoicedescription = "";
        $invoiceitems = $invoice->getLineItems();
        foreach( $invoiceitems as $item ) 
        {
            $lines = preg_split("/<br \\/>(\\r\\n|\\n)/", $item["description"]);
            foreach( $lines as $line ) 
            {
                $invoicedescription .= trim($line . " " . $item["amount"]) . "<br>\n";
                $item["amount"] = "";
            }
        }
        $invoicedescription .= str_repeat("-", 54) . "<br>\n";
        $invoicedescription .= \Lang::trans("invoicessubtotal") . ": " . $data["subtotal"] . "<br>\n";
        if( 0 < $data["taxrate"] ) 
        {
            $invoicedescription .= $data["taxrate"] . "% " . $data["taxname"] . ": " . $data["tax"] . "<br>\n";
        }

        if( 0 < $data["taxrate2"] ) 
        {
            $invoicedescription .= $data["taxrate2"] . "% " . $data["taxname2"] . ": " . $data["tax2"] . "<br>\n";
        }

        $invoicedescription .= \Lang::trans("invoicescredit") . ": " . $data["credit"] . "<br>\n";
        $invoicedescription .= \Lang::trans("invoicestotal") . ": " . $data["total"] . "";
        $email_merge_fields = array(  );
        $email_merge_fields["invoice_id"] = (int) $data["invoiceid"];
        $email_merge_fields["invoice_num"] = $data["invoicenum"];
        $email_merge_fields["invoice_date_created"] = $data["date"];
        $email_merge_fields["invoice_date_due"] = $data["duedate"];
        $email_merge_fields["invoice_date_paid"] = $data["datepaid"];
        $email_merge_fields["invoice_items"] = $invoiceitems;
        $email_merge_fields["invoice_html_contents"] = $invoicedescription;
        $email_merge_fields["invoice_subtotal"] = $data["subtotal"];
        $email_merge_fields["invoice_credit"] = $data["credit"];
        $email_merge_fields["invoice_tax"] = $data["tax"];
        $email_merge_fields["invoice_tax_rate"] = $data["taxrate"] . "%";
        $email_merge_fields["invoice_tax2"] = $data["tax2"];
        $email_merge_fields["invoice_tax_rate2"] = $data["taxrate2"] . "%";
        $email_merge_fields["invoice_total"] = $data["total"];
        $email_merge_fields["invoice_amount_paid"] = $data["amountpaid"];
        $email_merge_fields["invoice_balance"] = $data["balance"];
        $email_merge_fields["invoice_status"] = $data["statuslocale"];
        $email_merge_fields["invoice_last_payment_amount"] = $data["lastpaymentamount"];
        $email_merge_fields["invoice_last_payment_transid"] = $data["lastpaymenttransid"];
        $email_merge_fields["invoice_payment_link"] = ($invoice->getData("status") == "Unpaid" && 0 < $invoice->getData("balance") ? $invoice->getPaymentLink() : "");
        $email_merge_fields["invoice_payment_method"] = $data["paymentmethod"];
        $email_merge_fields["invoice_link"] = "<a href=\"" . $sysurl . "viewinvoice.php?id=" . $data["id"] . "\">" . $sysurl . "viewinvoice.php?id=" . $data["id"] . "</a>";
        $email_merge_fields["invoice_notes"] = $data["notes"];
        $email_merge_fields["invoice_subscription_id"] = $data["subscrid"];
        $email_merge_fields["invoice_previous_balance"] = $data["clientpreviousbalance"];
        $email_merge_fields["invoice_all_due_total"] = $data["clienttotaldue"];
        $email_merge_fields["invoice_total_balance_due"] = $data["clientbalancedue"];
        $this->massAssign($email_merge_fields);
        $existingLanguage = "";
        if( \WHMCS\Config\Setting::getValue("EnablePDFInvoices") ) 
        {
            $invoice->pdfCreate();
            $invoice->pdfInvoicePage();
            $this->message->addStringAttachment(\Lang::trans("invoicefilename") . $data["invoicenum"] . ".pdf", $invoice->pdfOutput());
        }

    }

}



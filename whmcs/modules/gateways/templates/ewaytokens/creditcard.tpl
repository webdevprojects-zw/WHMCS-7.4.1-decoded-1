{if $errorMessage}
    {if file_exists("templates/$template/includes/alert.tpl")}
        {include file="templates/$template/includes/alert.tpl" type="error" errorshtml=$errorMessage}
    {else}
        <div class="alert alert-danger">
            <strong>{$LANG.clientareaerrors}</strong>
            <ul>
                {$errorMessage}
            </ul>
        </div>
    {/if}
{elseif $infoMessage}
    {if file_exists("templates/$template/includes/alert.tpl")}
        {include file="templates/$template/includes/alert.tpl" type="info" msg=$infoMessage}
    {else}
        <div class="alert alert-info">
            <ul>
                {$infoMessage}
            </ul>
        </div>
    {/if}
{else}
    {if $success}
        {if file_exists("templates/$template/includes/alert.tpl")}
            {include file="templates/$template/includes/alert.tpl" type="success" textcenter=true msg=$LANG.changessavedsuccessfully}
        {else}
            <div class="alert alert-success text-center textcenter">
                {$LANG.changessavedsuccessfully}
            </div>
        {/if}
    {/if}
    <div id="frmRemoteCardProcess" class="text-center">
        <form method="POST" action="{$formActionURL}" id="payment_form" autocomplete="off"  class="form-horizontal text-left" role="form">
            <input type="hidden" name="EWAY_ACCESSCODE" value="{$accessCode}" />
            <input type="hidden" name="EWAY_PAYMENTTYPE" value="Credit Card" />
            <div class="row clearfix control-group">
                <div class="col-md-6 col2half">
                    <div class="form-group cc-details control-group">
                        <label for="idCardName" class="col-sm-4 control-label">{$LANG.clientareafullname}</label>
                        <div class="col-sm-7 controls">
                            <input type="text" name="EWAY_CARDNAME" id="idCardName" class="form-control" {if isset($cardName)}value="{$cardName}" {/if} data-toggle="manual" data-placement="top" title="{lang key='orderForm.required'}"/>
                        </div>
                    </div>
                    <div class="form-group cc-details control-group">
                        <label for="inputCardNumber" class="col-sm-4 control-label">{$LANG.creditcardcardnumber}</label>
                        <div class="col-sm-7 controls">
                            <input type="text" name="EWAY_CARDNUMBER" id="inputCardNumber" class="form-control" {if isset($ccNumber)}value="{$ccNumber}" {/if}data-toggle="manual" data-placement="top" title="{lang key='creditcardnumberinvalid'}" />
                        </div>
                    </div>
                    <div class="form-group cc-details control-group text-left">
                        <label for="inputCardExpiry" for="inputCardExpiryYear" class="col-sm-4 control-label">{$LANG.creditcardcardexpires}</label>
                        <div class="col-sm-7 controls">
                            <select name="EWAY_CARDEXPIRYMONTH" id="inputCardExpiry" class="form-control select-inline">
                                {foreach from=$monthOptions item=month}
                                    <option{if $ccExpiryMonth == $month} selected{/if}>{$month}</option>
                                {/foreach}
                            </select>
                            <select name="EWAY_CARDEXPIRYYEAR" id="inputCardExpiryYear" class="form-control select-inline">
                                {foreach from=$expiryOptions item=year}
                                    <option{if $ccExpiryYear == substr($year, -2)} selected{/if}>{$year}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group control-group text-left">
                        <label for="inputCardCvv" class="col-sm-4 control-label">{$LANG.creditcardcvvnumber}</label>
                        <div class="col-sm-7 controls">
                            <input type="number" name="EWAY_CARDCVN" id="inputCardCvv" class="form-control input-inline input-inline-100 input-mini" data-toggle="manual" data-placement="top" title="{lang key='creditcardccvinvalid'}" />
                        </div>
                    </div>
                    <div class="form-group textcenter text-center">
                        <div class="text-center">
                            <input type="submit" class="btn btn-primary btn-lg" value="{if $invoiceid}{$LANG.submitpayment}{else}{$LANG.update}{/if}" onclick="this.value='{$LANG.pleasewait}'" id="btnSubmit" />
                        </div>
                    </div>
                </div>
                <div class="col-md-5 col2half">
                    {if $invoiceid}
                        <div id="invoiceIdSummary" class="invoice-summary">
                            <h2 class="text-center textcenter">{$LANG.invoicenumber}{$invoiceid}</h2>
                            <div class="invoice-summary-table">
                                <table class="table table-condensed">
                                    <tr>
                                        <td class="text-center"><strong>{$LANG.invoicesdescription}</strong></td>
                                        <td width="150" class="text-center textcenter"><strong>{$LANG.invoicesamount}</strong></td>
                                    </tr>
                                    {foreach $invoiceitems as $item}
                                        <tr>
                                            <td>{$item.description}</td>
                                            <td class="text-center textcenter">{$item.amount}</td>
                                        </tr>
                                    {/foreach}
                                    <tr>
                                        <td class="total-row text-right textright">{$LANG.invoicessubtotal}</td>
                                        <td class="total-row text-center textcenter">{$invoice.subtotal}</td>
                                    </tr>
                                    {if $invoice.taxrate}
                                        <tr>
                                            <td class="total-row text-right textright">{$invoice.taxrate}% {$invoice.taxname}</td>
                                            <td class="total-row text-center textcenter">{$invoice.tax}</td>
                                        </tr>
                                    {/if}
                                    {if $invoice.taxrate2}
                                        <tr>
                                            <td class="total-row text-right textright">{$invoice.taxrate2}% {$invoice.taxname2}</td>
                                            <td class="total-row text-center textcenter">{$invoice.tax2}</td>
                                        </tr>
                                    {/if}
                                    <tr>
                                        <td class="total-row text-right textright">{$LANG.invoicescredit}</td>
                                        <td class="total-row text-center textcenter">{$invoice.credit}</td>
                                    </tr>
                                    <tr>
                                        <td class="total-row text-right textright">{$LANG.invoicestotaldue}</td>
                                        <td class="total-row text-center textcenter">{$invoice.total}</td>
                                    </tr>
                                </table>
                            </div>
                            <p class="text-center textcenter">
                                {$LANG.paymentstodate}: <strong>{$invoice.amountpaid}</strong><br />
                                {$LANG.balancedue}: <strong>{$balance}</strong>
                            </p>
                        </div>
                    {else}
                        <label for="storedBillingDetails" class="control-label full">
                            <strong>{$LANG.billingAddress}</strong>
                        </label>
                        <div id="storedBillingDetails">
                            {if $clientDetails.companyName}
                                {$clientDetails.companyName}
                            {else}
                                {$clientDetails.firstName} {$clientDetails.lastName}
                            {/if}<br />
                            {$clientDetails.address1}
                            {if $clientDetails.address2}
                                , {$clientDetails.address2}
                            {/if}<br />
                            {$clientDetails.city}, {$clientDetails.state}, {$clientDetails.postalCode}<br />
                            {$clientDetails.countryName}
                        </div>
                    {/if}
                </div>
            </div>
        </form>
    </div>
    <script src="{$BASE_PATH_JS}/jquery.payment.js"></script>
    <script type="text/javascript">
        jQuery(document).ready(function() {
            var ccValidation = {if (isset($ccNumber))}false{else}true{/if},
                cardNumber = jQuery('#inputCardNumber'),
                cardInitialValue = cardNumber.val(),
                allowSubmit = false;

            cardNumber.change(function(){
                ccValidation = jQuery(this).val() != cardInitialValue;
            });

            jQuery('#payment_form').submit(function(e) {
                if (allowSubmit) {
                    return true;
                }
                e.preventDefault();
                var cardName = jQuery('#idCardName'),
                    cardCvv = jQuery('#inputCardCvv'),
                    cardValid = jQuery.payment.validateCardNumber(cardNumber.val()),
                    cvvValid = jQuery.payment.validateCardCVC(
                        cardCvv.val(),
                        jQuery.payment.cardType(cardNumber.val())
                    ),
                    submitButton = jQuery('#btnSubmit'),
                    returnVal = true;

                if (!cardName.val()) {
                    cardName.tooltip('show');
                    returnVal = false;
                }

                if (ccValidation && !cardValid) {
                    cardNumber.tooltip('show');
                    returnVal = false;
                }

                if (!cvvValid) {
                    cardCvv.tooltip('show');
                    returnVal = false;
                }

                if (!returnVal) {
                    submitButton.val('{if $invoiceid}{lang key='submitpayment'}{else}{lang key='update'}{/if}');
                    return false;
                }
                cardName.tooltip('hide');
                cardNumber.tooltip('hide');
                cardCvv.tooltip('hide');
                allowSubmit = true;
                jQuery(this).trigger('submit');
            });
        });
    </script>
{/if}

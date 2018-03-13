<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<script type="text/javascript" src="{$BASE_PATH_JS}/StatesDropdown.js"></script>
<script type="text/javascript" src="{$BASE_PATH_JS}/PasswordStrength.js"></script>
<script type="text/javascript" src="{$BASE_PATH_JS}/CreditCardValidation.js"></script>
<script type="text/javascript" src="templates/orderforms/{$carttpl}/checkout.js"></script>
<script>
// Used by the JS function removeItem confirm box.
var removeItemText = '{$LANG.cartremoveitemconfirm|escape:"quotes"}';

</script>

<div id="order-boxes">

    {if !$checkout}

        <div class="header-lined">
            <h1>{$LANG.carttitle}</h1>
        </div>

        {if $bundlewarnings}
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <strong>{$LANG.bundlereqsnotmet}</strong><br />
                <ul>
                    {foreach from=$bundlewarnings item=warning}
                        <li>{$warning}</li>
                    {/foreach}
                </ul>
            </div>
        {/if}

        {if $errormessage}
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <strong>{$LANG.clientareaerrors}</strong>
                <ul>
                    {$errormessage}
                </ul>
            </div>
        {elseif $promotioncode && $rawdiscount eq "0.00"}
            <div class="errorbox">{$LANG.promoappliedbutnodiscount}</div>
        {/if}

        <form method="post" action="{$smarty.server.PHP_SELF}?a=view">

            <table class="styled">
                <tr>
                    <th width="55%">{$LANG.orderdesc}</td>
                    <th width="45%">{$LANG.orderprice}</td>
                </tr>
                {foreach key=num item=product from=$products}
                    <tr>
                        <td>
                            <strong><em>{$product.productinfo.groupname}</em> - {$product.productinfo.name}</strong>{if $product.domain} ({$product.domain}){/if}<br />
                            {if $product.configoptions}
                                {foreach key=confnum item=configoption from=$product.configoptions}
                                    &nbsp;&raquo; {$configoption.name}: {if $configoption.type eq 1 || $configoption.type eq 2}
                                        {$configoption.option}
                                    {elseif $configoption.type eq 3}
                                        {if $configoption.qty}{$LANG.yes}{else}{$LANG.no}{/if}
                                    {elseif $configoption.type eq 4}
                                        {$configoption.qty} x {$configoption.option}
                                    {/if}<br />
                                {/foreach}
                            {/if}
                            {if $product.allowqty}
                                <br /><br />
                                <div class="text-right">
                                    {$LANG.cartqtyenterquantity} <input type="text" name="qty[{$num}]" size="3" value="{$product.qty}" /> <input type="submit" value="{$LANG.cartqtyupdate}" />
                                </div>
                            {/if}
                            <div class="text-right">
                                <a href="{$smarty.server.PHP_SELF}?a=confproduct&i={$num}" class="btn btn-default btn-xs"><i class="fa fa-pencil"></i> {$LANG.carteditproductconfig}</a>
                                <a href="#" onclick="removeItem('p','{$num}');return false" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> {$LANG.cartremove}</a>
                            </div>
                        </td>
                        <td class="text-center">
                            <strong>
                                {$product.pricingtext}
                                {if $product.proratadate}<br />({$LANG.orderprorata} {$product.proratadate}){/if}
                            </strong>
                        </td>
                    </tr>
                    {foreach key=addonnum item=addon from=$product.addons}
                        <tr class="carttableproduct">
                            <td>
                                <strong>{$LANG.orderaddon}</strong> - {$addon.name}
                            </td>
                            <td class="text-center">
                                <strong>{$addon.pricingtext}</strong>
                            </td>
                        </tr>
                    {/foreach}
                {/foreach}
                {foreach key=num item=addon from=$addons}
                    <tr>
                        <td>
                            <strong>{$addon.name}</strong><br />
                            {$addon.productname}{if $addon.domainname} - {$addon.domainname}{/if}
                        </td>
                        <td class="text-center">
                            <strong>{$addon.pricingtext}</strong>
                        </td>
                    </tr>
                    <tr class="carttableconfig">
                        <td>
                            <a href="#" onclick="removeItem('a','{$num}');return false"class="textred">[{$LANG.cartremove}]</a>
                        </td>
                        <td>&nbsp;</td>
                    </tr>
                {/foreach}
                {foreach key=num item=domain from=$domains}
                    <tr>
                        <td>
                            <strong>{if $domain.type eq "register"}{$LANG.orderdomainregistration}{else}{$LANG.orderdomaintransfer}{/if}</strong> - {$domain.domain} - {$domain.regperiod} {$LANG.orderyears}<br />
                            {if $domain.dnsmanagement}&nbsp;&raquo; {$LANG.domaindnsmanagement}<br />{/if}
                            {if $domain.emailforwarding}&nbsp;&raquo; {$LANG.domainemailforwarding}<br />{/if}
                            {if $domain.idprotection}&nbsp;&raquo; {$LANG.domainidprotection}<br />{/if}
                        </td>
                        <td class="text-center">
                            <strong>{$domain.price}</strong>
                        </td>
                    </tr>
                    <tr class="carttableconfig">
                        <td>
                            <a href="{$smarty.server.PHP_SELF}?a=confdomains" class="textgreen">[{$LANG.cartconfigdomainextras}]</a>
                            <a href="#" onclick="removeItem('d','{$num}');return false" class="textred">[{$LANG.cartremove}]</a>
                        </td>
                        <td>&nbsp;</td>
                    </tr>
                {/foreach}
                {foreach key=num item=domain from=$renewals}
                    <tr>
                        <td>
                            <strong>{$LANG.domainrenewal}</strong> - {$domain.domain} - {$domain.regperiod} {$LANG.orderyears}<br />
                            {if $domain.dnsmanagement}&nbsp;&raquo; {$LANG.domaindnsmanagement}<br />{/if}
                            {if $domain.emailforwarding}&nbsp;&raquo; {$LANG.domainemailforwarding}<br />{/if}
                            {if $domain.idprotection}&nbsp;&raquo; {$LANG.domainidprotection}<br />{/if}
                        </td>
                        <td class="text-center">
                            <strong>{$domain.price}</strong>
                        </td>
                    </tr>
                    <tr class="carttableconfig">
                        <td>
                            <a href="#" onclick="removeItem('r','{$num}');return false" class="textred">[{$LANG.cartremove}]</a>
                        </td>
                        <td>&nbsp;</td>
                    </tr>
                {/foreach}
                {if $cartitems == 0}
                    <tr>
                        <td colspan="2" class="text-center">
                            <br />
                            {$LANG.cartempty}
                            <br /><br />
                        </td>
                    </tr>
                {/if}
                <tr class="carttablesummary">
                    <td class="left">{$LANG.ordersubtotal}: &nbsp;</td>
                    <td align="center">{$subtotal}</td>
                </tr>
                {if $promotioncode}
                    <tr class="carttablesummary">
                        <td class="left">{$promotiondescription}: &nbsp;</td>
                        <td align="center">{$discount}</td>
                    </tr>
                {/if}
                {if $taxrate}
                    <tr class="carttablesummary">
                        <td class="left">{$taxname} @ {$taxrate}%: &nbsp;</td>
                        <td align="center">{$taxtotal}</td>
                    </tr>
                {/if}
                {if $taxrate2}
                    <tr class="carttablesummary">
                        <td class="left">{$taxname2} @ {$taxrate2}%: &nbsp;</td>
                        <td align="center">{$taxtotal2}</td>
                    </tr>
                {/if}
                <tr class="carttablesummary">
                    <td class="left">{$LANG.ordertotalduetoday}: &nbsp;</td>
                    <td align="center">{$total}</td>
                </tr>
                {if $totalrecurringmonthly || $totalrecurringquarterly || $totalrecurringsemiannually || $totalrecurringannually || $totalrecurringbiennially || $totalrecurringtriennially}
                    <tr class="carttablesummary">
                        <td class="left">{$LANG.ordertotalrecurring}: &nbsp;</td>
                        <td align="center">
                            {if $totalrecurringmonthly}{$totalrecurringmonthly} {$LANG.orderpaymenttermmonthly}<br />{/if}
                            {if $totalrecurringquarterly}{$totalrecurringquarterly} {$LANG.orderpaymenttermquarterly}<br />{/if}
                            {if $totalrecurringsemiannually}{$totalrecurringsemiannually} {$LANG.orderpaymenttermsemiannually}<br />{/if}
                            {if $totalrecurringannually}{$totalrecurringannually} {$LANG.orderpaymenttermannually}<br />{/if}
                            {if $totalrecurringbiennially}{$totalrecurringbiennially} {$LANG.orderpaymenttermbiennially}<br />{/if}
                            {if $totalrecurringtriennially}{$totalrecurringtriennially} {$LANG.orderpaymenttermtriennially}<br />{/if}
                        </td>
                    </tr>
                {/if}
            </table>

        </form>

        <div class="row line-padded">
            <div class="col-sm-7 text-center">
                <div id="promoAddText">
                    <span class="text-muted">Have a promotion code? <a href="#" onclick="showPromoInput();return false">Click here to add it</a></span>
                </div>
                <div id="promoInput" class="hidden">
                    <div class="col-md-6 col-md-offset-3">
                        <form method="post" action="cart.php?a=view">
                            <div class="input-group">
                                <input type="text" name="promocode" class="form-control" placeholder="{$LANG.cartenterpromo}" />
                                <div class="input-group-btn">
                                    <input type="submit" name="validatepromo" value="{$LANG.orderpromovalidatebutton}" class="btn btn-warning" />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                {if $promotioncode}{$promotioncode} - {$promotiondescription}<br /><a href="{$smarty.server.PHP_SELF}?a=removepromo">{$LANG.orderdontusepromo}</a>{else}{/if}
            </div>
            <div class="col-sm-5 text-center">
                {foreach from=$gatewaysoutput item=gatewayoutput}
                    <div>{$gatewayoutput}</div>
                {/foreach}
                <form method="post" action="cart.php?a=checkout">
                    <a href="cart.php" class="btn btn-default"><i class="fa fa-shopping-cart"></i> {$LANG.continueshopping}</a>
                    <button type="submit" class="btn btn-primary btn-lg"{if $cartitems==0} disabled="disabled"{/if}>{$LANG.checkout} &nbsp;<i class="fa fa-arrow-circle-right"></i></button>
                </form>
            </div>
        </div>

    {else}

        <div class="header-lined">
            <h1>{$LANG.checkout}</h1>
        </div>

        {if $errormessage}
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <strong>{$LANG.clientareaerrors}</strong>
                <ul>
                    {$errormessage}
                </ul>
            </div>
        {/if}

        <form method="post" action="{$smarty.server.PHP_SELF}?a=checkout" name="orderfrm" id="frmCheckout">
            <input type="hidden" name="submit" value="true" />
            <input type="hidden" name="custtype" id="inputCustType" value="{$custtype}" />

            <div id="signupContainer"{if !$loggedin && $custtype eq "existing"} class="hidden"{/if}>

            {if !$loggedin}
                <div class="alert alert-warning" role="alert">
                    <strong>{$LANG.alreadyregistered}</strong> <a href="#" onclick="showLogin();return false" class="alert-link">{$LANG.clickheretologin}</a>
                </div>
            {/if}

            <div class="row">
                <div class="col-sm-6 form-horizontal">
                    <div class="form-group">
                        <label for="inputFirstName" class="col-md-4 control-label">{$LANG.clientareafirstname}</label>
                        <div class="col-md-8">
                            <input type="text" name="firstname" id="inputFirstName" value="{$clientsdetails.firstname}" class="form-control"{if $loggedin} disabled="disabled"{/if} />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputLastName" class="col-md-4 control-label">{$LANG.clientarealastname}</label>
                        <div class="col-md-8">
                            <input type="text" name="lastname" id="inputLastName" value="{$clientsdetails.lastname}" class="form-control"{if $loggedin} disabled="disabled"{/if} />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputCompanyName" class="col-md-4 control-label">{$LANG.clientareacompanyname}</label>
                        <div class="col-md-8">
                            <input type="text" name="companyname" id="inputCompanyName" value="{$clientsdetails.companyname}" class="form-control"{if $loggedin} disabled="disabled"{/if} />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputEmail" class="col-md-4 control-label">{$LANG.clientareaemail}</label>
                        <div class="col-md-8">
                            <input type="email" name="email" id="inputEmail" value="{$clientsdetails.email}" class="form-control"{if $loggedin} disabled="disabled"{/if} />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputPhone" class="col-md-4 control-label">{$LANG.clientareaphonenumber}</label>
                        <div class="col-md-8">
                            <input type="text" name="phonenumber" id="inputPhone" value="{$clientsdetails.phonenumber}" class="form-control"{if $loggedin} disabled="disabled"{/if} />
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 form-horizontal">
                    <div class="form-group">
                        <label for="inputAddress1" class="col-md-4 control-label">{$LANG.clientareaaddress1}</label>
                        <div class="col-md-8">
                            <input type="text" name="address1" id="inputAddress1" value="{$clientsdetails.address1}" class="form-control"{if $loggedin} disabled="disabled"{/if} />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputAddress2" class="col-md-4 control-label">{$LANG.clientareaaddress2}</label>
                        <div class="col-md-8">
                            <input type="text" name="address2" id="inputAddress2" value="{$clientsdetails.address2}" class="form-control"{if $loggedin} disabled="disabled"{/if} />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputCity" class="col-md-4 control-label">{$LANG.clientareacity}</label>
                        <div class="col-md-8">
                            <input type="text" name="city" id="inputCity" value="{$clientsdetails.city}" class="form-control"{if $loggedin} disabled="disabled"{/if} />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputState" class="col-md-4 control-label">{$LANG.clientareastate}</label>
                        <div class="col-md-8">
                            <input type="text" name="state" id="inputState" value="{$clientsdetails.state}" class="form-control"{if $loggedin} disabled="disabled"{/if} />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputPostcode" class="col-md-4 control-label">{$LANG.clientareapostcode}</label>
                        <div class="col-md-8">
                            <input type="text" name="postcode" id="inputPostcode" value="{$clientsdetails.postcode}" class="form-control"{if $loggedin} disabled="disabled"{/if} />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputCountry" class="col-md-4 control-label">{$LANG.clientareacountry}</label>
                        <div class="col-md-8">
                            <select name="country" id="inputCountry" class="form-control"{if $loggedin} disabled="disabled"{/if}>
                            {foreach from=$countries key=countrycode item=countrylabel}
                                <option value="{$countrycode}"{if (!$country && $countrycode == $defaultcountry) || $countrycode eq $country} selected{/if}>{$countrylabel}</option>
                            {/foreach}
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {if $taxenabled && !$loggedin}
                <div class="line-padded text-center">
                    {$LANG.carttaxupdateselections}
                    <button type="submit" name="updateonly" class="btn btn-info btn-sm" /><i class="fa fa-refresh"></i> {$LANG.carttaxupdateselectionsupdate}</button>
                </div>
            {/if}

            {if $customfields}
                <h2>{$LANG.orderadditionalrequiredinfo}</h2>
                <div class="row">
                    {foreach $customfields as $customfield}
                        <div class="form-horizontal">
                            <label class="col-sm-5 control-label" for="customfield{$customfield.id}">
                                {$customfield.name}
                            </label>
                            <div class="col-sm-6 col-md-5">
                                {$customfield.input}
                                {if $customfield.description}
                                    <span id="helpBlock" class="help-block">
                                        {$customfield.description}
                                    </span>
                                {/if}
                            </div>
                        </div>
                    {/foreach}
                </div>
            {/if}

            {if !$loggedin}
                <h2>Choose a Password</h2>
                <div class="form-horizontal">
                    <div id="newPassword1" class="form-group has-feedback">
                        <label for="inputNewPassword1" class="col-sm-5 control-label">{$LANG.clientareapassword}</label>
                        <div class="col-sm-6 col-md-5">
                            <input type="password" name="password" id="inputNewPassword1" data-error-threshold="{$pwStrengthErrorThreshold}" data-warning-threshold="{$pwStrengthWarningThreshold}" value="{$password}" class="form-control" />
                            <span class="form-control-feedback glyphicon"></span>
                            {if file_exists("templates/$template/includes/pwstrength.tpl")}
                                {include file="$template/includes/pwstrength.tpl"}
                            {elseif file_exists("templates/six/includes/pwstrength.tpl")}
                                {include file="six/includes/pwstrength.tpl"}
                            {/if}
                        </div>
                    </div>
                    <div id="newPassword2" class="form-group has-feedback">
                        <label for="inputNewPassword2" class="col-sm-5 control-label">{$LANG.clientareaconfirmpassword}</label>
                        <div class="col-sm-6 col-md-5">
                            <input type="password" name="password2" id="inputNewPassword2" value="{$password2}" class="form-control" />
                            <span class="form-control-feedback glyphicon"></span>
                            <div id="inputNewPassword2Msg">
                            </div>
                        </div>
                    </div>
                    {if $securityquestions}
                        <div class="form-group">
                            <label for="inputSecurityQId" class="col-sm-5 control-label">{$LANG.clientareasecurityquestion}</label>
                            <div class="col-sm-7">
                                <select name="securityqid" id="inputSecurityQId" class="form-control select-autowidth">
                                {foreach from=$securityquestions item=question}
                                    <option value="{$question.id}"{if $question.id eq $securityqid} selected{/if}>{$question.question}</option>
                                {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputSecurityQAns" class="col-sm-5 control-label">{$LANG.clientareasecurityanswer}</label>
                            <div class="col-sm-6 col-md-5">
                                <input type="password" name="securityqans" id="inputSecurityQAns" value="{$securityqans}" class="form-control" />
                            </div>
                        </div>
                    {/if}
                </div>
            {/if}

            </div>

            <div id="loginContainer"{if $loggedin || $custtype neq "existing"} class="hidden"{/if}>

                <div class="alert alert-warning" role="alert">
                    <strong>Not Registered?</strong> <a href="#" onclick="showSignup();return false" class="alert-link">Click here to signup as a new user</a>
                </div>

                <div class="form-horizontal">
                    <div class="form-group">
                        <label for="inputLoginEmail" class="col-sm-4 control-label">{$LANG.loginemail}</label>
                        <div class="col-sm-6">
                            <input type="text" name="loginemail" id="inputLoginEmail" class="form-control" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputLoginPassword" class="col-sm-4 control-label">{$LANG.loginpassword}</label>
                        <div class="col-sm-6">
                            <input type="password" name="loginpw" id="inputLoginPassword" class="form-control" />
                        </div>
                    </div>
                </div>

                <div class="line-padded text-center">
                    <strong>{$LANG.loginforgotten}</strong> <a href="pwreset.php" target="_blank">{$LANG.loginforgotteninstructions}</a>
                </div>

            </div>

            {if $domainsinorder}
                <h2>{$LANG.domainregistrantinfo}</h2>

                <div class="form-horizontal">
                    <div class="form-group">
                        <label for="inputDomainContact" class="col-sm-5 control-label">{$LANG.domainregistrantchoose}</label>
                        <div class="col-sm-6">
                            <select name="contact" id="inputDomainContact" onchange="domainContactChange()" class="form-control">
                                <option value="">{$LANG.usedefaultcontact}</option>
                                {foreach from=$domaincontacts item=domcontact}
                                    <option value="{$domcontact.id}"{if $contact==$domcontact.id} selected{/if}>{$domcontact.name}</option>
                                {/foreach}
                                <option value="addingnew"{if $contact eq "addingnew"} selected{/if}>{$LANG.clientareanavaddcontact}...</option>
                        </select>
                        </div>
                    </div>
                </div>

                <div id="domainContactContainer" class="row{if $contact neq "addingnew"} hidden{/if}">
                    <div class="col-sm-6 form-horizontal">
                        <div class="form-group">
                            <label for="inputDCFirstName" class="col-md-4 control-label">{$LANG.clientareafirstname}</label>
                            <div class="col-md-8">
                                <input type="text" name="domaincontactfirstname" id="inputDCFirstName" value="{$domaincontact.firstname}" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputDCLastName" class="col-md-4 control-label">{$LANG.clientarealastname}</label>
                            <div class="col-md-8">
                                <input type="text" name="domaincontactlastname" id="inputDCLastName" value="{$domaincontact.lastname}" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputDCCompanyName" class="col-md-4 control-label">{$LANG.clientareacompanyname}</label>
                            <div class="col-md-8">
                                <input type="text" name="domaincontactcompanyname" id="inputDCCompanyName" value="{$domaincontact.companyname}" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputDCEmail" class="col-md-4 control-label">{$LANG.clientareaemail}</label>
                            <div class="col-md-8">
                                <input type="email" name="domaincontactemail" id="inputDCEmail" value="{$domaincontact.email}" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputDCPhone" class="col-md-4 control-label">{$LANG.clientareaphonenumber}</label>
                            <div class="col-md-8">
                                <input type="text" name="domaincontactphonenumber" id="inputDCPhone" value="{$domaincontact.phonenumber}" class="form-control" />
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 form-horizontal">
                        <div class="form-group">
                            <label for="inputDCAddress1" class="col-md-4 control-label">{$LANG.clientareaaddress1}</label>
                            <div class="col-md-8">
                                <input type="text" name="domaincontactaddress1" id="inputDCAddress1" value="{$domaincontact.address1}" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputDCAddress2" class="col-md-4 control-label">{$LANG.clientareaaddress2}</label>
                            <div class="col-md-8">
                                <input type="text" name="domaincontactaddress2" id="inputDCAddress2" value="{$domaincontact.address2}" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputDCCity" class="col-md-4 control-label">{$LANG.clientareacity}</label>
                            <div class="col-md-8">
                                <input type="text" name="domaincontactcity" id="inputDCCity" value="{$domaincontact.city}" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputDCState" class="col-md-4 control-label">{$LANG.clientareastate}</label>
                            <div class="col-md-8">
                                <input type="text" name="domaincontactstate" id="inputDCState" value="{$domaincontact.state}" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputDCPostcode" class="col-md-4 control-label">{$LANG.clientareapostcode}</label>
                            <div class="col-md-8">
                                <input type="text" name="domaincontactpostcode" id="inputDCPostcode" value="{$domaincontact.postcode}" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputDCCountry" class="col-md-4 control-label">{$LANG.clientareacountry}</label>
                            <div class="col-md-8">
                                <select name="domaincontactcountry" id="inputDCCountry" class="form-control">
                                {foreach from=$countries key=countrycode item=countrylabel}
                                    <option value="{$countrycode}"{if (!$domaincontact.country && $countrycode == $defaultcountry) || $countrycode eq $domaincontact.country} selected{/if}>{$countrylabel}</option>
                                {/foreach}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            {/if}

            <h2>{$LANG.orderpaymentmethod}</h2>

            <div class="row">
                <div class="col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3">
                    <div class="alert alert-success text-center large-text" role="alert">
                        {$LANG.ordertotalduetoday}: &nbsp; <strong>{$total}</strong>
                    </div>
                </div>
            </div>

            <div class="alert alert-danger text-center gateway-errors hidden"></div>

            <div class="line-padded text-center">
                {foreach key=num item=gateway from=$gateways}
                    <label class="radio-inline">
                        <input type="radio" name="paymentmethod" value="{$gateway.sysname}" onclick="{if $gateway.type eq "CC"}showCCForm(){else}hideCCForm(){/if}"{if $selectedgateway eq $gateway.sysname} checked{/if} />
                        {$gateway.name}
                    </label>
                {/foreach}
            </div>

            <div id="ccinputform" class="form-horizontal{if $selectedgatewaytype neq "CC"} hidden{/if}">
                {if $clientsdetails.cclastfour}
                    <div class="form-group">
                        <div class="col-sm-7 col-sm-offset-5">
                            <label class="radio-inline"><input type="radio" name="ccinfo" value="useexisting" id="useexisting" onclick="useExistingCC()"{if $ccinfo eq "useexisting"} checked{elseif !$clientsdetails.cclastfour} disabled="disabled"{/if} /> {$LANG.creditcarduseexisting}{if $clientsdetails.cclastfour} ({$clientsdetails.cclastfour}){/if}</label><br />
                            <label class="radio-inline"><input type="radio" name="ccinfo" value="new" id="new" onclick="enterNewCC()"{if $ccinfo eq "new"} checked{/if} /> {$LANG.creditcardenternewcard}</label>
                        </div>
                    </div>
                {else}
                    <input type="hidden" name="ccinfo" value="new" />
                {/if}
                <div class="form-group new-card-info{if $ccinfo eq "useexisting"} hidden{/if}">
                    <label for="inputCardType" class="col-sm-5 control-label">{$LANG.creditcardcardtype}</label>
                    <div class="col-sm-7">
                        <select name="cctype" id="cctype" class="form-control select-autowidth">
                        {foreach from=$acceptedcctypes item=cardtype}
                            <option{if $cctype eq $cardtype} selected{/if}>{$cardtype}</option>
                        {/foreach}
                        </select>
                    </div>
                </div>
                <div class="form-group new-card-info{if $ccinfo eq "useexisting"} hidden{/if}">
                    <label for="inputCardNumber" class="col-sm-5 control-label">{$LANG.creditcardcardnumber}</label>
                    <div class="col-sm-5">
                        <input type="text" name="ccnumber" id="inputCardNumber" value="{$ccnumber}" autocomplete="off" class="form-control" />
                    </div>
                </div>
                <div class="form-group new-card-info{if $ccinfo eq "useexisting"} hidden{/if}">
                    <label for="inputCardExpiry" class="col-sm-5 control-label">{$LANG.creditcardcardexpires}</label>
                    <div class="col-sm-7 form-inline-always">
                        <select name="ccexpirymonth" id="inputCardExpiry" class="form-control select-inline">
                            {foreach from=$months item=month}
                                <option{if $ccexpirymonth eq $month} selected{/if}>{$month}</option>
                            {/foreach}
                        </select> /
                        <select name="ccexpiryyear" class="form-control select-inline">
                            {foreach from=$expiryyears item=year}
                                <option{if $ccexpiryyear eq $year} selected{/if}>{$year}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                {if $showccissuestart}
                    <div class="form-group new-card-info{if $ccinfo eq "useexisting"} hidden{/if}">
                        <label for="inputCardStart" class="col-sm-5 control-label">{$LANG.creditcardcardstart}</label>
                        <div class="col-sm-7 form-inline-always">
                            <select name="ccstartmonth" id="inputCardStart" class="form-control select-inline">
                                {foreach from=$months item=month}
                                    <option{if $ccstartmonth eq $month} selected{/if}>{$month}</option>
                                {/foreach}
                            </select> /
                            <select name="ccstartyear" class="form-control select-inline">
                                {foreach from=$startyears item=year}
                                    <option{if $ccstartyear eq $year} selected{/if}>{$year}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group new-card-info{if $ccinfo eq "useexisting"} hidden{/if}">
                        <label for="inputCardIssueNum" class="col-sm-5 control-label">{$LANG.creditcardcardissuenum}</label>
                        <div class="col-sm-7 row">
                            <div class="col-xs-4 col-sm-3">
                                <input type="text" name="ccissuenum" id="inputCardIssueNum" value="{$ccissuenum}" autocomplete="off" class="form-control" />
                            </div>
                        </div>
                    </div>
                {/if}
                <div class="form-group">
                    <label for="inputCardCvv" class="col-sm-5 control-label">{$LANG.creditcardcvvnumber}</label>
                    <div class="col-sm-7 row">
                        <div class="col-md-6 col-lg-5">
                            <div class="input-group">
                                <input type="text" name="cccvv" id="inputCardCvv" value="{$cccvv}" autocomplete="off" class="form-control input-mini" />
                                <span class="input-group-addon"><a href="#" onclick="window.open('assets/img/ccv.gif','','width=280,height=200,scrollbars=no,top=100,left=100');return false">{$LANG.creditcardcvvwhere}</a></span>
                            </div>
                        </div>
                    </div>
                </div>
                {if $shownostore}
                    <div class="form-group">
                        <div class="col-sm-7 col-sm-offset-5">
                            <label class="checkbox-inline"><input type="checkbox" name="nostore" /> {$LANG.creditcardnostore}</label>
                        </div>
                    </div>
                {/if}
            </div>

            {if $shownotesfield}
                <h2>{$LANG.ordernotes}</h2>
                <div class="row">
                    <div class="col-md-10 col-md-offset-1">
                        <textarea name="notes" rows="4" class="form-control" placeholder="{$LANG.ordernotesdescription}">{$orderNotes}</textarea>
                    </div>
                </div>
            {/if}

            <div class="text-center">

                {if $accepttos}
                    <div class="line-padded">
                        <label class="checkbox-inline"><input type="checkbox" name="accepttos" id="accepttos" /> {$LANG.ordertosagreement} <a href="{$tosurl}" target="_blank">{$LANG.ordertos}</a></label>
                    </div>
                {/if}

                <div class="line-padded">
                    <button type="submit" id="btnCompleteOrder" class="btn btn-primary btn-lg"{if $cartitems==0} disabled="disabled"{/if} onclick="this.value='{$LANG.pleasewait}'"{if $custtype eq "existing" && !$loggedin} formnovalidate{/if}>{$LANG.completeorder} &nbsp;<i class="fa fa-arrow-circle-right"></i></button>
                </div>

            </div>

        </form>

    {/if}

    <div class="secure-warning">
        <img src="assets/img/padlock.gif" align="absmiddle" border="0" alt="Secure Transaction" /> &nbsp;{$LANG.ordersecure} (<strong>{$ipaddress}</strong>) {$LANG.ordersecure2}
    </div>

</div>

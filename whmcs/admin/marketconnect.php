<?php 
define("ADMINAREA", true);
require(dirname(__DIR__) . "/init.php");
$aInt = new WHMCS\Admin("Manage MarketConnect");
$aInt->title = AdminLang::trans("setup.marketconnect");
$aInt->requireAuthConfirmation();
$request = WHMCS\Http\Message\ServerRequest::fromGlobals();
$adminController = new WHMCS\MarketConnect\AdminController();
$aInt->setBodyContent($adminController->dispatch($request));
$aInt->display();


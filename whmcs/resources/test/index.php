<?php 
require_once(dirname(dirname(__DIR__)) . "/init.php");
error_reporting(32767 ^ 8);
$request = WHMCS\Http\Message\ServerRequest::fromGlobals();
$response = DI::make("Frontend\\Dispatcher")->dispatch($request);
(new Zend\Diactoros\Response\SapiEmitter())->emit($response);
exit();


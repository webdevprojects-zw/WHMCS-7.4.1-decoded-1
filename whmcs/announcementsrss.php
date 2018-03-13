<?php 
require_once("init.php");
$rss = new WHMCS\Announcement\Rss();
$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
$response = $rss->toXml($request);
(new Zend\Diactoros\Response\SapiEmitter())->emit($response);


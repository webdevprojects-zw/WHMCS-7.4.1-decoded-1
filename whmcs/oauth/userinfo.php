<?php 
require_once(__DIR__ . DIRECTORY_SEPARATOR . "bootstrap.php");
$server = DI::make("oauth2_server");
$server->setConfig("issuer", WHMCS\ApplicationLink\Server\Server::getIssuer());
$server->handleUserInfoRequest($request, $response);
Log::debug("oauth/userinfo", array( "request" => array( "headers" => $request->server->getHeaders(), "request" => $request->request->all(), "query" => $request->query->all() ), "response" => array( "body" => $response->getContent() ) ));
$response->send();


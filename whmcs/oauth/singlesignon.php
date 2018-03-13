<?php 
require_once(__DIR__ . DIRECTORY_SEPARATOR . "bootstrap.php");
$server = DI::make("oauth2_sso");
$response = $server->handleSingleSignOnRequest($request, $response);
Log::debug("oauth/singlesignon", array( "request" => array( "headers" => $request->server->getHeaders(), "request" => $request->request->all(), "query" => $request->query->all() ), "response" => array( "body" => $response->getContent() ) ));
$response->send();


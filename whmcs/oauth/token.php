<?php 
require_once(__DIR__ . DIRECTORY_SEPARATOR . "bootstrap.php");
$server = DI::make("oauth2_server");
$response = $server->handleTokenRequest($request, $response);
Log::debug("oauth/token", array( "request" => array( "headers" => $request->server->getHeaders(), "request" => $request->request->all(), "query" => $request->query->all() ), "response" => array( "body" => $response->getContent() ) ));
$response->send();
$server->postAccessTokenResponseAction($request, $response);


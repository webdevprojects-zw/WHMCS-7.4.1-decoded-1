<?php 
namespace WHMCS\ApplicationLink\Server;


interface ApplicationLinkServerInterface extends \OAuth2\Controller\TokenControllerInterface, \OAuth2\Controller\ResourceControllerInterface
{
    public function postAccessTokenResponseAction(\OAuth2\RequestInterface $request, \OAuth2\ResponseInterface $response);

}



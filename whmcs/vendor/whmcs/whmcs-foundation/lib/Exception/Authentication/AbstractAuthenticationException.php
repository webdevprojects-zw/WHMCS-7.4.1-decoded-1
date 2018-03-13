<?php 
namespace WHMCS\Exception\Authentication;


abstract class AbstractAuthenticationException extends \WHMCS\Exception\HttpCodeException
{
    const DEFAULT_HTTP_CODE = 403;

}



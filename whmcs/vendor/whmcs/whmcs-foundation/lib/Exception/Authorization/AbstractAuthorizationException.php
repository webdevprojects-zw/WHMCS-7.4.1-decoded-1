<?php 
namespace WHMCS\Exception\Authorization;


class AbstractAuthorizationException extends \WHMCS\Exception\HttpCodeException
{
    const DEFAULT_HTTP_CODE = 403;

}



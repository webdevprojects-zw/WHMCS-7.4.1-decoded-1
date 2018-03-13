<?php 
namespace WHMCS\Route\Contracts;


interface MapInterface
{
    public function mapRoute($route);

    public function getMappedRoute($key);

    public function getMappedAttributeName();

}



<?php 
namespace WHMCS\Authorization\Contracts;


interface PermissionInterface
{
    public function isAllowed($item);

    public function listAll();

}



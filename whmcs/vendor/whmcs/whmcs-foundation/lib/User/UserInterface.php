<?php 
namespace WHMCS\User;


interface UserInterface
{
    public function getUsernameAttribute();

    public function hasPermission($permission);

}



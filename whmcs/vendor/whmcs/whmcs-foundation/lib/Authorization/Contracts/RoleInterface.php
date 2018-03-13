<?php 
namespace WHMCS\Authorization\Contracts;


interface RoleInterface
{
    public function getId();

    public function allow(array $itemsToAllow);

    public function deny(array $itemsToDeny);

    public function getData();

    public function setData(array $data);

}



<?php 
namespace WHMCS\Database;


interface DatabaseInterface
{
    public function getConnection();

    public function retrieveDatabaseConnection();

}



<?php 

class Transip_Db
{
    public $name = NULL;
    public $username = NULL;
    public $maxDiskUsage = NULL;

    public function __construct($name, $username = "", $maxDiskUsage = 100)
    {
        $this->name = $name;
        $this->username = $username;
        $this->maxDiskUsage = $maxDiskUsage;
    }

}



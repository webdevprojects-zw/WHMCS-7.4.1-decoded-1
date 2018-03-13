<?php 

class Transip_MailForward
{
    public $name = NULL;
    public $targetAddress = NULL;

    public function __construct($name, $targetAddress)
    {
        $this->name = $name;
        $this->targetAddress = $targetAddress;
    }

}



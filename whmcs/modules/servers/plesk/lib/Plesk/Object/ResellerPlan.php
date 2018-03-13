<?php 

class Plesk_Object_ResellerPlan
{
    public $id = NULL;
    public $name = NULL;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

}



<?php 

class Transip_Cronjob
{
    public $name = NULL;
    public $url = NULL;
    public $email = NULL;
    public $minuteTrigger = NULL;
    public $hourTrigger = NULL;
    public $dayTrigger = NULL;
    public $monthTrigger = NULL;
    public $weekdayTrigger = NULL;

    public function __construct($name, $url, $email, $minuteTrigger, $hourTrigger, $dayTrigger, $monthTrigger, $weekdayTrigger)
    {
        $this->name = $name;
        $this->url = $url;
        $this->email = $email;
        $this->minuteTrigger = $minuteTrigger;
        $this->hourTrigger = $hourTrigger;
        $this->dayTrigger = $dayTrigger;
        $this->monthTrigger = $monthTrigger;
        $this->weekdayTrigger = $weekdayTrigger;
    }

}



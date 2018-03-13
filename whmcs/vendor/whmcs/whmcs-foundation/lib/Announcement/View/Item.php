<?php 
namespace WHMCS\Announcement\View;


class Item extends Index
{
    protected function initializeView()
    {
        parent::initializeView();
        $this->setTemplate("viewannouncement");
    }

}



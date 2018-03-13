<?php 
namespace WHMCS\Announcement\View;


class TwitterFeed extends \WHMCS\ClientArea
{
    protected function initializeView()
    {
        parent::initializeView();
        $this->disableHeaderFooterOutput();
        $this->setTemplate("twitterfeed");
    }

}



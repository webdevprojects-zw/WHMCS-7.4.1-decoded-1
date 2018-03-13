<?php 
namespace WHMCS\Admin\ApplicationSupport\View\Html\Smarty;


class ErrorPage extends BodyContentWrapper
{
    public function __construct($data = "", $status = 200, array $headers = array(  ))
    {
        parent::__construct($data, $status, $headers);
        $this->setSidebarName("home");
        $this->setFavicon("warning");
    }

    public function getBody()
    {
        if( \WHMCS\Session::get("adminid") ) 
        {
            return parent::getBody();
        }

        return (new \WHMCS\Admin\ApplicationSupport\View\Html\PopUp($this->getBodyContent()))->getBody();
    }

}



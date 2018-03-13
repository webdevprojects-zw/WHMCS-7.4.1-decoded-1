<?php 
namespace WHMCS\Admin\ApplicationSupport\View\Traits;


trait BodyContentTrait
{
    protected $bodyContent = "";

    public function getBodyContent()
    {
        return $this->bodyContent;
    }

    public function setBodyContent($content)
    {
        $this->bodyContent = (string) $content;
        return $this;
    }

}



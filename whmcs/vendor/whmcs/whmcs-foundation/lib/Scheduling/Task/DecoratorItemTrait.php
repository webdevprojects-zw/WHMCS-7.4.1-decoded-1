<?php 
namespace WHMCS\Scheduling\Task;


trait DecoratorItemTrait
{
    protected $icon = "fa-cube";
    protected $successCountIdentifier = 0;
    protected $successKeyword = "Completed";
    protected $failureCountIdentifier = 0;
    protected $failureKeyword = "Failed";
    protected $failureUrl = "modulequeue.php";
    protected $isBooleanStatus = false;

    public function getIcon()
    {
        return $this->icon;
    }

    public function getSuccessCountIdentifier()
    {
        return $this->successCountIdentifier;
    }

    public function getFailureCountIdentifier()
    {
        return $this->failureCountIdentifier;
    }

    public function getSuccessKeyword()
    {
        return $this->successKeyword;
    }

    public function getFailureKeyword()
    {
        return $this->failureKeyword;
    }

    public function getFailureUrl()
    {
        return $this->failureUrl;
    }

    public function isBooleanStatusItem()
    {
        return (bool) $this->isBooleanStatus;
    }

}



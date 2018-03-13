<?php 
namespace WHMCS\Payment\Filter\Iterator;


class CallbackIterator extends \FilterIterator
{
    private $callback = NULL;

    public function __construct(\Iterator $iterator, $conditionalCallback)
    {
        $this->setCallback($conditionalCallback);
        parent::__construct($iterator);
    }

    public function accept()
    {
        $item = $this->getInnerIterator()->current();
        if( call_user_func($this->getCallback(), $item) ) 
        {
            return true;
        }

        return false;
    }

    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    public function getCallback()
    {
        return $this->callback;
    }

}



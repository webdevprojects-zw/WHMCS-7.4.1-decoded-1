<?php 
namespace WHMCS\Payment\Filter;


abstract class AbstractFilter implements FilterInterface
{
    public function getFilteredIterator(\Iterator $iterator)
    {
        return new Iterator\CallbackIterator($iterator, array( $this, "filter" ));
    }

    abstract public function filter(\WHMCS\Payment\Adapter\AdapterInterface $adapter);

}



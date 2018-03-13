<?php 
namespace WHMCS\Payment\Filter;


interface FilterInterface
{
    public function getFilteredIterator(\Iterator $iterator);

    public function filter(\WHMCS\Payment\Adapter\AdapterInterface $adapter);

}



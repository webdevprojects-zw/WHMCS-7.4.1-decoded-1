<?php 
namespace WHMCS\Http;


trait PriceDataTrait
{
    public function mutatePriceToFull($data = array(  ))
    {
        array_walk_recursive($data, function(&$item)
{
    if( $item instanceof \WHMCS\View\Formatter\Price ) 
    {
        $item = $item->toFull();
    }

}

);
        return $data;
    }

}



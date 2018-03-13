<?php 
namespace WHMCS\Search\ApplicationSupport\Controller;


interface SearchInterface
{
    public function searchRequest(\WHMCS\Http\Message\ServerRequest $request);

}



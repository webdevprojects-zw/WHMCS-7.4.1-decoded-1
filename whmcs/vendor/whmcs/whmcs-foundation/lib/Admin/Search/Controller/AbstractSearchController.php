<?php 
namespace WHMCS\Admin\Search\Controller;


abstract class AbstractSearchController implements \WHMCS\Search\ApplicationSupport\Controller\SearchInterface, \WHMCS\Search\SearchInterface
{
    abstract public function getSearchTerm(\WHMCS\Http\Message\ServerRequest $request);

    abstract public function getSearchable();

    public function searchRequest(\WHMCS\Http\Message\ServerRequest $request)
    {
        $data = $this->getSearchable()->search($this->getSearchTerm($request));
        return new \WHMCS\Http\Message\JsonResponse($data);
    }

}



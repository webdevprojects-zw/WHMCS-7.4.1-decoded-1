<?php 
namespace WHMCS\Http;


trait DataTrait
{
    protected $rawData = array(  );

    public function getRawData()
    {
        return $this->rawData;
    }

    public function setRawData($rawData)
    {
        $this->rawData = $rawData;
        return $this;
    }

}



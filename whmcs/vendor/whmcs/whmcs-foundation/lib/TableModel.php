<?php 
namespace WHMCS;


abstract class TableModel extends TableQuery
{
    protected $pageObj = NULL;
    protected $queryObj = NULL;

    public function __construct(Pagination $obj = NULL)
    {
        $whmcs = \DI::make("app");
        $this->pageObj = $obj;
        $numrecords = $whmcs->get_config("NumRecordstoDisplay");
        $this->setRecordLimit($numrecords);
        return $this;
    }

    abstract public function _execute($implementationData);

    public function setPageObj(Pagination $pageObj)
    {
        $this->pageObj = $pageObj;
    }

    public function getPageObj()
    {
        return $this->pageObj;
    }

    public function execute($implementationData = NULL)
    {
        $results = $this->_execute($implementationData);
        $this->getPageObj()->setData($results);
        return $this;
    }

}



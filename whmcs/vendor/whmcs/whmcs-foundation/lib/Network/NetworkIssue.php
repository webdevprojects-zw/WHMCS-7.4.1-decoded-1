<?php 
namespace WHMCS\Network;


class NetworkIssue extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblnetworkissues";
    protected $columnMap = array( "affectedType" => "type", "affectedOther" => "affecting", "affectedServerId" => "server", "lastUpdateDate" => "lastupdate" );
    protected $dates = array( "startdate", "enddate", "lastupdate" );

}



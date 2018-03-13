<?php 
namespace WHMCS\Log;


class ActivityLogHandler extends \Monolog\Handler\AbstractProcessingHandler
{
    protected function write(array $record)
    {
        if( $record["formatted"] ) 
        {
            try
            {
                $event = array( "date" => (string) \Carbon\Carbon::now()->format("YmdHis"), "description" => $record["formatted"], "user" => "", "userid" => "", "ipaddr" => "" );
                \WHMCS\Database\Capsule::table("tblactivitylog")->insertGetId($event);
            }
            catch( \Exception $e ) 
            {
            }
        }

    }

}



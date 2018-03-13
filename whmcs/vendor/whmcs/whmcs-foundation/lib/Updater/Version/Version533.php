<?php 
namespace WHMCS\Updater\Version;


class Version533 extends IncrementalVersion
{
    protected $runUpdateCodeBeforeDatabase = true;

    protected function runUpdateCode()
    {
        $query = "ALTER TABLE  `tblsslorders` ADD  `provisiondate` DATE NOT NULL AFTER  `configdata`";
        mysql_query($query);
        return $this;
    }

}



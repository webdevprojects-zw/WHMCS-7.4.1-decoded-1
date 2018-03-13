<?php 
namespace WHMCS\Domain;


class TopLevel extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbltlds";
    public $unique = array( "tld" );

    public function categories()
    {
        return $this->belongsToMany("WHMCS\\Domain\\TopLevel\\Category", "tbltld_category_pivot", "tld_id")->withTimestamps();
    }

}



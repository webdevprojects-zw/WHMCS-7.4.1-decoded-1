<?php 
namespace WHMCS\Billing;


class Pricing
{
    protected $db_fields = array( "msetupfee", "qsetupfee", "ssetupfee", "asetupfee", "bsetupfee", "tsetupfee", "monthly", "quarterly", "semiannually", "annually", "biennially", "triennially" );

    public function getDBFields()
    {
        return $this->db_fields;
    }

}



<?php 
namespace WHMCSProjectManagement\Models;


class WHMCSInvoice extends \WHMCS\Billing\Invoice
{
    protected $appends = array( "balance" );

}



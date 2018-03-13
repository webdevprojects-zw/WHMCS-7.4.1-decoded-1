<?php 
namespace WHMCS\View\Engine\Smarty;


class Admin extends \WHMCS\Smarty implements \WHMCS\View\Engine\VariableAccessorInterface
{
    public function __construct($admin = true, $policyName = NULL)
    {
        parent::__construct($admin, $policyName);
    }

}



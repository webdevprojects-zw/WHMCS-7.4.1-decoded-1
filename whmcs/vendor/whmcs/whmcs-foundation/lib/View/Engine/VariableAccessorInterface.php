<?php 
namespace WHMCS\View\Engine;


interface VariableAccessorInterface
{
    public function assign($tpl_var, $value, $nocache);

}



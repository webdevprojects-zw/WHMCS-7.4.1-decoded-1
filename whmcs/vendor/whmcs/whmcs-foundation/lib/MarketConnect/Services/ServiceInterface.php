<?php 
namespace WHMCS\MarketConnect\Services;


interface ServiceInterface
{
    public function provision($model, array $params);

    public function configure($model, array $params);

    public function cancel($model);

    public function renew($model);

    public function install($model);

}



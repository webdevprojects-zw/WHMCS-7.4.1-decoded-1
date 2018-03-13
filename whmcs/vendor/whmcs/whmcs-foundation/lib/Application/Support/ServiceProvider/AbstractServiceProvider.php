<?php 
namespace WHMCS\Application\Support\ServiceProvider;


abstract class AbstractServiceProvider
{
    protected $app = NULL;

    public function __construct(\WHMCS\Container $app)
    {
        $this->app = $app;
    }

    abstract public function register();

}



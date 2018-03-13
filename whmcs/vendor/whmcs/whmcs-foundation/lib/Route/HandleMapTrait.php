<?php 
namespace WHMCS\Route;


trait HandleMapTrait
{
    protected $routes = array(  );

    abstract public function getMappedAttributeName();

    public function mapRoute($route)
    {
        $attributeName = $this->getMappedAttributeName();
        if( empty($route["handle"]) || empty($route[$attributeName]) ) 
        {
            return $this;
        }

        $this->routes[serialize($route["handle"])] = $route[$attributeName];
        return $this;
    }

    public function getMappedRoute($key)
    {
        if( is_array($key) || is_object($key) && !$key instanceof \Closure ) 
        {
            $key = serialize($key);
        }

        if( isset($this->routes[$key]) ) 
        {
            return $this->routes[$key];
        }

        return null;
    }

}



<?php 
namespace WHMCS\View\Menu;


class MenuFactory extends \Knp\Menu\MenuFactory
{
    protected $loader = NULL;
    protected $rootItemName = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->loader = new \Knp\Menu\Loader\ArrayLoader($this);
    }

    public function createItem($name, array $options = array(  ))
    {
        $extension = new Factory\WhmcsExtension();
        $options = $extension->buildOptions($options);
        $item = parent::createItem($name, $options);
        $item = unserialize(sprintf("O:%d:\"%s\"%s", strlen("WHMCS\\View\\Menu\\Item"), "WHMCS\\View\\Menu\\Item", strstr(strstr(serialize($item), "\""), ":")));
        $extension->buildItem($item, $options);
        return $item;
    }

    protected function buildMenuStructure(array $structure = array(  ))
    {
        return array( "name" => $this->rootItemName, "children" => $structure );
    }

    public function emptySidebar()
    {
        return $this->loader->load($this->buildMenuStructure());
    }

    public function getLoader()
    {
        return $this->loader;
    }

}



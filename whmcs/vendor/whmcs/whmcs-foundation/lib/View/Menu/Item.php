<?php 
namespace WHMCS\View\Menu;


class Item extends \Knp\Menu\MenuItem
{
    protected $badge = "";
    protected $order = NULL;
    protected $disabled = false;
    protected $icon = "";
    protected $headingHtml = NULL;
    protected $bodyHtml = NULL;
    protected $footerHtml = NULL;

    public function getName()
    {
        return parent::getName();
    }

    public function setName($name)
    {
        return parent::setName($name);
    }

    public function getUri()
    {
        return parent::getUri();
    }

    public function setUri($uri)
    {
        if( !$uri ) 
        {
            return $this;
        }

        if( filter_var($uri, FILTER_VALIDATE_URL) === false ) 
        {
            $base = \WHMCS\Utility\Environment\WebHelper::getBaseUrl(ROOTDIR, $_SERVER["SCRIPT_NAME"]);
            if( empty($base) || strpos($uri, $base) !== 0 ) 
            {
                $uri = \WHMCS\Utility\Environment\WebHelper::getBaseUrl(ROOTDIR, $_SERVER["SCRIPT_NAME"]) . "/" . $uri;
            }

            $uri = preg_replace("/\\/+/", "/", $uri);
        }

        $this->uri = $uri;
        return $this;
    }

    public function getLabel()
    {
        return parent::getLabel();
    }

    public function setLabel($label)
    {
        return parent::setLabel($label);
    }

    public function addChild($child, array $options = array(  ))
    {
        return parent::addChild($child, $options);
    }

    public function getChild($name)
    {
        return parent::getChild($name);
    }

    public function copy()
    {
        return parent::copy();
    }

    public function getLevel()
    {
        return parent::getLevel();
    }

    public function getRoot()
    {
        return parent::getRoot();
    }

    public function isRoot()
    {
        return parent::isRoot();
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getChildren()
    {
        return parent::getChildren();
    }

    public function setChildren(array $children)
    {
        return parent::setChildren($children);
    }

    public function removeChild($name)
    {
        return parent::removeChild($name);
    }

    public function hasChildren()
    {
        return parent::hasChildren();
    }

    public function setBadge($badge)
    {
        $this->badge = trim($badge);
        return $this;
    }

    public function getBadge()
    {
        return $this->badge;
    }

    public function hasBadge()
    {
        return $this->badge !== "";
    }

    public function setOrder($order)
    {
        $this->order = (int) $order;
        return $this;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setClass($cssClassString)
    {
        $this->attributes["class"] = $cssClassString;
        return $this;
    }

    public function getClass()
    {
        return (isset($this->attributes["class"]) ? $this->attributes["class"] : "");
    }

    public function disable()
    {
        $this->disabled = true;
        return $this;
    }

    public function enable()
    {
        $this->disabled = false;
        return $this;
    }

    public function isDisabled()
    {
        return (isset($this->disabled) ? $this->disabled : false);
    }

    public function getExtras()
    {
        return parent::getExtras();
    }

    public function getExtra($name, $default = NULL)
    {
        return parent::getExtra($name, $default);
    }

    public function setExtras(array $extras)
    {
        return parent::setExtras($extras);
    }

    public function setExtra($name, $value)
    {
        return parent::setExtra($name, $value);
    }

    protected function isFontAwesomeIcon($icon)
    {
        return substr($icon, 0, 3) == "fa-";
    }

    protected function isGlyphicon($icon)
    {
        return substr($icon, 0, 10) == "glyphicon-";
    }

    public function setIcon($icon)
    {
        $icon = trim($icon);
        if( $icon != "" && !$this->isFontAwesomeIcon($icon) && !$this->isGlyphicon($icon) ) 
        {
            throw new \WHMCS\Exception("Please provide either a Font Awesome or Glyphicon.");
        }

        $this->icon = $icon;
        return $this;
    }

    public function getIcon()
    {
        $icon = "";
        if( $this->hasFontAwesomeIcon() ) 
        {
            $icon = "fa " . $this->icon;
        }
        else
        {
            if( $this->hasGlyphicon() ) 
            {
                $icon = "glyphicon " . $this->icon;
            }

        }

        return $icon;
    }

    public function hasIcon()
    {
        return $this->icon !== "";
    }

    public function hasFontAwesomeIcon()
    {
        return $this->hasIcon() && $this->isFontAwesomeIcon($this->icon);
    }

    public function hasGlyphicon()
    {
        return $this->hasIcon() && $this->isGlyphicon($this->icon);
    }

    public function getBodyHtml()
    {
        return $this->bodyHtml;
    }

    public function setBodyHtml($html)
    {
        $this->bodyHtml = $html;
        return $this;
    }

    public function hasBodyHtml()
    {
        return $this->bodyHtml != "";
    }

    public function getFooterHtml()
    {
        return $this->footerHtml;
    }

    public function setFooterHtml($html)
    {
        $this->footerHtml = $html;
        return $this;
    }

    public function hasFooterHtml()
    {
        return $this->footerHtml != "";
    }

    public function getHeadingHtml()
    {
        return $this->headingHtml;
    }

    public function setHeadingHtml($html)
    {
        $this->headingHtml = $html;
        return $this;
    }

    public function hasHeadingHtml()
    {
        return $this->headingHtml != "";
    }

    public function getId()
    {
        $parentId = "";
        if( !is_null($this->getParent()) ) 
        {
            $parentId = $this->getParent()->getId() . "-";
        }

        return $parentId . str_replace(array( " ", "/" ), "_", $this->getName());
    }

    public static function sort(Item $menu, $sortChildren = true)
    {
        $children = $menu->getChildren();
        if( $sortChildren ) 
        {
            foreach( $children as $i => $child ) 
            {
                $children[$i] = static::sort($child);
            }
        }

        uasort($children, function(Item $a, Item $b)
{
    $aOrder = $a->getOrder();
    $bOrder = $b->getOrder();
    if( $aOrder == $bOrder ) 
    {
        return ($b->getName() < $a->getName() ? 1 : -1);
    }

    return ($bOrder < $aOrder ? 1 : -1);
}

);
        $menu->setChildren($children);
        return $menu;
    }

    protected function swapOrder($swapOrder)
    {
        $parent = $this->getParent();
        static::sort($parent, false);
        $siblings = $parent->getChildren();
        reset($siblings);
        while( list($name) = each($siblings) ) 
        {
            if( $name == $this->getName() ) 
            {
                current($siblings);
                (current($siblings) === false ? end($siblings) : prev($siblings));
                break;
            }

        }
        $swapItem = ($swapOrder == "up" ? prev($siblings) : next($siblings));
        if( $swapItem !== false ) 
        {
            $order = $swapItem->getOrder();
            $swapItem->setOrder($this->getOrder());
            $this->setOrder($order);
            $this->getParent()->removeChild($swapItem->getName());
            $this->getParent()->addChild($swapItem);
        }

        return $this;
    }

    public function moveUp()
    {
        return $this->swapOrder("up");
    }

    public function moveDown()
    {
        return $this->swapOrder("down");
    }

    public function moveToFront()
    {
        static::sort($this->getParent(), false);
        while( !$this->isFirst() ) 
        {
            $this->moveUp();
        }
        return $this;
    }

    public function moveToBack()
    {
        static::sort($this->getParent(), false);
        while( !$this->isLast() ) 
        {
            $this->moveDown();
        }
        return $this;
    }

}



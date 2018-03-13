<?php 
namespace WHMCS\Module\Notification\Slack;


class Field
{
    public $title = "";
    public $value = "";
    public $short = false;

    public function title($title)
    {
        $this->title = trim($title);
        return $this;
    }

    public function value($value)
    {
        $this->value = trim($value);
        return $this;
    }

    public function short()
    {
        $this->short = true;
        return $this;
    }

    public function toArray()
    {
        $field = array( "title" => $this->title, "value" => $this->value, "short" => $this->short );
        return $field;
    }

}



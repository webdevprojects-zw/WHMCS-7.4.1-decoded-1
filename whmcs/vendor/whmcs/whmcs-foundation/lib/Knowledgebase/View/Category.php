<?php 
namespace WHMCS\Knowledgebase\View;


class Category extends Index
{
    protected function initializeView()
    {
        parent::initializeView();
        $this->setTemplate("knowledgebasecat");
    }

}



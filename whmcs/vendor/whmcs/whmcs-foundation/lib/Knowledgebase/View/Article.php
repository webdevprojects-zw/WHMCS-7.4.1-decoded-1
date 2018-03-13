<?php 
namespace WHMCS\Knowledgebase\View;


class Article extends Index
{
    protected function initializeView()
    {
        parent::initializeView();
        $this->setTemplate("knowledgebasearticle");
    }

}



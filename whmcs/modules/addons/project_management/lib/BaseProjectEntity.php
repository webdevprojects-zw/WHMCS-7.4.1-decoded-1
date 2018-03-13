<?php 
namespace WHMCSProjectManagement;


abstract class BaseProjectEntity
{
    public $project = NULL;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function project()
    {
        return $this->project;
    }

}



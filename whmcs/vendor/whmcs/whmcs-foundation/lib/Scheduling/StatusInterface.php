<?php 
namespace WHMCS\Scheduling;


interface StatusInterface
{
    public function isInProgress();

    public function isDueNow();

    public function calculateAndSetNextDue();

    public function setNextDue(\Carbon\Carbon $nextDue);

    public function setInProgress($state);

    public function getLastRuntime();

    public function setLastRuntime(\Carbon\Carbon $date);

    public function getNextDue();

}



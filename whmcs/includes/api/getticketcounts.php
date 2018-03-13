<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}

$adminId = WHMCS\Session::get("adminid");
$showActive = $showAwaiting = array(  );
$ticketStatuses = WHMCS\Database\Capsule::table("tblticketstatuses")->get(array( "title", "showactive", "showawaiting" ));
foreach( $ticketStatuses as $status ) 
{
    if( $status->showactive ) 
    {
        $showActive[] = $status->title;
    }

    if( $status->showawaiting ) 
    {
        $showAwaiting[] = $status->title;
    }

}
$applyDepartmentFilter = (bool) (!App::getFromRequest("ignoreDepartmentAssignments"));
$adminSupportDepartmentsQuery = array(  );
$departmentFilter = "";
if( $applyDepartmentFilter ) 
{
    $adminSupportDepartments = get_query_val("tbladmins", "supportdepts", array( "id" => $adminId ));
    $adminSupportDepartments = explode(",", $adminSupportDepartments);
    foreach( $adminSupportDepartments as $departmentId ) 
    {
        if( trim($departmentId) ) 
        {
            $adminSupportDepartmentsQuery[] = (int) $departmentId;
        }

    }
    if( 0 < count($adminSupportDepartmentsQuery) ) 
    {
        $departmentFilter = " AND did IN (" . db_build_in_array($adminSupportDepartmentsQuery) . ")";
    }

}

$allActive = (int) get_query_val("tbltickets", "count(id)", "merged_ticket_id=0 AND status IN (" . db_build_in_array($showActive) . ")" . $departmentFilter);
$awaitingReply = (int) get_query_val("tbltickets", "count(id)", "merged_ticket_id=0 AND status IN (" . db_build_in_array($showAwaiting) . ")" . $departmentFilter);
$flaggedWhere = "merged_ticket_id = 0 AND tbltickets.status IN (" . db_build_in_array($showActive) . ") AND flag=" . (int) $adminId;
$flaggedTickets = (int) get_query_val("tbltickets", "count(id)", $flaggedWhere);
$apiresults = array( "result" => "success", "filteredDepartments" => $adminSupportDepartmentsQuery, "allActive" => $allActive, "awaitingReply" => $awaitingReply, "flaggedTickets" => $flaggedTickets );
if( App::getFromRequest("includeCountsByStatus") ) 
{
    $ticketCounts = array(  );
    $ticketStatuses = WHMCS\Database\Capsule::table("tblticketstatuses")->pluck(WHMCS\Database\Capsule::raw("0"), "title");
    $tickets = WHMCS\Database\Capsule::table("tbltickets")->where("merged_ticket_id", "=", "0")->selectRaw("status, COUNT(*) as count")->groupBy("status")->pluck("count", "status");
    foreach( $tickets as $status => $count ) 
    {
        $ticketStatuses[$status] = $count;
    }
    foreach( $ticketStatuses as $ticketStatus => $ticketCount ) 
    {
        $ticketCounts[preg_replace("/[^a-z0-9]/", "", strtolower($ticketStatus))] = array( "title" => $ticketStatus, "count" => $ticketCount );
    }
    $apiresults["status"] = $ticketCounts;
}



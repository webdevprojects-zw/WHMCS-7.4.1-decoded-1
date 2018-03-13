<?php 
require("../init.php");
error_reporting(0);
if( !function_exists("getimagesize") ) 
{
    exit( "You need to recompile with the GD library included in PHP for this feature to be able to function" );
}

$filename = "";
$tid = (int) App::get_req_var("tid");
$rid = (int) App::get_req_var("rid");
$nid = (int) App::get_req_var("nid");
if( $tid ) 
{
    $data = get_query_vals("tbltickets", "userid,attachment", array( "id" => $tid ));
    list($userid, $attachments) = $data;
    $attachments = explode("|", $attachments);
    $filename = $attachments_dir . DIRECTORY_SEPARATOR . $attachments[$i];
}

if( $rid ) 
{
    $data = get_query_vals("tblticketreplies", "tid,attachment", array( "id" => $rid ));
    list($ticketid, $attachments) = $data;
    $attachments = explode("|", $attachments);
    $filename = $attachments_dir . DIRECTORY_SEPARATOR . $attachments[$i];
    $userid = get_query_val("tbltickets", "userid", array( "id" => $ticketid ));
}

if( $nid ) 
{
    $data = get_query_vals("tblticketnotes", "ticketid,attachments", array( "id" => $nid ));
    $ticketid = $data["ticketid"];
    $attachments = $data["attachments"];
    $attachments = explode("|", $attachments);
    $filename = $attachments_dir . DIRECTORY_SEPARATOR . $attachments[$i];
    $userid = get_query_val("tbltickets", "userid", array( "id" => $ticketid ));
}

if( $_SESSION["uid"] != $userid && !$_SESSION["adminid"] ) 
{
    $filename = DI::make("asset")->getFilesystemImgPath() . "/nothumbnail.gif";
}

if( !$filename ) 
{
    $filename = DI::make("asset")->getFilesystemImgPath() . "/nothumbnail.gif";
}

$output_function = "";
$size = getimagesize($filename);
switch( $size["mime"] ) 
{
    case "image/jpeg":
        $img = imagecreatefromjpeg($filename);
        $output_function = "imagejpeg";
        break;
    case "image/gif":
        $img = imagecreatefromgif($filename);
        $output_function = "imagegif";
        break;
    case "image/png":
        $img = imagecreatefrompng($filename);
        $output_function = "imagepng";
        break;
    default:
        $img = false;
        break;
}
$thumbWidth = 200;
$thumbHeight = 125;
if( !$img ) 
{
    $filename = DI::make("asset")->getFilesystemImgPath() . "/nothumbnail.gif";
    $img = imagecreatefromgif($filename);
    $output_function = "imagegif";
}

$width = imagesx($img);
$height = imagesy($img);
$new_height = $thumbHeight;
$new_width = floor($width * $thumbHeight / $height);
if( $new_width < 200 ) 
{
    $new_width = 200;
    $new_height = floor($height * $thumbWidth / $width);
}
else
{
    if( 500 < $new_width ) 
    {
        $new_width = 500;
        $new_height = floor($height * $thumbWidth / $width);
    }

}

$tmp_img = imagecreatetruecolor($new_width, $new_height);
imagecopyresized($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Content-Type: " . $size["mime"]);
$output_function($tmp_img);
imagedestroy($tmp_img);


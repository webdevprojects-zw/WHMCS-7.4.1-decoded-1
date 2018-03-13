<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("View PHP Info");
$aInt->title = $aInt->lang("system", "phpinfo");
$aInt->sidebar = "utilities";
$aInt->icon = "phpinfo";
ob_start();
phpinfo();
$info = ob_get_contents();
ob_end_clean();
$info = preg_replace("%^.*<body>(.*)</body>.*\$%ms", "\$1", $info);
ob_start();
echo "<style type=\"text/css\">\n.e {background-color: #EFF2F9; font-weight: bold; color: #000000;}\n.v {background-color: #efefef; color: #000000;}\n.vr {background-color: #efefef; text-align: right; color: #000000;}\nhr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}\n</style>\n";
echo $info;
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();


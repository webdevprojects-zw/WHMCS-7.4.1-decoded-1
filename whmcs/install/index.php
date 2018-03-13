<?php 
$location = pathinfo($_SERVER["PHP_SELF"], PATHINFO_DIRNAME) . "/install.php";
header("Location: " . $location);
exit();


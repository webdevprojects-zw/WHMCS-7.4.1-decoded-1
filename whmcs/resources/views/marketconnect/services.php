<?php 
$this->layout("layouts/admin-content");
$this->start("body");
echo "\n<div class=\"market-connect-apps-container\">\n    <div class=\"row\">\n        ";
foreach( $services as $service => $data ) 
{
    $this->insert("shared/service", array( "service" => $service, "state" => $state, "data" => $data ));
}
echo "    </div>\n</div>\n\n";
$this->insert("shared/tour");
$this->end();


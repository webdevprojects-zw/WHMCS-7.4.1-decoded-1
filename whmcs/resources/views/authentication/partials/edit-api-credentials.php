<?php 
echo "<form name=\"frmApiCredentialManage\" action=\"";
echo routePath("admin-setup-authz-api-devices-update");
echo "\">\n    <input type=\"hidden\" name=\"token\" value=\"";
echo $csrfToken;
echo "\">\n    <input type=\"hidden\" name=\"id\" value=\"";
echo $device->id;
echo "\">\n    ";
echo $this->insert("partials/attributes-api-credentials");
echo "</form>\n";


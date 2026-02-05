<?php

#
# Delete certificate
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, false);
# validate permissions
$User->validate_user_permissions (3, true);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant (false, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# fetch certificate
$certificate = $Certificates->get_certificate ($_GET['serial'], $_params['tenant']);
$tenant      = $Tenants->get_tenant_by_href ($_GET['tenant']);

# validate cert
if(sizeof($certificate)==0) {
	$Result->show("danger", _("Invalid serial").".", true, true, false, false);
}

# validate tenant
if($tenant===null) {
	$Result->show("danger", _("Invalid tenant").".", true, true, false, false);
}

# ok, validations passed, remove
try {
	$Database->deleteObject("certificates", $certificate->id);
	// Write log :: object, object_id, tenant_id, user_id, action, public, text
	$Log->write ("certificates", $certificate->id, $tenant->id, $user->id, "delete", true, "Certificate serial ".$certificate->serial." deleted", json_encode($certificate), NULL);

} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, true, false, false);
}

# ok
$Result->show("success", _("Certificate removed").".", false, true, false, true);
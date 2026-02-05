<?php

#
# Delete hostname
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

# fetch zone
$zone = $Zones->get_zone ($_GET['tenant'], $_GET['zone_id']);
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);
$host = $Zones->get_host ($_GET['host_id']);

# validate id
if($Common->validate_int($_GET['host_id'])===false || $zone===null) {
	$Result->show("danger", _("Invalid hostname").".", true, false, false, false);
}

# validate tenant
if($tenant===null) {
	$Result->show("danger", _("Invalid tenant").".", true, false, false, false);
}

# validate host
if($host===null) {
	$Result->show("danger", _("Invalid tenant").".", true, false, false, false);
}

# ok, validations passed, remove
try {
	$Database->deleteObject("hosts", $_GET['host_id']);
	// Write log :: object, object_id, tenant_id, user_id, action, public, text
	$Log->write ("hosts", $_GET['host_id'], $tenant->id, $user->id, "delete", true, "Host ".$host->hostname." deleted", json_encode($host), NULL);
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, true, false, false);
}

# ok
$Result->show("success", _("Host removed").".", false, true, false, true);
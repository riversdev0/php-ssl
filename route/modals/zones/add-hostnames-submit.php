<?php

#
# Edit host
#



# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, true);
# validate permissions
$User->validate_user_permissions (2, true);

# validate tenant
$_params['tenant'] = $_POST['tenant'];
$User->validate_tenant (false, true);

# strip tags
$_POST = $User->strip_input_tags ($_POST);

# fetch zone
$zone = $Zones->get_zone ($_POST['tenant'], $_POST['zone_id']);
$tenant = $Tenants->get_tenant_by_href ($_POST['tenant']);

# validate zone id
if($Common->validate_int($_POST['zone_id'])===false || $zone===null) {
	$Result->show("danger", _("Invalid zone").".", true, false, false, false);
}
# validate tenant
if($tenant===null) {
	$Result->show("danger", _("Invalid tenant").".", true, false, false, false);
}

# create hosts to insert
foreach ($_POST as $k=>$p) {
	if (strpos($k, "hostname-")!==false) {
		if($Common->validate_hostname($p)) {
			$index = substr($k, 9);
			$out[$index]['hostname'] = $p;
		}
		else {
			$Result->show("danger", _("Invalid hostname").".", true, false, false, false);
		}
		// make sure it is inside domain !
		if($Zones->is_host_inside_domain ($p, $zone->name)===false) {
			$Result->show("danger", _("Hostname not in zone").".", true, false, false, false);
		}
	}
	elseif (strpos($k, "pg-")!==false) {
		if($Common->validate_int($p)) {
			$index = substr($k, 3);
			$out[$index]['pg_id'] = $p;
		}
	}
}

# ok, validations passed, insert
try {
	foreach ($out as $o) {
		$new_host_id = $Database->insertObject("hosts", ["z_id"=>$_POST['zone_id'], "pg_id"=>$o['pg_id'], "hostname"=>$o['hostname']]);
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("hosts", $new_host_id, $tenant->id, $user->id, "add", true, "New host added to zone"." :: ".json_encode($o));
	}
	// ok
	$Result->show("success", _("Hosts created").".", false, false, false, false);

} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}
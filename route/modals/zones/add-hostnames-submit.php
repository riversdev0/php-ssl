<?php

#
# Edit host
#



# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, true);
$User->validate_csrf_token ();
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
		// domain ?
		if($zone->is_domain=="1") {
			// append domain if they are not same !
			if($zone->name!==$p)
			$p .= ".".$zone->name;
			// replace multiple dots to be sure
			$p = preg_replace('/\.+/', '.', $p);

			// make sure it is inside domain !
			if($Zones->is_host_inside_domain ($p, $zone->name)===false) {
				$Result->show("danger", _("Hostname not in zone").".", true, false, false, false);
			}
		}

		// not domain
		if($Common->validate_hostname($p) || strlen($p)>0) {
			// remove dots in case hostname is empty
			$index = substr($k, 9);
			$out[$index]['hostname'] = trim($p, ".");
		}
		else {
			$Result->show("danger", _("Invalid hostname").".", true, false, false, false);
		}
	}
	elseif (strpos($k, "pg-")!==false) {
		if($Common->validate_int($p)) {
			$index = substr($k, 3);
			$out[$index]['pg_id'] = $p;
		}
	}
}

// if empty entry was added we need to remove pg_id as hostname is missing
foreach ($out as $i=>$item) {
	if (!array_key_exists("hostname", $item)) {
		unset($out[$i]);
	}
}

# ok, validations passed, insert
try {
	foreach ($out as $o) {
		// object
		$new_object = ["z_id"=>$_POST['zone_id'], "pg_id"=>$o['pg_id'], "hostname"=>$o['hostname']];
		// insert
		$new_host_id = $Database->insertObject("hosts", $new_object);
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("hosts", $new_host_id, $tenant->id, $user->id, "add", true, "New host ".$o['hostname']." added to zone", NULL, json_encode(["hosts"=>["0"=>$new_object]]));
	}
	// ok
	$Result->show("success", _("Hosts created").".", false, false, false, false);

} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}
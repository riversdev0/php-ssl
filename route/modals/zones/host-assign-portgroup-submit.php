<?php

#
# Commit portgroup assignment
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

# fetch items
$tenant = $Tenants->get_tenant_by_href ($_POST['tenant']);
$zone   = $Zones->get_zone ($_POST['tenant'], $_POST['zone_id']);
$host   = $Zones->get_host ($_POST['host_id']);

# validate zone id
if($Common->validate_int($_POST['zone_id'])===false || $zone===NULL) {
	$Result->show("danger", _("Invalid zone").".", true, false, false, false);
}
# validate tenant
if($tenant===NULL) {
	$Result->show("danger", _("Invalid tenant").".", true, false, false, false);
}
# invalid host
if ($host===NULL || $host->z_id!=$zone->id) {
	$Result->show("danger", _("Invalid host"), true, false, false, false);
}
# validate pg_id
if($Common->validate_int($_POST['pg_id'])===false && strlen($_POST['pg_id'])>0) {
	$Result->show("danger", _("Invalid portgroup").".", true, false, false, false);
}

# no change ?
if($host->pg_id == $_POST['pg_id']) {
	$Result->show("info", _("No change").".", true, false, false, false);
}

# ok, validations passed, insert
try {
	$Database->updateObject("hosts", ["id"=>$_POST['host_id'], "pg_id"=>$_POST['pg_id']]);

	# get old portgroup name
	$all_port_groups = $SSL->get_all_port_groups ();
	$old_pg_name = isset($all_port_groups[$zone->t_id][$host->pg_id]['name']) ? $all_port_groups[$zone->t_id][$host->pg_id]['name'] : $host->pg_id;
	$new_pg_name = isset($all_port_groups[$zone->t_id][$_POST['pg_id']]['name']) ? $all_port_groups[$zone->t_id][$_POST['pg_id']]['name'] : $_POST['pg_id'];

	// Write log :: object, object_id, tenant_id, user_id, action, public, text
	$Log->write ("hosts", $_POST['host_id'], $tenant->id, $user->id, "edit", true, "Portgroup changed for host ".$host->hostname." from ".$old_pg_name." to ".$new_pg_name, json_encode($host->pg_id), json_encode($_POST['pg_id']));

} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}

# ok
$Result->show("success", _("Portgroup updated").".", false, false, false, false);

<?php

#
# Edit zone - submit
#



# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, true);
# validate permissions
$User->validate_user_permissions (3, true);

# validate tenant
$_params['tenant'] = $_POST['tenant'];
$User->validate_tenant (false, true);

# strip tags
$_POST = $User->strip_input_tags ($_POST);

# fetch zone
if($_POST['action']!="add")
$zone = $Zones->get_zone ($_POST['tenant'], $_POST['zone_id']);
# fetch tentant
$tenant = $Tenants->get_tenant_by_href ($_POST['tenant']);

// validate zone id
if(($Common->validate_int($_POST['zone_id'])===false || $zone===null) && $_POST['action']!=="add")
$Result->show("danger", _("Invalid zone").".", true, false, false, false);
// validate tenant
if($tenant===null)
$Result->show("danger", _("Invalid tenant").".", true, false, false, false);
// validate name
if($_POST['action']!=="delete") {
	if ($Common->validate_hostname($_POST['name'])===false)
	$Result->show("danger", _("Invalid name").".", true, false, false, false);
	// validate type
	if (!in_array($_POST['type'], ['local','axfr']))
	$Result->show("danger", _("Invalid zone type").".", true, false, false, false);
	// validate ignore
	if (!in_array($_POST['ignore'], [0,1]))
	$Result->show("danger", _("Invalid ignore value").".", true, false, false, false);
	// validate is_domain
	if (!in_array($_POST['is_domain'], [0,1]))
	$Result->show("danger", _("Invalid is_domain value").".", true, false, false, false);
	// agent validation
	if (!$Common->validate_int($_POST['agent_id']))
	$Result->show("danger", _("Invalid Agent").".", true, false, false, false);
}

// general update parameters
$update = [
	"name"        => $_POST['name'],
	"type"        => $_POST['type'],
	"t_id"        => $tenant->id,
	"ignore"      => $_POST['ignore'],
	"is_domain"   => $_POST['is_domain'],
	"description" => $_POST['description'],
	"agent_id"    => $_POST['agent_id']
];

// edit,delete - add key
if($_POST['action']!="add")
$update['id'] = $zone->id;

// axfr ?
if ($_POST['type']=="axfr" && $_POST['action']!=="delete") {
	// validations
	if (!$Common->validate_ip($_POST['dns']))
	$Result->show("danger", _("Invalid DNS address").".", true, false, false, false);
	if (!$Common->validate_hostname($_POST['aname']))
	$Result->show("danger", _("Invalid zone name").".", true, false, false, false);
	if (!$Common->validate_alphanumeric($_POST['tsig_name'], true))
	$Result->show("danger", _("Invalid TSIG name").".", true, false, false, false);
	if (!$Common->validate_alphanumeric($_POST['tsig'], true))
	$Result->show("danger", _("Invalid TSIG").".", true, false, false, false);
	if (!$Common->validate_alphanumeric($_POST['record_types'], true))
	$Result->show("danger", _("Invalid record types").".", true, false, false, false);
	if (!in_array($_POST['delete_records'], [0,1]))
	$Result->show("danger", _("Invalid delete record value").".", true, false, false, false);
	if (!in_array($_POST['check_ip'], [0,1]))
	$Result->show("danger", _("Invalid check IP value").".", true, false, false, false);
	if (!$Common->validate_regex_string($_POST['regex_include']) && strlen($_POST['regex_include']>0))
	$Result->show("danger", _("Invalid include regex").".", true, false, false, false);
	if (!$Common->validate_regex_string($_POST['regex_exclude']) && strlen($_POST['regex_exclude']>0))
	$Result->show("danger", _("Invalid exclude regex").".", true, false, false, false);

	// save
	$update['dns']            = $_POST['dns'];
	$update['aname']          = $_POST['aname'];
	$update['tsig_name']      = $_POST['tsig_name'];
	$update['tsig']           = $_POST['tsig'];
	$update['record_types']   = $_POST['record_types'];
	$update['delete_records'] = $_POST['delete_records'];
	$update['check_ip'] 	  = $_POST['check_ip'];
	$update['regex_include']  = $_POST['regex_include'];
	$update['regex_exclude']  = $_POST['regex_exclude'];
}


# edit, verify change is present
if($_POST['action']=="edit"){
	$is_change = false;

	// description fix ker mergamo select iz 2 baz !
	$zone->description = $zone->z_description;

	foreach ($update as $k=>$u) {
		if ($zone->$k!==$u) {
			$is_change = true;
			break;
		}
	}

	if($is_change===false)
	$Result->show("info", _("No change").".", true, false, false, false);
}


# ok, validations passed, insert
try {
	// add
	if($_POST['action']=="add") {
		$new_zone_id = $Database->insertObject("zones", $update);
		// ok
		$Result->show("success", _("Zone created").".", false, false, false, false);
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("zones", $new_zone_id, $tenant->id, $user->id, $_POST['action'], true, "Zone $update[name] created", NULL, json_encode($update));
	}
	// update
	elseif($_POST['action']=="edit") {
		$Database->updateObject("zones", $update);
		// ok
		$Result->show("success", _("Zone updated").".", false, false, false, false);
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("zones", $zone->id, $tenant->id, $user->id, $_POST['action'], true, "Zone $update[name] updated", json_encode($zone), json_encode($update));
	}
	elseif($_POST['action']=="delete") {
		$Database->deleteObject("zones", $update['id']);
		// ok
		$Result->show("success", _("Zone deleted").".", false, false, false, false);
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("zones", $zone->id, $tenant->id, $user->id, $_POST['action'], true, "Zone ".$zone->name." deleted", json_encode($zone), NULL);
	}
	else {
		throw new exception("Invalid action");
	}
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}
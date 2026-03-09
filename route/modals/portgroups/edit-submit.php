<?php

#
# Edit port group - submit
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session ();
$User->validate_csrf_token ();
# validate permissions
$User->validate_user_permissions (3, true);

# strip tags
$_POST = $User->strip_input_tags ($_POST);

# fetch tenant and validate
if($_POST['action']=="add") {
	# get tenant
	$tenant = $Tenants->get_tenant_by_href ($_POST['t_id']);
	# invalid tenant
	if($tenant===null)
	$Result->show("danger", _("Invalid tenant").".", true, false, false, false);
	# not allowed
	if($user->admin !== "1" && $user->t_id!=$_POST['t_id'])
	$Result->show("danger", _("Admin privileges required").".", true, false, false, false);
}
else {
	# get port group
	$port_group = $Database->getObject ("ssl_port_groups",$_POST['id']);
	# invalid port group
	if($port_group===null)
	$Result->show("danger", _("Invalid port group").".", true, false, false, false);
	# get tenant
	$tenant = $Tenants->get_tenant_by_href ($port_group->t_id);
	# not allowed
	if($user->admin !== "1" && $user->t_id!=$port_group->t_id)
	$Result->show("danger", _("Admin privileges required").".", true, false, false, false);
}


# validate variables
if($_POST['action']!=="delete") {
	if($Common->validate_alphanumeric($_POST['name'])===false)
	$Result->show("danger", _("Invalid name").". "._("Only alphanumeric characters are allowed").".", true, false, false, false);

	# validate ports - comma separated numbers
	$ports = explode(",", $_POST['ports']);
	foreach ($ports as $p) {
		$p = trim($p);
		if(!is_numeric($p) || $p < 1 || $p > 65535)
		$Result->show("danger", _("Invalid port number").": ".$p.". "._("Ports must be comma separated numbers between 1 and 65535").".", true, false, false, false);
	}
}

# add, edit
if ($_POST['action']!="delete") {
	# name
	if($Common->validate_alphanumeric($_POST['name'])===false)
	$Result->show("danger", _("Invalid name value").".", true, false, false, false);
}

# general update parameters
$update = [
	"name"            => $_POST['name'],
	"ports"           => $_POST['ports']
];

# add - add t_id
if($_POST['action']=="add") {
	$update['t_id'] = $tenant->id;
}

# edit,delete - add key
if($_POST['action']!="add") {
	$update['id'] = $port_group->id;
}


# edit, verify change is present
if($_POST['action']=="edit"){
	$is_change = false;

	foreach ($update as $k=>$u) {
		if ($port_group->$k!==$u) {
			$is_change = true;
			break;
		}
	}

	if($is_change===false)
	$Result->show("info", _("No change").".", true, false, false, false);
}


# ok, validations passed, insert
try {
	# add
	if($_POST['action']=="add") {
		$new_pg_id = $Database->insertObject("ssl_port_groups", $update);
		# ok
		$Result->show("success", _("Port group created").".", false, false, false, false);
		# get port group
		$port_group = $Database->getObject ("ssl_port_groups",$new_pg_id);
		# Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("portgroups", $new_pg_id, $tenant->id, $user->id, $_POST['action'], true, "Port group $update[name] created", null, json_encode(["portgroups"=>["0"=>$port_group]]));
	}
	# update
	elseif($_POST['action']=="edit") {
		$Database->updateObject("ssl_port_groups", $update);
		# ok
		$Result->show("success", _("Port group updated").".", false, false, false, false);
		# get port group
		$port_group_new = $Database->getObject ("ssl_port_groups",$port_group->id);
		# Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("portgroups", $port_group->id, $tenant->id, $user->id, $_POST['action'], true, "Port group $update[name] updated", json_encode(["portgroups"=>["0"=>$port_group]]), json_encode(["portgroups"=>["0"=>$port_group_new]]), true);
	}
	elseif($_POST['action']=="delete") {
		$Database->deleteObject("ssl_port_groups", $update['id']);
		# ok
		$Result->show("success", _("Port group deleted").".", false, false, false, false);
		# Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("portgroups", $port_group->id, $tenant->id, $user->id, $_POST['action'], true, "Port group ".$port_group->name." deleted", json_encode(["portgroups"=>["0"=>$port_group]]), null, true);
	}
	else {
		throw new exception("Invalid action");
	}
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}

<?php

#
# Edit domain - submit
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, true);
$User->validate_csrf_token ();
# validate permissions
$User->validate_user_permissions (3, true);

# strip tags
$_POST = $User->strip_input_tags ($_POST);

# validate action
if(!$Common->validate_action($_POST['action']))
$Result->show("danger", _("Invalid action").".", true, false, false, false);

# fetch tenant and validate
$tenant = $Tenants->get_tenant_by_href ($_POST['tenant']);
if($tenant===null)
$Result->show("danger", _("Invalid tenant").".", true, false, false, false);

# tenant access - non-admins can only manage their own tenant
if($user->admin !== "1" && $user->t_id !== $tenant->id)
$Result->show("danger", _("Access denied").".", true, false, false, false);

# fetch domain to edit/delete
if($_POST['action']!=="add") {
	if(!$Common->validate_int($_POST['id']))
	$Result->show("danger", _("Invalid domain ID").".", true, false, false, false);

	$domain = $Database->getObject("domains", $_POST['id']);
	if($domain===null)
	$Result->show("danger", _("Invalid domain").".", true, false, false, false);

	# IDOR: verify domain belongs to tenant
	if($domain->t_id !== $tenant->id)
	$Result->show("danger", _("Access denied").".", true, false, false, false);
}

# validate fields (not on delete)
if($_POST['action']!=="delete") {
	# name
	if($Common->validate_alphanumeric($_POST['name'])===false || strlen(trim($_POST['name']))==0)
	$Result->show("danger", _("Invalid name").".", true, false, false, false);

	# type — Local is not a valid type for new domains
	$allowed_types = $_POST['action']==='add' ? ['AD'] : ['AD', 'local'];
	if(!in_array($_POST['type'], $allowed_types))
	$Result->show("danger", _("Invalid type").".", true, false, false, false);

	# active
	if(!in_array($_POST['active'], ['Yes', 'No']))
	$Result->show("danger", _("Invalid active value").".", true, false, false, false);

	# port
	if(!is_numeric($_POST['port']) || (int)$_POST['port'] < 1 || (int)$_POST['port'] > 65535)
	$Result->show("danger", _("Invalid port value").".", true, false, false, false);

	# account_suffix - allow empty, max length
	if(strlen($_POST['account_suffix']) > 256)
	$Result->show("danger", _("Account suffix too long").".", true, false, false, false);

	# base_dn - allow empty, max length
	if(strlen($_POST['base_dn']) > 256)
	$Result->show("danger", _("Base DN too long").".", true, false, false, false);

	# domain_controllers - allow empty, max length
	if(strlen($_POST['domain_controllers']) > 256)
	$Result->show("danger", _("Domain controllers value too long").".", true, false, false, false);

	# autocreateGroup - allow empty
	if(strlen($_POST['autocreateGroup']) > 0 && $Common->validate_alphanumeric($_POST['autocreateGroup'])===false)
	$Result->show("danger", _("Invalid autocreate group value").".", true, false, false, false);
}

# build update array
$update = [];
if($_POST['action']!=="delete") {
	$update['name']               = trim($_POST['name']);
	$update['type']               = $_POST['type'];
	$update['active']             = $_POST['active'];
	$update['port']               = (int)$_POST['port'];
	$update['use_ssl']            = !empty($_POST['use_ssl']) ? 1 : 0;
	$update['use_tls']            = !empty($_POST['use_tls']) ? 1 : 0;
	$update['account_suffix']     = $_POST['account_suffix'];
	$update['base_dn']            = $_POST['base_dn'];
	$update['domain_controllers'] = $_POST['domain_controllers'];
	$update['autocreateGroup']    = $_POST['autocreateGroup'];

	# admin credentials - only update if user is admin
	if($user->admin === "1") {
		$update['adminUsername'] = strlen($_POST['adminUsername']) > 0 ? $_POST['adminUsername'] : null;
		# only update password if provided
		if(strlen($_POST['adminPassword']) > 0) {
			$update['adminPassword'] = $_POST['adminPassword'];
		}
	}
}

# add - set t_id
if($_POST['action']==="add") {
	$update['t_id'] = $tenant->id;
}

# edit/delete - set id
if($_POST['action']!=="add") {
	$update['id'] = $domain->id;
}

# edit: check for actual changes
if($_POST['action']==="edit") {
	$is_change = false;
	foreach(['name', 'type', 'active', 'port', 'use_ssl', 'use_tls', 'account_suffix', 'base_dn', 'domain_controllers', 'autocreateGroup'] as $k) {
		if(isset($update[$k]) && $domain->$k != $update[$k]) {
			$is_change = true;
			break;
		}
	}
	if($user->admin === "1") {
		if(isset($update['adminUsername']) && $domain->adminUsername != $update['adminUsername']) $is_change = true;
		if(isset($update['adminPassword'])) $is_change = true;
	}

	if($is_change===false)
	$Result->show("info", _("No change").".", true, false, false, false);
}


# ok, validations passed
try {
	# add
	if($_POST['action']==="add") {
		$new_domain_id = $Database->insertObject("domains", $update);
		$Result->show("success", _("Domain created").".", false, false, false, false);
		$domain_new = $Database->getObject("domains", $new_domain_id);
		$log_data = (array)$domain_new; unset($log_data['adminPassword']);
		$Log->write("domains", $new_domain_id, $tenant->id, $user->id, $_POST['action'], true, "Domain ".$update['name']." created", null, json_encode(["domains"=>["0"=>$log_data]]));
	}
	# update
	elseif($_POST['action']==="edit") {
		$before = (array)$domain; unset($before['adminPassword']);
		$Database->updateObject("domains", $update);
		$Result->show("success", _("Domain updated").".", false, false, false, false);
		$domain_new = $Database->getObject("domains", $domain->id);
		$after = (array)$domain_new; unset($after['adminPassword']);
		$Log->write("domains", $domain->id, $tenant->id, $user->id, $_POST['action'], true, "Domain ".$domain->name." updated", json_encode(["domains"=>["0"=>$before]]), json_encode(["domains"=>["0"=>$after]]), true);
	}
	elseif($_POST['action']==="delete") {
		$Database->deleteObject("domains", $update['id']);
		$Result->show("success", _("Domain deleted").".", false, false, false, false);
		$log_data = (array)$domain; unset($log_data['adminPassword']);
		$Log->write("domains", $domain->id, $tenant->id, $user->id, $_POST['action'], true, "Domain ".$domain->name." deleted", json_encode(["domains"=>["0"=>$log_data]]), null, true);
	}
	else {
		throw new exception("Invalid action");
	}
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}

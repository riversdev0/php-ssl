<?php

#
# Edit issuer - submit
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

# fetch tentant and validate
if($_POST['action']=="add") {
	// get tenant
	$tenant = $Tenants->get_tenant_by_href ($_POST['t_id']);
	// invalid tenant
	if($tenant===null)
	$Result->show("danger", _("Invalid tenant").".", true, false, false, false);
	// not allowed
	if($user->admin !== "1" && $user->t_id!=$_POST['t_id'])
	$Result->show("danger", _("Admin privileges required").".", true, false, false, false);
}
else {
	// get issuer
	$issuer = $Database->getObject ("ignored_issuers",$_POST['id']);
	// invalid issuer
	if($issuer===null)
	$Result->show("danger", _("Invalid issuer").".", true, false, false, false);
	// not allowed
	if($user->admin !== "1" && $user->t_id!=$issuer->t_id)
	$Result->show("danger", _("Admin privileges required").".", true, false, false, false);
}

# add, edit
if ($_POST['action']!="delete") {
	// name
	if($Common->validate_alphanumeric($_POST['name'])===false)
	$Result->show("danger", _("Invalid name value").".", true, false, false, false);
	// url
	if($Common->validate_alphanumeric($_POST['ski'])===false)
	$Result->show("danger", _("Invalid SKI value").".", true, false, false, false);
}

// general update parameters
$update = [
	"name"            => $_POST['name'],
	"ski"             => $_POST['ski'],
];

// add/edit: include the new flag fields (checkboxes not submitted when unchecked, so fall back to 0)
if($_POST['action']!="delete") {
	$update['update']  = isset($_POST['update'])  && $_POST['update']  == '1' ? 1 : 0;
	$update['expired'] = isset($_POST['expired']) && $_POST['expired'] == '1' ? 1 : 0;
}

// add - add t_id
if($_POST['action']=="add") {
	$update['t_id'] = $tenant->id;
}

// edit,delete - add key
if($_POST['action']!="add") {
	$update['id'] = $issuer->id;
}

# ok, validations passed, insert
try {
	// add
	if($_POST['action']=="add") {
		$new_ignored_id = $Database->insertObject("ignored_issuers", $update);
		// ok
		$Result->show("success", _("Ignored issuer created").".", false, false, false, false);
		// add id
		$update['id'] = $new_ignored_id;
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("ignored", $new_ignored_id, $tenant->id, $user->id, $_POST['action'], true, "Ignored issuer $update[name] created", null, json_encode(["ignored"=>["0"=>$update]]));
	}
	elseif($_POST['action']=="edit") {
		$Database->updateObject("ignored_issuers", $update);
		// ok
		$Result->show("success", _("Ignored issuer updated").".", false, false, false, false);
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("ignored", $issuer->id, $issuer->t_id, $user->id, $_POST['action'], true, "Ignored issuer $update[name] updated", json_encode(["ignored"=>["0"=>$issuer]]), json_encode(["ignored"=>["0"=>$update]]), false);
	}
	elseif($_POST['action']=="delete") {
		$Database->deleteObject("ignored_issuers", $update['id']);
		// ok
		$Result->show("success", _("Ignored issuer deleted").".", false, false, false, false);
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("ignored", $issuer->id, $issuer->t_id, $user->id, $_POST['action'], true, "Ignored issuer $update[name] deleted", json_encode(["ignored"=>["0"=>$issuer]]), null, true);
	}
	else {
		throw new exception("Invalid action");
	}
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}
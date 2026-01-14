<?php

#
# Edit agent - submit
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session ();
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
	// get agent
	$agent = $Database->getObject ("agents",$_POST['id']);
	// invalid agent
	if($agent===null)
	$Result->show("danger", _("Invalid agent").".", true, false, false, false);
	// not allowed
	if($user->admin !== "1" && $user->t_id!=$agent->t_id)
	$Result->show("danger", _("Admin privileges required").".", true, false, false, false);
}


# validate variables
if($_POST['action']!=="delete") {
	if($Common->validate_alphanumeric($_POST['name'])===false)
	$Result->show("danger", _("Invalid name").". "._("Only alphanumeric characters are allowed").".", true, false, false, false);

	if($Common->validate_url($_POST['url'])===false)
	$Result->show("danger", _("Invalid url value").".", true, false, false, false);
}

# add, edit
if ($_POST['action']!="delete") {
	// name
	if($Common->validate_alphanumeric($_POST['name'])===false)
	$Result->show("danger", _("Invalid name value").".", true, false, false, false);
	// url
	if($Common->validate_url($_POST['url'])===false)
	$Result->show("danger", _("Invalid url value").".", true, false, false, false);
	// validate comment
	if($Common->validate_alphanumeric($_POST['comment'])===false)
	$Result->show("danger", _("Invalid comment value").".", true, false, false, false);
}

// general update parameters
$update = [
	"name"            => $_POST['name'],
	"url"             => $_POST['url'],
	"comment"     	  => $_POST['comment']
];

// add - add t_id
if($_POST['action']=="add") {
	$update['t_id'] = $tenant->id;
}

// edit,delete - add key
if($_POST['action']!="add") {
	$update['id'] = $agent->id;
}

# ok, validations passed, insert
try {
	// add
	if($_POST['action']=="add") {
		$Database->insertObject("agents", $update);
		// ok
		$Result->show("success", _("Agent created").".", false, false, false, false);
	}
	// update
	elseif($_POST['action']=="edit") {
		$Database->updateObject("agents", $update);
		// ok
		$Result->show("success", _("Agent updated").".", false, false, false, false);
	}
	elseif($_POST['action']=="delete") {
		$Database->deleteObject("agents", $update['id']);
		// ok
		$Result->show("success", _("Agent deleted").".", false, false, false, false);
	}
	else {
		throw new exception("Invalid action");
	}
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}
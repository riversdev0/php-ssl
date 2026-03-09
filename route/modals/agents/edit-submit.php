<?php

#
# Edit agent - submit
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
	// get agent
	$agent = $Database->getObject ("agents",$_POST['id']);
	// invalid agent
	if($agent===null)
	$Result->show("danger", _("Invalid agent").".", true, false, false, false);
	// get tenant
	$tenant = $Tenants->get_tenant_by_href ($agent->t_id);
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



# edit, verify change is present
if($_POST['action']=="edit"){
	$is_change = false;

	foreach ($update as $k=>$u) {
		if ($agent->$k!==$u) {
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
		$new_agent_id = $Database->insertObject("agents", $update);
		// ok
		$Result->show("success", _("Agent created").".", false, false, false, false);
		// get agent
		$agent = $Database->getObject ("agents",$new_agent_id);
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("agents", $new_agent_id, $tenant->id, $user->id, $_POST['action'], true, "Agent $update[name] created", null, json_encode(["agents"=>["0"=>$agent]]));
	}
	// update
	elseif($_POST['action']=="edit") {
		$Database->updateObject("agents", $update);
		// ok
		$Result->show("success", _("Agent updated").".", false, false, false, false);
		// get agent
		$agent_new = $Database->getObject ("agents",$agent->id);
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("agents", $agent->id, $tenant->id, $user->id, $_POST['action'], true, "Agent $update[name] updated", json_encode(["agents"=>["0"=>$agent]]), json_encode(["agents"=>["0"=>$agent_new]]), true);
	}
	elseif($_POST['action']=="delete") {
		$Database->deleteObject("agents", $update['id']);
		// ok
		$Result->show("success", _("Agent deleted").".", false, false, false, false);
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("agents", $agent->id, $tenant->id, $user->id, $_POST['action'], true, "Agent $update[name] deleted", json_encode(["agents"=>["0"=>$agent]]), null, true);
	}
	else {
		throw new exception("Invalid action");
	}
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}
<?php

#
# Edit zone - submit
#



# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (true, true, true);
$User->validate_csrf_token ();
# validate permissions
$User->validate_user_permissions (3, true);


# strip tags
$_POST = $User->strip_input_tags ($_POST);

# fetch tentant and validate
if($_POST['action']!="add") {
	$tenant = $Tenants->get_tenant_by_href ($_POST['id']);
	if($tenant===null)
	$Result->show("danger", _("Invalid tenant").".", true, false, false, false);
}

# add, edit
if ($_POST['action']!="delete") {
	// validate name
	if($Common->validate_alphanumeric($_POST['name'])===false)
	$Result->show("danger", _("Invalid name value").".", true, false, false, false);

	// href
	if($Common->validate_alphanumeric($_POST['href'])===false)
	$Result->show("danger", _("Invalid href value").".", true, false, false, false);

	// active
	if($Common->validate_bin($_POST['active'])===false)
	$Result->show("danger", _("Invalid active value").".", true, false, false, false);

	// validate remove_orphaned
	if($Common->validate_bin($_POST['remove_orphaned'])===false)
	$Result->show("danger", _("Invalid remove orphaned value").".", true, false, false, false);

	// valiudate recipients
	$recipients = array_filter(explode(";", str_replace(",", ";", $_POST['recipients'])));
	if(sizeof($recipients)>0) {
		foreach ($recipients as $r) {
			if($Common->validate_mail($r)===false)
			$Result->show("danger", _("Invalid remove orphaned value").".", true, false, false, false);
		}
	}
	$_POST['recipients'] = implode(";", $recipients);

	// validate log_retention
	if(!is_numeric($_POST['log_retention']) || (int)$_POST['log_retention'] < 1 || (int)$_POST['log_retention'] > 3650)
	$Result->show("danger", _("Invalid log retention value. Must be between 1 and 3650 days").".", true, false, false, false);
}

// general update parameters
$update = [
	"name"            => $_POST['name'],
	"href"            => $_POST['href'],
	"description"     => $_POST['description'],
	"active"          => $_POST['active'],
	"remove_orphaned" => $_POST['remove_orphaned'],
	"mail_style"	  => $_POST['mail_style'],
	"recipients"      => $_POST['recipients'],
	"log_retention"   => (int)$_POST['log_retention']
];

// edit,delete - add key
if($_POST['action']!="add") {
	$update['id'] = $tenant->id;
	$update['order'] = $tenant->order;
	$update['admin'] = $tenant->admin;
}


# edit, verify change is present
if($_POST['action']=="edit"){
	$is_change = false;

	foreach ($update as $k=>$u) {
		if ($tenant->$k!==$u) {
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
		$new_tenant_id = $Database->insertObject("tenants", $update);
		// set random cronjobs
		$rand = $Cron->rand(0,60,5);

		// add default cronjobs
		$Database->insertObject("cron", ["t_id"=>$new_tenant_id, "minute"=>$rand, "hour"=>"*", "day"=>"*", "weekday"=>"*", "script"=>"update_certificates"]);
		$Database->insertObject("cron", ["t_id"=>$new_tenant_id, "minute"=>$rand, "hour"=>2,   "day"=>"*", "weekday"=>"*", "script"=>"remove_orphaned"]);
		$Database->insertObject("cron", ["t_id"=>$new_tenant_id, "minute"=>$rand, "hour"=>8,   "day"=>"*", "weekday"=>"*", "script"=>"expired_certificates"]);
		$Database->insertObject("cron", ["t_id"=>$new_tenant_id, "minute"=>$rand, "hour"=>3,   "day"=>"*", "weekday"=>"*", "script"=>"axfr_transfer"]);

		// add default ports
		$Database->insertObject("ssl_port_groups", ["t_id"=>$new_tenant_id, "name"=>"pg_ssl", "ports"=>"443"]);

		// get tenant
		$new_tenant = $Tenants->get_tenant_by_href ($update['href']);

		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("tenants", $new_tenant_id, $new_tenant_id, $user->id, $_POST['action'], true, "New tenant created", NULL, json_encode(["tenants"=>["0"=>$new_tenant]]));
		// ok
		$Result->show("success", _("Tenant created").".", false, false, false, false);
	}
	// update
	elseif($_POST['action']=="edit") {
		$Database->updateObject("tenants", $update);
		// ok
		$Result->show("success", _("Tenant updated").".", false, false, false, false);
		// get tenant
		$new_tenant = $Tenants->get_tenant_by_href ($update['href']);
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("tenants", $tenant->id, $tenant->id, $user->id, $_POST['action'], true, "Tenant $update[name] updated", json_encode(["tenants"=>["0"=>$tenant]]), json_encode(["tenants"=>["0"=>$new_tenant]]), true);
	}
	elseif($_POST['action']=="delete") {
		$Database->deleteObject("tenants", $update['id']);
		// ok
		$Result->show("success", _("Tenant deleted").".", false, false, false, false);
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("tenants", $tenant->id, $tenant->id, $user->id, $_POST['action'], true, "Tenant ".$tenant->name." deleted", json_encode(["tenants"=>["0"=>$tenant]]), NULL);
	}
	else {
		throw new exception("Invalid action");
	}
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}
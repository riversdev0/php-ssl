<?php

#
# Edit zone - submit
#



# functions
require('../../functions/autoload.php');
# validate user session
$User->validate_session ();
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
}

// general update parameters
$update = [
	"name"            => $_POST['name'],
	"href"            => $_POST['href'],
	"description"     => $_POST['description'],
	"active"          => $_POST['active'],
	"remove_orphaned" => $_POST['remove_orphaned'],
	"mail_style"	  => $_POST['mail_style'],
	"recipients"      => $_POST['recipients']
];

// edit,delete - add key
if($_POST['action']!="add") {
	$update['id'] = $tenant->id;
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

		// ok
		$Result->show("success", _("Tenant created").".", false, false, false, false);
	}
	// update
	elseif($_POST['action']=="edit") {
		$Database->updateObject("tenants", $update);
		// ok
		$Result->show("success", _("Tenant updated").".", false, false, false, false);
	}
	elseif($_POST['action']=="delete") {
		$Database->deleteObject("tenants", $update['id']);
		// ok
		$Result->show("success", _("Tenant deleted").".", false, false, false, false);
	}
	else {
		throw new exception("Invalid action");
	}
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}
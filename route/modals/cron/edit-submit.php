<?php

#
# Edit cronjob - submit
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session ();
# validate permissions
$User->validate_user_permissions (3, true);

# strip tags
$_POST = $User->strip_input_tags ($_POST);

# validate action
if($_POST['action']!="edit") {
	$Result->show("danger", _("Invalid action").".", true, false, false, false);
}

# fetch cronjob
$cronjob = $Database->getObject ("cron", $_POST['id']);

# invalid cronjob
if($cronjob===null)
$Result->show("danger", _("Invalid cronjob").".", true, false, false, false);

# get tenant
$tenant = $Tenants->get_tenant_by_href ($cronjob->t_id);

# not allowed
if($user->admin !== "1" && $user->t_id!=$cronjob->t_id)
$Result->show("danger", _("Admin privileges required").".", true, false, false, false);


# validate cron fields
$cron_fields = ['minute', 'hour', 'day', 'month', 'weekday'];
foreach ($cron_fields as $field) {
	if(!isset($_POST[$field]) || strlen($_POST[$field])==0) {
		$Result->show("danger", _("Invalid {$field}").".", true, false, false, false);
	}
}

# build full cron expression and validate
$cron_expression = $_POST['minute'].' '.$_POST['hour'].' '.$_POST['day'].' '.$_POST['month'].' '.$_POST['weekday'];
if($Common->validate_cron($cron_expression)===false) {
	$Result->show("danger", _("Invalid cron expression").": ".$cron_expression.".". implode("; ", $Common->errors), true, false, false, false);
}


# general update parameters
$update = [
	"minute"   => $_POST['minute'],
	"hour"     => $_POST['hour'],
	"day"      => $_POST['day'],
	"month"    => $_POST['month'],
	"weekday"  => $_POST['weekday']
];

# add key
$update['id'] = $cronjob->id;


# edit, verify change is present
$is_change = false;
foreach ($update as $k=>$u) {
	if ($cronjob->$k!==$u) {
		$is_change = true;
		break;
	}
}

if($is_change===false)
$Result->show("info", _("No change").".", true, false, false, false);


# ok, validations passed, update
try {
	$Database->updateObject("cron", $update);
	# ok
	$Result->show("success", _("Cronjob updated").".", false, false, false, false);
	# get updated cronjob
	$cronjob_new = $Database->getObject ("cron", $cronjob->id);
	# Write log
	$Log->write ("cron", $cronjob->id, $tenant->id, $user->id, $_POST['action'], true, "Cronjob {$cronjob->script} updated", json_encode($cronjob), json_encode($cronjob_new));
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}

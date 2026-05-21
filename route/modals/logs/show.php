<?php

#
# Show log entry details
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session ();

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# validate id
if(!$Common->validate_int($_GET['id'])) {
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid log ID"), false, false, true);
	$header_class = "danger";
	$Modal->modal_print (_("Log details"), implode("\n", $content), "", "", false, $header_class);
	die();
}

# fetch log
$log = $Log->get_log_by_id ((int)$_GET['id'], $user);

if($log === null || $log === false) {
	$content      = [];
	$content[]    = $Result->show("danger", _("Log entry not found"), false, false, true);
	$header_class = "danger";
	$Modal->modal_print (_("Log details"), implode("\n", $content), "", "", false, $header_class);
	die();
}

# fetch users
$users = $User->get_all ();

# decode JSON objects
$logdata_old = json_decode($log->json_object_old, true);
$logdata_new = json_decode($log->json_object_new, true);
if($logdata_old === null) { $logdata_old = []; }
if($logdata_new === null) { $logdata_new = []; }

# build content
$content = [];

$content[] = "<table class='table table-borderless table-md table-zones-details'>";

# user
$u_name = isset($users[$log->object_u_id]) ? htmlspecialchars($users[$log->object_u_id]->name, ENT_QUOTES, 'UTF-8') : _("System");
$content[] = "<tr><td class='text-secondary' style='min-width:100px;width:120px;'>"._("User")."</td><td><b>".$u_name."</b></td></tr>";

# tenant (admin only)
if($user->admin === "1") {
	$tenant = $Database->getObject("tenants", $log->object_t_id);
	$t_name = $tenant ? htmlspecialchars($tenant->name, ENT_QUOTES, 'UTF-8') : "-";
	$content[] = "<tr><td class='text-secondary'>"._("Tenant")."</td><td>".$t_name."</td></tr>";
}

# object
$content[] = "<tr><td class='text-secondary'>"._("Object")."</td><td>".htmlspecialchars(ucwords($log->object), ENT_QUOTES, 'UTF-8')."</td></tr>";

# action
$log_nice = clone $log;
$log_nice = $Log->format_log_entry($log_nice, $user);
$content[] = "<tr><td class='text-secondary'>"._("Action")."</td><td>".$log_nice->action."</td></tr>";

# content
$content[] = "<tr><td class='text-secondary'>"._("Content")."</td><td>".htmlspecialchars($log->text, ENT_QUOTES, 'UTF-8')."</td></tr>";

# date
$content[] = "<tr><td class='text-secondary'>"._("Date")."</td><td>".$log_nice->date."</td></tr>";

$content[] = "</table>";

# show old/new objects if user has permission and objects exist
if($User->get_user_permissions(3) && (strlen($log->json_object_old) > 0 || strlen($log->json_object_new) > 0) && $log->action !== "notification") {

	# diff for edit actions
	if(strlen($log->json_object_old) > 0 && strlen($log->json_object_new) > 0 && $log->action !== "add" && $log->action !== "delete" && $log->action !== "refresh") {
		$d1 = $logdata_old;
		$d2 = $logdata_new;
		$diff = _log_modal_diff($d2, $d1);
		if(!empty($diff)) {
			$content[] = "<hr style='margin:8px 0'>";
			$content[] = "<small class='text-muted'>"._("Changed fields")."</small>";
			$content[] = "<pre class='diff' style='margin:4px 0;font-size:12px;'>".$Log->pretty_json(json_encode($diff))."</pre>";
		}
	}

	# old object
	if(strlen($log->json_object_old) > 0) {
		$content[] = "<hr style='margin:8px 0'>";
		$content[] = "<small class='text-muted'>"._("Old object")."</small>";
		$content[] = "<pre class='diff' style='margin:4px 0;font-size:12px;'>".$Log->pretty_json(json_encode($logdata_old))."</pre>";
	}

	# new object
	if(strlen($log->json_object_new) > 0) {
		$content[] = "<hr style='margin:8px 0'>";
		$content[] = "<small class='text-muted'>"._("New object")."</small>";
		$content[] = "<pre class='diff' style='margin:4px 0;font-size:12px;'>".$Log->pretty_json(json_encode($logdata_new))."</pre>";
	}
}

# email notification
if($log->action === "notification") {
	$maildata = json_decode($log->json_object_new);
	if($maildata) {
		$content[] = "<hr style='margin:8px 0'>";
		$content[] = "<table class='table table-borderless table-md'>";
		$content[] = "<tr><td class='text-secondary' style='width:120px;'>"._("Title")."</td><td>".htmlspecialchars($maildata->title, ENT_QUOTES, 'UTF-8')."</td></tr>";
		$content[] = "<tr><td class='text-secondary'>"._("Sent to")."</td><td>".htmlspecialchars(implode(", ", json_decode($log->json_object_old)), ENT_QUOTES, 'UTF-8')."</td></tr>";
		$content[] = "</table>";
	}
}

# print modal
$Modal->modal_print (_("Log details")." [".(int)$log->id."]", implode("\n", $content), "", "", false, "info");


function _log_modal_diff($a1, $a2) {
	$r = [];
	foreach ($a1 as $k => $v) {
		if(array_key_exists($k, $a2)) {
			if(is_array($v)) {
				$sub = _log_modal_diff($v, $a2[$k]);
				if(!empty($sub)) { $r[$k] = $sub; }
			} else {
				if($v != $a2[$k]) {
					$old = is_null($a2[$k]) ? 'null' : $a2[$k];
					$r[$k] = $old." => ".$v;
				}
			}
		} else {
			$r[$k] = $v;
		}
	}
	return $r;
}

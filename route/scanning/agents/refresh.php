<?php

#
# Refresh agent
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session ();
# validate permissions
$User->validate_user_permissions (3, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# tenant
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);

# fetch agent
$agent = $Database->getObject ("agents",$_GET['id']);

#
# title
#
$title = _("Refresh")." "._("agent");

# tenant validation
if($user->admin !== "1" && $user->t_id!=$_GET['tenant']) {
	# content
	$content = [];
	$content[] = $Result->show("danger", _("Admin user required"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
# validate agent
elseif (is_null($agent)) {
	# content
	$content = [];
	$content[] = $Result->show("danger", _("Invalid agent"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
else {
	// content
	$content = [];
	// check agent status
	$Agent = new Agent ();
	// test
	$resp = $Agent->test_agents ($Database, "google.com", 443, date("Y-m-d H:i:s"), $agent->id, true);

	$header_class = "info";


	// print "<pre>";
	// print_r($resp);

	// print
	$content[] = $Result->show("info", _("Agent updated"), false, false, true);


	// process response
	$content[] = "<hr>";
	$content[] = "<table class='table table-sm table-borderless'>";
	$content[] = "<tr><th>"._("Queried URL")."</th><td>".$resp['info']['url']."</td>";
	// error ?
	if(strlen($resp['error'])>0) {
		$content[] = "<tr><th>"._("Curl error")."</th><td>http/".$resp['error']."</td>";
		$header_class = "danger";
	}
	else {
		$content[] = "<tr><th>"._("HTTP code")."</th><td>http ".$resp['info']['http_code']." :: ".$Agent->name_http_code($resp['info']['http_code'])."</td>";
		$content[] = "<tr><th>"._("HTTP version")."</th><td>http/".$resp['info']['http_version']."</td>";
	}

	// success ?
	if(isset($resp['data']['success'])) {
		if ($resp['data']['success']=="1") {
			$content[] = "<tr><th>"._("Retrieved serial")."</th><td>".$resp['data']['result']['serial']."</td>";
		}
		else {
			$content[] = "<tr><th>"._("Agent Error")."</th><td>".$resp['data']['result']['error']."</td>";
		}
	}

	$content[] = "</table>";
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), "", false, true, $header_class);

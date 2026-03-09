<?php

#
# Edit agent
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
if($_GET['action']!=="add")
$agent = $Database->getObject ("agents",$_GET['id']);

#
# title
#
$title = _(ucwords($_GET['action']))." "._("agent");

# validate action
if(!$User->validate_action($_GET['action'])) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid action"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
# rtenant validation
elseif($user->admin !== "1" && $user->t_id!=$_GET['tenant']) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Admin user required"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
# validate agent
elseif ($_GET['action']!=="add" && is_null($agent)) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid agent"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
# validate agent belongs to tenant (IDOR)
elseif ($_GET['action']!=="add" && $agent->t_id !== $tenant->id) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Access denied"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
else {

	$header_class = $_GET['action']=="delete" ? "danger" : "success";

	// content
	$content = [];

	// disabled
	$disabled = $_GET['action']=="delete" ? "disabled" : "";

	// import form
	$content[] = "<form id='modal-form'>";
	$content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";
	$content[] = "<table class='table table-condensed table-borderless align-middle table-zone-management table-md'>";
	// name
	$content[] = "<tbody class='name'>";
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Agent name")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='name' value='".htmlspecialchars(@$agent->name, ENT_QUOTES, 'UTF-8')."' $disabled>";
	$content[] = "		<input type='hidden' class='form-control form-control-sm' name='t_id' value='".htmlspecialchars(@$tenant->id, ENT_QUOTES, 'UTF-8')."' $disabled>";
	$content[] = "		<input type='hidden' name='action' value='".htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8')."'>";
	if($user->admin !== "1" || $_GET['action']!=="add")
	$content[] = "		<input type='hidden' name='id' value='".htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8')."'>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// URL
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("URL")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='url' value='".htmlspecialchars(@$agent->url, ENT_QUOTES, 'UTF-8')."' $disabled>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	// description
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Description")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='comment' value='".htmlspecialchars(@$agent->comment, ENT_QUOTES, 'UTF-8')."' $disabled>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	$content[] = "</tbody>";

	$content[] = "</table>";
	$content[] = "</form>";

	#
	# button text
	#
	$btn_text = _(ucwords($_GET['action']))." "._("agent");
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/modals/agents/edit-submit.php", false, $header_class);

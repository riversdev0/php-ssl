<?php

#
# Edit port group
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

# fetch port group
if($_GET['action']!=="add")
$port_group = $Database->getObject ("ssl_port_groups",$_GET['id']);

#
# title
#
$title = _(ucwords($_GET['action']))." "._("port group");

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
# validate port group
elseif ($_GET['action']!=="add" && is_null($port_group)) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid port group"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
# validate port group belongs to tenant (IDOR)
elseif ($_GET['action']!=="add" && $port_group->t_id !== $tenant->id) {
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
	$content[] = "	<th style='width:100px;'>"._("Name")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='name' value='".htmlspecialchars(@$port_group->name, ENT_QUOTES, 'UTF-8')."' $disabled>";
	$content[] = "		<input type='hidden' class='form-control form-control-sm' name='t_id' value='".htmlspecialchars(@$tenant->id, ENT_QUOTES, 'UTF-8')."' $disabled>";
	$content[] = "		<input type='hidden' name='action' value='".htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8')."'>";
	if($user->admin !== "1" || $_GET['action']!=="add")
	$content[] = "		<input type='hidden' name='id' value='".htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8')."'>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// ports
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Ports")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='ports' value='".htmlspecialchars(@$port_group->ports, ENT_QUOTES, 'UTF-8')."' $disabled placeholder='443,8443,9443'>";
	$content[] = "		<span class='text-muted' style='font-size:11px'>Comma separated port numbers</span>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	$content[] = "</tbody>";

	$content[] = "</table>";
	$content[] = "</form>";

	#
	# button text
	#
	$btn_text = _(ucwords($_GET['action']))." "._("port group");
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/modals/portgroups/edit-submit.php", false, $header_class);

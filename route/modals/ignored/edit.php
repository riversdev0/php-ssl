<?php

#
# Edit issuer
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

# fetch issuer
if($_GET['action']!=="add")
$issuer = $Database->getObject ("ignored_issuers",$_GET['id']);

#
# title
#
$title = _(ucwords($_GET['action']))." "._("ignored issuer");

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
# validate issuer
elseif ($_GET['action']!=="add" && is_null($issuer)) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid issuer"), false, false, true);
	$header_class = "danger";

	# btn
	$btn_text = "";
}
# validate issuer belongs to tenant (IDOR)
elseif ($_GET['action']!=="add" && $issuer->t_id !== $tenant->id) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Access denied"), false, false, true);
	$header_class = "danger";

	# btn
	$btn_text = "";
}
else {
	// content
	$content = [];

	$header_class = $_GET['action']=="delete" ? "danger" : "success";

	// disabled
	$disabled = $_GET['action']=="delete" ? "disabled" : "";

	// import form
	$content[] = "<form id='modal-form'>";
	$content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";
	$content[] = "<table class='table table-condensed table-borderless align-middle table-zone-management table-sm'>";
	// tenant - admin
	if($user->admin === "1" && $_GET['action']=="add") {
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Tenant")."</th>";
	$content[] = "	<td>".$tenant->name."</td>";
	$content[] = "</tr>";
	}
	// name
	$content[] = "<tbody class='name'>";
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Name")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='name' value='".htmlspecialchars(@$issuer->name, ENT_QUOTES, 'UTF-8')."' $disabled>";
	$content[] = "		<input type='hidden' name='t_id' value='".htmlspecialchars(@$tenant->id, ENT_QUOTES, 'UTF-8')."'>";
	$content[] = "		<input type='hidden' name='action' value='".htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8')."'>";
	if($user->admin !== "1" || $_GET['action']!=="add")
	$content[] = "		<input type='hidden' name='id' value='".htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8')."'>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// SKI
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Key identifier")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='ski' value='".htmlspecialchars(@$issuer->ski, ENT_QUOTES, 'UTF-8')."' $disabled>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	$content[] = "</tbody>";

	$content[] = "</table>";
	$content[] = "</form>";

	#
	# button text
	#
	$btn_text = _(ucwords($_GET['action']))." "._("issuer");
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/modals/ignored/edit-submit.php", false, $header_class);

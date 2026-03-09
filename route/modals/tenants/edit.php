<?php

#
# Edit tenant
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (true, true);
# validate permissions
$User->validate_user_permissions (3, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# fetch tenant
if($_GET['action']!=="add")
$tenant = $Tenants->get_tenant_by_href ($_GET['id']);

#
# title
#
$title = _(ucwords($_GET['action']))." "._("tenant");

# validate action
if(!$User->validate_action($_GET['action'])) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid action"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
# admin
elseif($user->admin !== "1") {
	# content
	$content = [];
	$content[]    = $Result->show("danger", _("Admin user required"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
# validate tenant
elseif ($_GET['action']!=="add" && is_null($tenant)) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid tenant"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
else {
	// content
	$content = [];

	// disabled
	$disabled = $_GET['action']=="delete" ? "disabled" : "";

	// import form
	$content[] = "<form id='modal-form'>";
	$content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";
	$content[] = "<table class='table table-condensed table-sm table-borderless align-middle table-zone-management'>";
	// name
	$content[] = "<tbody class='name'>";
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Tenant name")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='name' value='".@$tenant->name."' $disabled>";
	$content[] = "		<input type='hidden' name='action' value='".$_GET['action']."'>";
	if($user->admin !== "1" || $_GET['action']!=="add")
	$content[] = "		<input type='hidden' name='id' value='".$_GET['id']."'>";
	if($_GET['action']=="delete")
	$content[] = "		<input type='hidden' id='target' name='target' value='/".$user->href."/tenants/'>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// href
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Href")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='href' value='".@$tenant->href."' $disabled>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	// active
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Active")."</th>";
	$content[] = "	<td>";
	$content[] = "<select name='active' class='form-select form-select-sm' style='width:auto' $disabled>";
	foreach([0=>"No", 1=>"Yes"] as $key=>$val) {
	$selected = $tenant->active == $key ? "selected" : "";
	$content[] =  "<option value='".$key."' $selected>".$val."</option>";
	}
	$content[] = "</select>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	// remove orphaned
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Remove orphaned")."</th>";
	$content[] = "	<td>";
	$content[] = "<select name='remove_orphaned' class='form-select form-select-sm' style='width:auto' $disabled>";
	foreach([0=>"No", 1=>"Yes"] as $key=>$val) {
	$selected = $tenant->remove_orphaned == $key ? "selected" : "";
	$content[] =  "<option value='".$key."' $selected>".$val."</option>";
	}
	$content[] = "</select>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	// mail style
	$content[] = "<tr>";
	$content[] = "	<th style='width:150px;'>"._("Mail style")."</th>";
	$content[] = "	<td>";
	$content[] = "<select name='mail_style' class='form-select form-select-sm' style='width:auto' $disabled>";
	foreach(["list", "table"] as $name) {
	$selected = $tenant->mail_style == $name ? "selected" : "";
	$content[] =  "<option value='$name' $selected>".$name."</option>";
	}
	$content[] = "</select>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	// recipients
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Recipients")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='recipients' value='".@$tenant->recipients."' $disabled>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// description
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Description")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='description' value='".@$tenant->description."' $disabled>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	$content[] = "</tbody>";

	$content[] = "</table>";
	$content[] = "</form>";

	#
	# button text
	#
	$btn_text = _(ucwords($_GET['action']))." "._("tenant");

	// header class
	if($_GET['action']=="add") 		  { $header_class = "success"; }
	elseif($_GET['action']=="delete") { $header_class = "danger"; }
	else 					  		  { $header_class = "info"; }
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/modals/tenants/edit-submit.php", false, $header_class);

<?php

#
# Edit user
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, false);
# validate permissions
$User->validate_user_permissions (3, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# tenant
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);

# fetch user for edit/delete
if($_GET['action']!=="add")
$edit_user = $Database->getObject ("users", $_GET['id']);

#
# title
#
$title = _u($_GET['action'])." "._("user");

# validate action
if(!$User->validate_action($_GET['action'])) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid action"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
# tenant access - non-admins can only manage their own tenant
elseif($user->admin !== "1" && (is_null($tenant) || $user->t_id !== $tenant->id)) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Access denied"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
# validate user exists
elseif ($_GET['action']!=="add" && is_null($edit_user)) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid user"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
# validate user belongs to tenant (IDOR)
elseif ($_GET['action']!=="add" && $edit_user->t_id !== $tenant->id) {
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
	$content[] = "<input type='hidden' name='action' value='".htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8')."'>";
	$content[] = "<input type='hidden' name='tenant' value='".htmlspecialchars($_GET['tenant'], ENT_QUOTES, 'UTF-8')."'>";
	if($_GET['action']!=="add")
	$content[] = "<input type='hidden' name='id' value='".htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8')."'>";
	if($_GET['action']=="delete") {
		$delete_target = isset($_GET['target']) ? $_GET['target'] : "/".$user->href."/users/";
		$content[] = "<input type='hidden' id='target' name='target' value='".htmlspecialchars($delete_target, ENT_QUOTES, 'UTF-8')."'>";
	}

	$content[] = "<table class='table table-condensed table-borderless table-zone-management table-sm table-td-top table-td-top-padded-0'>";
	$content[] = "<tbody>";

	// name
	$content[] = "<tr>";
	$content[] = "	<th>"._("Name")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='name' value='".htmlspecialchars(@$edit_user->name, ENT_QUOTES, 'UTF-8')."' $disabled>";
	$content[] = "	</td>";
	$content[] = "</tr>";

	// email
	$content[] = "<tr>";
	$content[] = "	<th>"._("Email")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='email' class='form-control form-control-sm' name='email' value='".htmlspecialchars(@$edit_user->email, ENT_QUOTES, 'UTF-8')."' $disabled>";
	$content[] = "	</td>";
	$content[] = "</tr>";

	// password (hidden on delete)
	if($_GET['action']!=="delete") {
	$pw_placeholder = $_GET['action']==="edit" ? _("Leave blank to keep current") : "";
	$content[] = "<tr>";
	$content[] = "	<th>"._("Password")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='password' class='form-control form-control-sm' name='password' placeholder='".htmlspecialchars($pw_placeholder, ENT_QUOTES, 'UTF-8')."' autocomplete='new-password'>";
	$content[] = "		<span class='text-muted' style='font-size:11px'>"._("Min 10 characters, must include uppercase, lowercase and a number")."</span>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	}

	// permission
	$content[] = "<tr>";
	$content[] = "	<th>"._("Permission")."</th>";
	$content[] = "	<td>";
	$content[] = "<select name='permission' class='form-select form-select-sm' style='width:auto' $disabled>";
	foreach([1 => "Read", 2 => "Write", 3 => "Admin"] as $perm => $label) {
		$selected = @$edit_user->permission == $perm ? "selected" : "";
		$content[] = "	<option value='$perm' $selected>$label</option>";
	}
	$content[] = "</select>";
	$content[] = "	</td>";
	$content[] = "</tr>";

	// days
	$content[] = "<tr>";
	$content[] = "	<th>"._("Days warning")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='number' class='form-control form-control-sm' name='days' value='".htmlspecialchars((string)(@$edit_user->days ?? 30), ENT_QUOTES, 'UTF-8')."' min='1' max='365' $disabled>";
	$content[] = "		<span class='text-muted' style='font-size:11px'>"._("Days before expiry to warn")."</span>";
	$content[] = "	</td>";
	$content[] = "</tr>";

	// days_expired
	$content[] = "<tr>";
	$content[] = "	<th>"._("Days expired")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='number' class='form-control form-control-sm' name='days_expired' value='".htmlspecialchars((string)(@$edit_user->days_expired ?? 30), ENT_QUOTES, 'UTF-8')."' min='1' max='365' $disabled>";
	$content[] = "		<span class='text-muted' style='font-size:11px'>"._("Days after expiry to still warn")."</span>";
	$content[] = "	</td>";
	$content[] = "</tr>";

	// changePass — only on edit, not add or delete
	if ($_GET['action'] === "edit") {
		$cp_checked = !empty($edit_user->changePass) ? "checked" : "";
		$content[] = "<tr>";
		$content[] = "	<th>"._("Force password change")."</th>";
		$content[] = "	<td>";
		$content[] = "		<label class='form-check'>";
		$content[] = "			<input type='checkbox' class='form-check-input' name='changePass' value='1' {$cp_checked}>";
		$content[] = "			<span class='form-check-label text-muted' style='font-size:11px'>"._("User must change password on next login")."</span>";
		$content[] = "		</label>";
		$content[] = "	</td>";
		$content[] = "</tr>";

		// disabled — only on edit
		$dis_checked = !empty($edit_user->disabled) ? "checked" : "";
		$content[] = "<tr>";
		$content[] = "	<th>"._("Disabled")."</th>";
		$content[] = "	<td>";
		$content[] = "		<label class='form-check'>";
		$content[] = "			<input type='checkbox' class='form-check-input' name='disabled' value='1' {$dis_checked}>";
		$content[] = "			<span class='form-check-label text-muted' style='font-size:11px'>"._("Account is disabled — user cannot log in")."</span>";
		$content[] = "		</label>";
		$content[] = "	</td>";
		$content[] = "</tr>";

		// language — only on edit; query translations from DB
		$all_langs = [];
		try {
			$all_langs = $Database->getObjectsQuery("SELECT id, name, native_name, locale_code FROM translations WHERE enabled = 1 ORDER BY name ASC", []);
		} catch (Exception $e) {}
		if (!empty($all_langs)) {
			$content[] = "<tr>";
			$content[] = "	<th>"._("Language")."</th>";
			$content[] = "	<td>";
			$content[] = "		<select name='lang_id' class='form-select form-select-sm' style='width:auto'>";
			$content[] = "			<option value=''>"._("— Tenant default —")."</option>";
			foreach ($all_langs as $lang) {
				$sel = (!empty($edit_user->lang_id) && $edit_user->lang_id == $lang->id) ? "selected" : "";
				$label = htmlspecialchars($lang->native_name . ' (' . $lang->locale_code . ')');
				$content[] = "			<option value='" . (int)$lang->id . "' {$sel}>{$label}</option>";
			}
			$content[] = "		</select>";
			$content[] = "		<span class='text-muted' style='font-size:11px'>"._("User interface language preference")."</span>";
			$content[] = "	</td>";
			$content[] = "</tr>";
		}
	}

	$content[] = "</tbody>";
	$content[] = "</table>";
	$content[] = "</form>";

	#
	# button text
	#
	$btn_text = _u($_GET['action'])." "._("user");
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/modals/users/edit-submit.php", false, $header_class);

<?php

#
# Edit domain
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

# fetch domain
if($_GET['action']!=="add")
$domain = $Database->getObject ("domains", $_GET['id']);

#
# title
#
$title = _u($_GET['action'])." "._("domain");

# validate action
if(!$User->validate_action($_GET['action'])) {
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid action"), false, false, true);
	$header_class = "danger";
	$btn_text = "";
}
# tenant validation
elseif($user->admin !== "1" && $user->t_id!=$tenant->id) {
	$content      = [];
	$content[]    = $Result->show("danger", _("Admin user required"), false, false, true);
	$header_class = "danger";
	$btn_text = "";
}
# validate domain
elseif ($_GET['action']!=="add" && is_null($domain)) {
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid domain"), false, false, true);
	$header_class = "danger";
	$btn_text = "";
}
# validate domain belongs to tenant (IDOR)
elseif ($_GET['action']!=="add" && $domain->t_id !== $tenant->id) {
	$content      = [];
	$content[]    = $Result->show("danger", _("Access denied"), false, false, true);
	$header_class = "danger";
	$btn_text = "";
}
else {

	$header_class = $_GET['action']=="delete" ? "danger" : "success";
	$content = [];
	$disabled = $_GET['action']=="delete" ? "disabled" : "";

	$content[] = "<form id='modal-form'>";
	$content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";
	$content[] = "<input type='hidden' name='action' value='".htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8')."'>";
	$content[] = "<input type='hidden' name='tenant' value='".htmlspecialchars($_GET['tenant'], ENT_QUOTES, 'UTF-8')."'>";
	if($_GET['action']!=="add")
	$content[] = "<input type='hidden' name='id' value='".htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8')."'>";

	$content[] = "<table class='table table-condensed table-borderless align-middle table-md'>";
	$content[] = "<tbody>";

	// Name
	$content[] = "<tr>";
	$content[] = "	<th style='width:150px;'>"._("Name")."</th>";
	$content[] = "	<td><input type='text' class='form-control form-control-sm' name='name' value='".htmlspecialchars(@$domain->name, ENT_QUOTES, 'UTF-8')."' $disabled></td>";
	$content[] = "</tr>";

	// Type
	$content[] = "<tr>";
	$content[] = "	<th>"._("Type")."</th>";
	$content[] = "	<td>";
	$content[] = "		<select class='form-select form-select-sm' name='type' $disabled>";
	if($_GET['action']!=="add")
	$content[] = "			<option value='local'".(@$domain->type==='local' ? ' selected' : '').">"._("Local")."</option>";
	$content[] = "			<option value='AD'".(@$domain->type==='AD' ? ' selected' : '').">"._("Active Directory (AD)")."</option>";
	$content[] = "		</select>";
	$content[] = "	</td>";
	$content[] = "</tr>";

	// Active
	$content[] = "<tr>";
	$content[] = "	<th>"._("Active")."</th>";
	$content[] = "	<td>";
	$content[] = "		<select class='form-select form-select-sm' name='active' $disabled>";
	$content[] = "			<option value='Yes'".(@$domain->active==='Yes' ? ' selected' : '').">"._("Yes")."</option>";
	$content[] = "			<option value='No'".(@$domain->active==='No' || $_GET['action']==='add' ? ' selected' : '').">"._("No")."</option>";
	$content[] = "		</select>";
	$content[] = "	</td>";
	$content[] = "</tr>";

	// Port
	$content[] = "<tr>";
	$content[] = "	<th>"._("Port")."</th>";
	$content[] = "	<td><input type='number' class='form-control form-control-sm' name='port' min='1' max='65535' value='".htmlspecialchars((string)(@$domain->port ?: 389), ENT_QUOTES, 'UTF-8')."' $disabled></td>";
	$content[] = "</tr>";

	// SSL/TLS
	$content[] = "<tr>";
	$content[] = "	<th>"._("Use SSL")."</th>";
	$content[] = "	<td><input type='checkbox' class='form-check-input' name='use_ssl' value='1'".(@$domain->use_ssl ? ' checked' : '')." $disabled></td>";
	$content[] = "</tr>";
	$content[] = "<tr>";
	$content[] = "	<th>"._("Use TLS")."</th>";
	$content[] = "	<td><input type='checkbox' class='form-check-input' name='use_tls' value='1'".(@$domain->use_tls ? ' checked' : '')." $disabled></td>";
	$content[] = "</tr>";

	// AD-specific fields (shown always, label indicates AD)
	$content[] = "<tr><td colspan=2><hr style='margin:5px 0'><small class='text-muted'>"._("Active Directory settings")."</small></td></tr>";

	// Account suffix
	$content[] = "<tr>";
	$content[] = "	<th>"._("Account suffix")."</th>";
	$content[] = "	<td><input type='text' class='form-control form-control-sm' name='account_suffix' placeholder='@domain.local' value='".htmlspecialchars(@$domain->account_suffix, ENT_QUOTES, 'UTF-8')."' $disabled></td>";
	$content[] = "</tr>";

	// Base DN
	$content[] = "<tr>";
	$content[] = "	<th>"._("Base DN")."</th>";
	$content[] = "	<td><input type='text' class='form-control form-control-sm' name='base_dn' placeholder='CN=Users,DC=domain,DC=local' value='".htmlspecialchars(@$domain->base_dn, ENT_QUOTES, 'UTF-8')."' $disabled></td>";
	$content[] = "</tr>";

	// Domain controllers
	$content[] = "<tr>";
	$content[] = "	<th>"._("Domain controllers")."</th>";
	$content[] = "	<td><input type='text' class='form-control form-control-sm' name='domain_controllers' placeholder='dc1.domain.local;dc2.domain.local' value='".htmlspecialchars(@$domain->domain_controllers, ENT_QUOTES, 'UTF-8')."' $disabled></td>";
	$content[] = "</tr>";

	// Autocreate group
	$content[] = "<tr>";
	$content[] = "	<th>"._("Autocreate group")."</th>";
	$content[] = "	<td><input type='text' class='form-control form-control-sm' name='autocreateGroup' value='".htmlspecialchars(@$domain->autocreateGroup, ENT_QUOTES, 'UTF-8')."' $disabled></td>";
	$content[] = "</tr>";

	// Admin credentials - only for admins
	if($user->admin === "1") {
		$content[] = "<tr><td colspan=2><hr style='margin:5px 0'><small class='text-muted'>"._("Admin credentials (admin only)")."</small></td></tr>";
		$content[] = "<tr>";
		$content[] = "	<th>"._("Admin username")."</th>";
		$content[] = "	<td><input type='text' class='form-control form-control-sm' name='adminUsername' autocomplete='off' value='".htmlspecialchars(@$domain->adminUsername, ENT_QUOTES, 'UTF-8')."' $disabled></td>";
		$content[] = "</tr>";
		$content[] = "<tr>";
		$content[] = "	<th>"._("Admin password")."</th>";
		$content[] = "	<td><input type='password' class='form-control form-control-sm' name='adminPassword' autocomplete='new-password' placeholder='".(_($_GET['action']==='edit' ? 'Leave blank to keep current' : ''))."' $disabled></td>";
		$content[] = "</tr>";
	}

	$content[] = "</tbody>";
	$content[] = "</table>";
	$content[] = "</form>";

	$btn_text = _u($_GET['action'])." "._("domain");
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/modals/domains/edit-submit.php", false, $header_class);

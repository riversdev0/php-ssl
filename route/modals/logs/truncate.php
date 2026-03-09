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
$tenant = $Tenants->get_tenant_by_href ($_GET['href']);
$all_tenants =

#
# title
#
$title = _("Truncate logs");



# validate tenant
if ($_GET['action']!=="add" && is_null($tenant)) {
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

	$all_tenants = $Tenants->get_all () ;

	// select tenants
	if($user->admin === "1") {
		$content[] = _('Select tenants for which you want to truncate logs:')."<br><br>";
		$content[] = "<form id='modal-form'>";
		$content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";
		// loop


		foreach ($Log->get_all_log_tenants () as $t) {
			// name
			$tname = isset($all_tenants[$t->tid]) ? $all_tenants[$t->tid]->name : _("Deleted tenant id")." ".$t->tid;

			$content[] = '<label class="form-check">';
			$content[] = '	<input class="form-check-input" type="checkbox" name="tenant-'.$t->tid.'">';
			$content[] = '	<span class="form-check-label">'.$tname.'</span>';
			$content[] = '</label>';
		}

		$content[] = "</tbody>";

		$content[] = "</table>";
		$content[] = "</form>";
	}
	//text only
	else {
		$content[] = _('Click Truncate to remove all logs').".";
	}


	#
	# button text
	#
	$btn_text = "Truncate";
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/modals/logs/truncate-submit.php", false, "info");

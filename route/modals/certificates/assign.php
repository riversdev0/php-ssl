<?php

#
# Assign certificate to host
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, false);
# validate permissions
$User->validate_user_permissions (2, true);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant (true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

$title = _("Assign certificate");

# validate host_id
if(!$Common->validate_int($_GET['host_id'])) {
	$content   = [$Result->show("danger", _("Invalid host"), false, false, true)];
	$btn_text  = "";
}
else {
	$host   = $Database->getObject("hosts", $_GET['host_id']);
	$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);

	if(is_null($host) || is_null($tenant)) {
		$content  = [$Result->show("danger", _("Invalid host or tenant"), false, false, true)];
		$btn_text = "";
	}
	else {
		# fetch all certificates for this tenant, ordered by expiry desc
		$certs = $Database->getObjectsQuery(
			"SELECT c.id, c.serial, c.expires, c.is_manual, z.name as zone_name FROM certificates c JOIN zones z ON c.z_id = z.id WHERE c.t_id = :t_id ORDER BY c.expires DESC",
			['t_id' => $tenant->id]
		);

		$content   = [];
		$content[] = "<form id='modal-form'>";
		$content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";
		$content[] = "<input type='hidden' name='tenant' value='" . htmlspecialchars($_GET['tenant']) . "'>";
		$content[] = "<input type='hidden' name='host_id' value='" . $host->id . "'>";

		$content[] = "<table class='table table-condensed table-borderless align-middle table-sm'>";

		$content[] = "<tr>";
		$content[] = "	<th style='width:120px;'>" . _("Host") . "</th>";
		$content[] = "	<td><b>" . htmlspecialchars($host->hostname) . "</b></td>";
		$content[] = "</tr>";

		$content[] = "<tr>";
		$content[] = "	<th>" . _("Certificate") . "</th>";
		$content[] = "	<td>";

		if(sizeof($certs) == 0) {
			$content[] = "<span class='text-muted'>" . _("No certificates available for this tenant.") . "</span>";
			$btn_text  = "";
		}
		else {
			$content[] = "<select name='certificate_id' class='form-select form-select-sm'>";
			foreach($certs as $c) {
				$expires_fmt  = $c->expires ? date("Y-m-d", strtotime($c->expires)) : "/";
				$manual_label = $c->is_manual == 1 ? " [manual]" : "";
				$content[]    = "<option value='" . $c->id . "'>[" . htmlspecialchars($c->zone_name) . "] " . htmlspecialchars($c->serial) . " &mdash; exp: " . $expires_fmt . $manual_label . "</option>";
			}
			$content[] = "</select>";
			$btn_text  = _("Assign certificate");
		}

		$content[] = "	</td>";
		$content[] = "</tr>";
		$content[] = "</table>";
		$content[] = "</form>";
	}
}

# print modal
$Modal->modal_print ($title, implode("\n", $content), isset($btn_text) ? $btn_text : "", "/route/modals/certificates/assign-submit.php", false, "success");

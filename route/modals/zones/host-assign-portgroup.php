<?php

#
# Assign portgroup
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

# fetch zone
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);
$zone   = $Zones->get_zone ($_GET['tenant'], $_GET['zone_id']);
$host   = $Zones->get_host ($_GET['host_id']);

# fetch port groups for tenant
$all_port_groups = $SSL->get_all_port_groups ();
$port_groups = isset($all_port_groups[$zone->t_id]) ? $all_port_groups[$zone->t_id] : [];

#
# title
#
$title = _("Assign portgroup");

# invalid zone
if ($zone===null) {
	$content = [];
	$content[] = $Result->show("danger", _("Invalid zone"), false, false, true);
	$btn_text = "";
}
# invalid host
elseif ($host===null || $host->z_id!=$zone->id) {
	$content = [];
	$content[] = $Result->show("danger", _("Invalid host"), false, false, true);
	$btn_text = "";
}
else {
	$content = [];

	$content[] = "<form id='modal-form'>";
	$content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";
	$content[] = "<table class='table table-condensed table-borderless align-middle'>";
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Port group")."</th>";
	$content[] = "	<td>";
	$content[] = "	<select class='form-select form-select-sm' name='pg_id'>";
	$content[] = "	<option value='' disabled selected>-- "._("Select port group")." --</option>";
	
	foreach($port_groups as $pg_id => $pg) {
		$selected = $host->pg_id == $pg_id ? "selected" : "";
		$content[] = "		<option value='$pg_id' $selected>".$pg['name']." (".implode(", ", $pg['ports']).")</option>";
	}
	
	$content[] = "	</select>";
	$content[] = "	<input type='hidden' name='tenant' value='".$_GET['tenant']."'>";
	$content[] = "	<input type='hidden' name='host_id' value='$_GET[host_id]'>";
	$content[] = "	<input type='hidden' name='zone_id' value='{$zone->id}'>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	$content[] = "</table>";
	$content[] = "</form>";

	$btn_text = _("Assign portgroup");
}

# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/modals/zones/host-assign-portgroup-submit.php", false, "success");

?>

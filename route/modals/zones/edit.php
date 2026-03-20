<?php

#
# Edit zone
#


# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, false);
# validate permissions
$User->validate_user_permissions (3, true);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant (true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# fetch zone
$zone   = $Zones->get_zone ($_GET['tenant'], $_GET['zone_name']);
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);
$agents = $Zones->get_tenant_agents ($tenant->id);


# axrf visible ?
$axfr_visible = @$zone->type=="axfr" ? "" : "d-none";

#
# title
#
$title = _(ucwords($_GET['action']))." "._("zone");

# validate action
if(!$User->validate_action($_GET['action'])) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid action"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
else {
	// content
	$content = [];

	// disabled
	$disabled = $_GET['action']=="delete" ? "disabled" : "";

	$header_class = $_GET['action']=="delete" ? "danger" : "success";


	// import form
	$content[] = "<form id='modal-form'>";
	$content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";
	$content[] = "<table class='table table-condensed table-borderless align-middle table-sm table-zone-management'>";
	// tenant - admin
	if($user->admin === "1" && $_GET['action']=="add") {
		$content[] = "<tr>";
		$content[] = "	<th style='width:100px;'>"._("Tenant")."</th>";
		$content[] = "	<td>";
		foreach ($Tenants->get_all () as $t) {
			if($zone->t_id == $t->id || $t->href==$_GET['tenant']) {
				$content[] = "<input name='tenant' class='form-select' type='hidden' value='".$t->id."'>";
				$content[] = $t->name;
			}
		}
		$content[] = "	</td>";
		$content[] = "</tr>";
	}
	// name
	$content[] = "<tbody class='name'>";
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Zone name")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='name' value='".@$zone->name."' $disabled>";
	$content[] = "		<input type='hidden' name='action' value='".$_GET['action']."'>";
	if($user->admin !== "1" || $_GET['action']!=="add")
	$content[] = "		<input type='hidden' name='tenant' value='".$_GET['tenant']."'>";
	if($_GET['action']=="delete")
	$content[] = "		<input type='hidden' id='target' name='target' value='/".$user->href."/zones/'>";
	$content[] = "		<input type='hidden' name='zone_id' value='{$zone->id}'>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// type
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Type")."</th>";
	$content[] = "	<td>";
	$content[] = "<select name='type' class='form-select form-select-sm' style='width:auto' $disabled>";
	foreach(["local"=>"Local zone", "axfr"=>"DNS zone transfer"] as $type=>$name) {
	$selected = $zone->type == $type ? "selected" : "";
	$content[] =  "<option value='$type' $selected>".$name."</option>";
	}
	$content[] = "</select>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	// agent
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Scan agent")."</th>";
	$content[] = "	<td>";
	$content[] = "<select name='agent_id' class='form-select form-select-sm' style='width:auto' $disabled>";
	foreach($agents as $a) {
	$selected = $zone->agent_id == $a->id ? "selected" : "";
	$content[] =  "<option value='".$a->id."' $selected>".$a->name."</option>";
	}
	$content[] = "</select>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	// ignore
	$content[] = "<tr>";
	$content[] = "	<th style='width:150px;'>"._("Exclude from scan")."</th>";
	$content[] = "	<td>";
	$content[] = "<select name='ignore' class='form-select form-select-sm' style='width:auto' $disabled>";
	foreach(["0"=>"No", "1"=>"Yes"] as $type=>$name) {
	$selected = $zone->type == $type ? "selected" : "";
	$content[] =  "<option value='$type' $selected>".$name."</option>";
	}
	$content[] = "</select>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	// is_domain
	$content[] = "<tr>";
	$content[] = "	<th style='width:150px;'>"._("Is domain")."</th>";
	$content[] = "	<td>";
	$content[] = "<select name='is_domain' class='form-select form-select-sm' style='width:auto' $disabled>";
	foreach(["0"=>"No", "1"=>"Yes"] as $type=>$name) {
	$selected = $zone->is_domain == $type || ($_GET['action']=="add" && $type=="1") ? "selected" : "";
	$content[] =  "<option value='$type' $selected>".$name."</option>";
	}
	$content[] = "</select>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	// description
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Description")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='description' value='".@$zone->z_description."' $disabled>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// private zone - checkbox on add, info label on edit
	if ($_GET['action'] == "add") {
		$content[] = "<tr>";
		$content[] = "	<th style='width:100px;'>"._("Private zone")."</th>";
		$content[] = "	<td>";
		$content[] = "		<label class='form-check'>";
		$content[] = "			<input type='checkbox' name='private_zone' class='form-check-input' value='1'>";
		$content[] = "			<span class='form-check-label text-muted'>"._("Only visible to you — not even admins can see it")."</span>";
		$content[] = "		</label>";
		$content[] = "	</td>";
		$content[] = "	<td>";
		$content[] = "</tr>";
	}
	elseif (!empty($zone->private_zone_uid)) {
		$content[] = "<tr>";
		$content[] = "	<th style='width:100px;'>"._("Private zone")."</th>";
		$content[] = "	<td><span class='badge text-purple'>"._("Private")."</span> <span class='text-muted small'>"._("Only visible to you")."</span></td>";
		$content[] = "	<td>";
		$content[] = "</tr>";
	}
	$content[] = "</tbody>";

	//
	// axfr
	//
	$content[] = "<tbody id='axfr' class='axfr $axfr_visible'>";
	$content[] = "<tr><td colspan='2'><h4>"._("AXFR parameters")."</h3></td></tr>";
	// dns
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("DNS server")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='dns' value='".@$zone->dns."' $disabled>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// aname
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Zone name")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='aname' value='".@$zone->aname."' $disabled>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// tsig
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("TSIG name")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='tsig_name' value='".@$zone->tsig_name."' $disabled>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// tsig
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("TSIG")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='tsig' value='".@$zone->tsig."' $disabled>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// types
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Record types")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='record_types' value='".@$zone->record_types."' $disabled>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// delete
	$content[] = "<tr>";
	$content[] = "	<th style='width:150px;'>"._("Delete records")."</th>";
	$content[] = "	<td>";
	$content[] = "<select name='delete_records' class='form-select form-select-sm' style='width:auto' $disabled>";
	foreach(["0"=>"No", "1"=>"Yes"] as $type=>$name) {
	$selected = $zone->delete_records == $type ? "selected" : "";
	$content[] =  "<option value='$type' $selected>".$name."</option>";
	}
	$content[] = "</select>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	// ip
	$content[] = "<tr>";
	$content[] = "	<th style='width:150px;'>"._("Add IP addresses")."</th>";
	$content[] = "	<td>";
	$content[] = "<select name='check_ip' class='form-select form-select-sm' style='width:auto' $disabled>";
	foreach(["0"=>"No", "1"=>"Yes"] as $type=>$name) {
	$selected = $zone->check_ip == $type ? "selected" : "";
	$content[] =  "<option value='$type' $selected>".$name."</option>";
	}
	$content[] = "</select>";
	$content[] = "	</td>";
	$content[] = "</tr>";
	// regex - include
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Included patterns")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='regex_include' value='".@$zone->regex_include."' $disabled>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// regex - exclude
	$content[] = "<tr>";
	$content[] = "	<th style='width:100px;'>"._("Excluded patterns")."</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='regex_exclude' value='".@$zone->regex_exclude."' $disabled>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";
	// test axfr transfer
	$content[] = "<tr>";
	$content[] = "	<th></th>";
	$content[] = "	<td>";
	$content[] = "		<a href='/route/modals/zones/axfr-test.php' class='btn btn-sm btn-outline-success float-end' id='axfr-regex-test' data-bs-toggle='modal' data-bs-target='#modal2'>Test transfer</a>";
	$content[] = "	</td>";
	$content[] = "	<td>";
	$content[] = "</tr>";

	$content[] = "</tbody>";

	$content[] = "</table>";
	$content[] = "</form>";

	#
	# button text
	#
	$btn_text = _(ucwords($_GET['action']))." "._("zone");
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/modals/zones/edit-submit.php", false, $header_class);

?>


<script type="text/javascript">
$(document).ready(function() {
	// show/hide axfr
	$('select[name=type]').change(function () {
		$('tbody#axfr').toggleClass('d-none');
	})
	// execute test
	$('#axfr-regex-test').click(function() {
		// add get parameters
		var _get = $('form#modal-form').serialize();
		// append
		$(this).attr('href', $(this).attr('href')+"?form="+btoa(_get))
	})
})
</script>
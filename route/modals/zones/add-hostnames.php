<?php

#
# Edit host
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
$zone = $Zones->get_zone ($_GET['tenant'], $_GET['zone_name']);
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);

#
# title
#
$title = _(ucwords($_GET['action']))." "._("host");

# validate action
if(!$User->validate_action($_GET['action'])) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid action"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
# invalid zone
elseif ($zone===null) {
	# content
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid zone"), false, false, true);
	$header_class = "danger";
	# btn
	$btn_text = "";
}
else {
	// reset title
	$title = _(ucwords($_GET['action']))." "._("host to zone")." ".$zone->name;
	// content
	$content = [];

	// ports
	$ports = $SSL->get_all_port_groups ();

	// disabled
	$disabled = $_GET['action']=="delete" ? "disabled" : "";

	// import form
	$content[] = "<form id='modal-form'>";
	$content[] = "<table class='table table-condensed table-borderless align-middle'>";
	// headers
	$content[] = "<tr>";
	$content[] = "	<th>"._("Hostname")."</th>";
	if($zone->is_domain=="1")
	$content[] = "	<th style='padding-left:0px'>"._("Domain")."</th>";
	$content[] = "	<th>"._("Ports")."</th>";
	$content[] = "	<td></td>";
	$content[] = "</tr>";
	// name
	$content[] = "<tr>";
	$content[] = "	<td>";
	$content[] = "		<input type='text' class='form-control form-control-sm' name='hostname-1'>";
	$content[] = "		<input type='hidden' name='action' value='".$_GET['action']."'>";
	$content[] = "		<input type='hidden' name='tenant' value='".$_GET['tenant']."'>";
	$content[] = "		<input type='hidden' name='id' value='$_GET[host_id]'>";
	$content[] = "		<input type='hidden' name='zone_id' value='{$zone->id}'>";
	$content[] = "	</td>";
	if($zone->is_domain=="1")
	$content[] = "	<td style='padding-left:0px'>.".$zone->name."</td>";
	$content[] = "	<td>";
	$content[] = "<select name='pg-1' class='form-select form-select-sm'>";
	foreach($ports[$tenant->id] as $id=>$p) {
	$content[] =  "<option value='$id'>".$p['name']."</option>";
	}
	$content[] = "</select>";
	$content[] = "	</td>";
	$content[] = "	<td></td>";
	$content[] = "</tr>";

	$content[] = "</table>";
	$content[] = "</form>";
	$content[] = "<hr>";
	$content[] = "<btn class='btn btn-sm btn-default btn-outline-success' id='add_hosts' data-isdomain='".$zone->is_domain."' data-domain='".$zone->name."'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg> '._("Add more")."</btn>";

	$content[] = "<div class='visually-hidden' id='hostcount'>1</div>";

	#
	# button text
	#
	$btn_text = _(ucwords($_GET['action']))." "._("hosts");

	# header
	$header_class = "success";
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/modals/zones/add-hostnames-submit.php", false, $header_class);

?>


<script type="text/javascript">
$(document).ready(function() {
	// add hosts
	$('#add_hosts').click(function() {
		// add count
		var current = $('#hostcount').html();
		current++;
		$('#hostcount').html(current);
		// domain (1 0r 0)
		var isdomain = $(this).attr('data-isdomain');
		var domain 	 = $(this).attr('data-domain');

		// template
		var append = "";
		append += "<tr>";
		append += "<td><input type='text' class='form-control form-control-sm' name='hostname-"+current+"'></td>";
		// domain ?
		if(isdomain==1)
		append += "<td style='padding-left:0px'>."+domain+"</td>";
		append += "<td><select name='pg-"+current+"' class='form-select form-select-sm'>";
		<?php foreach ($ports[$tenant->id] as $id=>$p) { ?>
		append += "<option value='<?php print $id; ?>'><?php print $p['name']; ?></option>";
		<?php } ?>
		append += "</td>";
		append += '<td style="padding:0px;"><a class="btn btn-sm btn-danger remove_host"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"> style="margin-right:0px"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></a></td>';

		// append to form
		$('form#modal-form table').append(append)
	})
	// remove host
	$(document).on("click", '.remove_host', function () {
		$(this).parent().parent().remove();
	})
})
</script>
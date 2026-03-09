<?php

#
# Import hosts - textarea form
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
$zone   = $Zones->get_zone ($_GET['tenant'], $_GET['zone_name']);
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);

#
# title
#
$title = _("Import hosts");

# invalid zone
if ($zone === null) {
	$content      = [];
	$content[]    = $Result->show("danger", _("Invalid zone"), false, false, true);
	$header_class = "danger";
	$btn_text     = "";
}
else {
	$content = [];

	$content[] = "<p class='text-muted'>"._("Enter one hostname per line to import into zone")." <strong>".htmlspecialchars($zone->name)."</strong>.</p>";
	$content[] = "<form id='modal-form'>";
	$content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";
	$content[] = "<textarea class='form-control' id='import-hostnames' name='hostnames' rows='10' placeholder='hostname1&#10;hostname2&#10;hostname3' style='font-family:monospace;font-size:13px;'></textarea>";
	$content[] = "<input type='hidden' id='import-tenant' value='".htmlspecialchars($_GET['tenant'])."'>";
	$content[] = "<input type='hidden' id='import-zone-name' value='".htmlspecialchars($_GET['zone_name'])."'>";
	$content[] = "</form>";

	$btn_text     = _("Import hosts");
	$header_class = "success";
}

# print modal — empty action_script so no standard POST handler is generated; custom JS below handles .modal-execute
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "", false, $header_class);

?>


<script type="text/javascript">
$(document).ready(function() {
	$('.modal-execute').click(function() {
		var raw = $('#import-hostnames').val();
		var hostnames = raw.split('\n').map(function(h) { return h.trim(); }).filter(function(h) { return h.length > 0; });

		if (hostnames.length === 0) {
			$('.modal-result').html('<div class="alert alert-warning"><?php print _("Please enter at least one hostname."); ?></div>').fadeIn('fast');
			return false;
		}

		var tenant   = $('#import-tenant').val();
		var zoneName = $('#import-zone-name').val();
		var url      = '/route/modals/zones/add-hostnames.php?action=add&tenant=' + encodeURIComponent(tenant) + '&zone_name=' + encodeURIComponent(zoneName);

		// Load add-hostnames modal, then pre-fill each hostname.
		// We replicate the row-adding logic directly instead of triggering
		// #add_hosts click, because $(document).ready() in the loaded content
		// may not have bound that handler yet when the $.load() callback runs.
		$('#modal1 .modal-content').load(url, function() {
			// Fill the first hostname (row already rendered by add-hostnames.php)
			$('input[name="hostname-1"]').val(hostnames[0]);

			if (hostnames.length === 1) return;

			var isdomain    = $('#add_hosts').attr('data-isdomain');
			var domain      = $('#add_hosts').attr('data-domain');
			var portOptions = $('select[name="pg-1"]').html();
			var trashIcon   = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';

			for (var i = 1; i < hostnames.length; i++) {
				var n = i + 1;
				$('#hostcount').html(n);

				var row = '<tr>';
				row += '<td><input type="text" class="form-control form-control-sm" name="hostname-' + n + '"></td>';
				if (isdomain == 1) {
					row += '<td style="padding-left:0px">.' + domain + '</td>';
				}
				row += '<td><select name="pg-' + n + '" class="form-select form-select-sm">' + portOptions + '</select></td>';
				row += '<td style="padding:0px;"><a class="btn btn-sm btn-danger remove_host">' + trashIcon + '</a></td>';
				row += '</tr>';

				$('form#modal-form table').append(row);
				$('input[name="hostname-' + n + '"]').val(hostnames[i]);
			}
		});

		return false;
	});
});
</script>

<?php

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session ();
# validate permissions
$User->validate_user_permissions (3, true);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant (true);

# base64 > string, urldecode and strip tags, save to $dns_params
parse_str($User->strip_input_tags (urldecode(base64_decode($_GET['form']))), $dns_params);

// init class
$AXFR = new AXFR ($Database);
// set dns, tcp and tsig parameters
$AXFR->set_nameservers (explode(",",$dns_params['dns']));					// set anmeservers to query
$AXFR->set_tsig ($dns_params['tsig_name'], $dns_params['tsig']);			// set tsig parameters
$AXFR->set_zone_name ($dns_params['aname']);								// set zone name to query
$AXFR->set_valid_types (explode(",",$dns_params['record_types']));			// set valid dns record types
$AXFR->set_regexes ($dns_params['regex_include'], $dns_params['regex_exclude']);	// set regexes

// execute
$AXFR->execute();

// get result
$results = $AXFR->get_records ();

// bootstrap table
print '<link href="https://unpkg.com/bootstrap-table@1.19.1/dist/bootstrap-table.min.css" rel="stylesheet">';
print '<script src="https://unpkg.com/bootstrap-table@1.19.1/dist/bootstrap-table.min.js"></script>';

// error ?
if($results['success']==false) {
	$title        = "Error";
	$content[]    = "<div class='alert alert-danger'>".$results['error']."</div>";
	$header_class = "danger";
}
else {
	# title
	$title = _("AXFR test");

	// calculate differences [create, remove, new etc]
	$AXFR->calculate_diffs ($dns_params['zone_id'], $dns_params['check_ip']);

	// table
	$content[] = "<table class='table table-hover align-top table-sm' data-toggle='table' data-mobile-responsive='true' data-check-on-init='true' data-classes='table table-hover table-sm' data-cookie='false' data-cookie-id-table='axfr' data-pagination='true' data-page-size='25' data-page-list='[25,50,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";

	// headers
	$content[] = "<thead>";
	$content[] = "<tr>";
	$content[] = "	<th data-field='type' data-width='30'>"._("Type")."</th>";
	$content[] = "	<th data-field='action' data-width='30'>"._("Action")."</th>";
	$content[] = "	<th data-field='name'>"._("Name")."</th>";
	$content[] = "	<th data-field='address'>"._("Address")."</th>";
	$content[] = "	<th data-field='ttl'>"._("TTL")."</th>";
	$content[] = "</tr>";
	$content[] = "</thead>";

	// data
	$content[] = "<tbody>";
	if (sizeof($results['values'])>0) {
		foreach ($results['values'] as $v) {
			// action
			if(in_array($v->name, $AXFR->records['removed_records'])) 		{ $action = "<span class='badge bg-red-lt'>"._("Remove")."</span>"; }
			elseif(in_array($v->name, $AXFR->records['new_records'])) 		{ $action = "<span class='badge bg-green-lt'>"._("Create")."</span>"; }
			else 															{ $action = "<span class='badge bg-azure-lt'>"._("Existing")."</span>"; }

			// content
			$content[] = "<tr>";
			$content[] = "	<td class='align-top'><span class='badge badge-outline text-light' style='margin-right: 20px;'>".$v->type."</span></td>";
			$content[] = "	<td class='align-top'>".$action."</td>";
			$content[] = "	<td class='align-top'>".$v->name."</td>";
			$content[] = "	<td class='align-top'>".$v->address."</td>";
			$content[] = "	<td class='align-top text-secondary'>".$v->ttl."</td>";
			$content[] = "</tr>";
		}
	}
	$content[] = "</tbody>";
	$content[] = "</table>";

	// info
	$content[] = "<div class='alert alert-block alert-info' style='margin-top: 30px;'>";
	$content[] = _("Used filters:");
	$content[] = "<ul>";
	$content[] = "	<li>"._("Record types:")."  ".$dns_params['record_types']."</li>";
	$content[] = "	<li>"._("Include regex:")." ".$dns_params['regex_include']."</li>";
	$content[] = "	<li>"._("Exclude regex:")." ".$dns_params['regex_exclude']."</li>";
	$content[] = "</ul>";
	$content[] = "</div>";

	$header_class = "info";
}

# print modal
$Modal->modal_id = "#modal2";
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "", false, $header_class);
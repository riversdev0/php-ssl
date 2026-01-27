<?php

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, false);
# validate permissions
$User->validate_user_permissions (3, true);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant (true);

# fetch zone details
$zone = $Zones->get_zone ($_params['tenant'], $_GET['zone_name']);

// init class
$AXFR = new AXFR ($Database);
// set dns, tcp and tsig parameters
$AXFR->set_nameservers (explode(",",$zone->dns));					// set anmeservers to query
$AXFR->set_tsig ($zone->tsig_name, $zone->tsig);					// set tsig parameters
$AXFR->set_zone_name ($zone->aname);								// set zone name to query
$AXFR->set_valid_types (explode(",", $zone->record_types));			// set valid dns record types
$AXFR->set_regexes ($zone->regex_include, $zone->regex_exclude);	// set regexes

// execute
$AXFR->execute();

// get result
$results = $AXFR->get_records ();

// error ?
if($results['success']==false) {
	$content[] = "<div class='alert alert-danger'>".$results['error']."</div>";
}
else {
	// calculate differences [create, remove, new etc]
	$AXFR->calculate_diffs ($zone->id, $zone->check_ip);

	// add records
	$AXFR->create_new_records ();

	// remove records not in DNS AXFR
	if ($zone->delete_records=="1") {
		$AXFR->delete_records ();
	}
	else {
		$AXFR->records['removed_records'] = [];
	}
}

# title
$title = _("AXFR zone sync");

# content
$content_text = [
	"Discovered records" => "<span class='badge badge-outline text-light' style='width:100%'>".sizeof($AXFR->records['axfr_records'])."</span>",
	"Existing records"   => "<span class='badge badge-outline text-info' style='width:100%'>".sizeof($AXFR->records['old_records'])."</span>",
	"Removed records"    => "<span class='badge badge-outline text-danger' style='width:100%'>".sizeof($AXFR->records['removed_records'])."</span>",
	"Created records"    => "<span class='badge badge-outline text-success' style='width:100%'>".sizeof($AXFR->records['new_records'])."</span>",

];

$content = [];
$content[] = "<div class='text-secondary' style='margin-bottom:10px'>"._("Zone AXFR sync results").":</div>";
foreach ($content_text as $title2=>$text)  {
	$content[] = '<div class="row" style="margin-bottom:5px;">';
	$content[] = '	<div class="col-1">'.$text.'</div>';
	$content[] = '	<div class="col-11">'._($title2).'</div>';
	$content[] = '</div>';
}

# print modal
$Modal->modal_id = "#modal1";
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "", true);


// Write log :: object, object_id, tenant_id, user_id, action, public, text
$Log->write ("zones", $zone->id, $zone->t_id, $user->id, "sync", true, "Zone AXFR sync executed");
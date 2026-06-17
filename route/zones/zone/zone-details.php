<?php

// validate user session
$User->validate_session ();

// set desciription
$zone->description = $zone->description=="" ? "/" : $zone->description;

// div
print "<div class='row'>";



//
// Details
//
print "	<div class='col-xs-12 col-sm-12 col-md-8 col-lg-6' style='margin-top:10px;'>";
print "	<div class='card'>";

print " <div class='card-header'>".$zone->name."</div>";

// table
print "<div>";
print "<table class='table table-borderless table-md table-hov1er table-zones-details'>";

print "<tr>";
print "	<th style='min-width:180px;width:220px;'>"._("Zone name")."</th>";
print "	<td><b>".$zone->name."</b></td>";
print "</tr>";

// private zone notice — only shown to the owner (who is the only one who can reach this page)
if (!empty($zone->private_zone_uid)) {
	print "<tr>";
	print "	<th>"._("Visibility")."</th>";
	print "	<td><span class='badge text-purple'><svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon me-1'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M3 3l18 18' /><path d='M10.584 10.587a2 2 0 0 0 2.828 2.829' /><path d='M9.363 5.365a9.466 9.466 0 0 1 2.637 -.365c4 0 7.333 2.333 10 7c-.778 1.361 -1.612 2.524 -2.503 3.488m-1.536 1.531c-1.671 1.326 -3.532 1.981 -5.961 1.981c-4 0 -7.333 -2.333 -10 -7c1.369 -2.395 2.913 -4.175 4.632 -5.341' /></svg> "._("Private zone")."</span> <span class='text-muted small'>"._("Only visible to you")."</span></td>";
	print "</tr>";
}

// tenants
if($user->admin=="1") {
	$tenant = $Database->getObject("tenants", $zone->t_id);
	print "<tr>";
	print "	<th style='min-width:180px;'>"._("Tenant")."</th>";
	print "	<td><b>".$tenant->name."</b></td>";
	print "</tr>";
}

print "<tr>";
print "	<th>"._("Type")."</th>";
print "	<td><span class='badge badge-outline text-azure' style='width:auto'>".$zone->type."</span></td>";
print "</tr>";

print "<tr>";
print "	<th>"._("Is domain")."</th>";
$zone->is_domain = $zone->is_domain=="1" ? "Yes" : "No";
print "	<td><span class='badge badge-outline text-azure' style='width:auto'>".$zone->is_domain."</span></td>";
print "</tr>";


print "<tr>";
print "	<th>"._("Scan agent")."</th>";
print "	<td>".$zone->agname." <br><span class='text-muted' style='font-size:11px'>(".$zone->url.")</span></td>";
print "</tr>";


$zone->recipients = str_replace(";", "<br>", $zone->recipients);
print "<tr>";
print "	<th>"._("Tenant mail recipients")."</th>";
print "	<td>".$zone->recipients."</td>";
print "</tr>";


$zone->z_description = strlen($zone->z_description)==0||$zone->z_description==NULL ? "No description" : $zone->z_description;
print "<tr>";
print "	<th>"._("Description")."</th>";
print "	<td><span class='text-muted'>".$zone->z_description."</span></td>";
print "</tr>";

print "<tr class='line'>";
print "<th>"._("Manage zone")."</th>";
print "<td>";
print '<a href="/route/modals/zones/edit.php?action=edit&tenant='.$_params['tenant'].'&zone_name='.$zone->name.'" data-bs-toggle="modal" data-bs-target="#modal1" class="btn btn-sm bg-info-lt '. $user->actions_disabled.'"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415" /><path d="M16 5l3 3" /></svg> '._("Edit zone").'</a>';
# validate permissions
print '<a style="margin-left:5px" href="/route/modals/zones/edit.php?action=delete&tenant='.$_params['tenant'].'&zone_name='.$zone->name.'" data-bs-toggle="modal" data-bs-target="#modal1" class="btn btn-sm bg-info-lt text-danger '.$user->actions_disabled.'"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg> '._("Delete zone").'</a>';
print "</td>";
print "</tr>";


// axfr
if ($zone->type!="axfr") {


	print "<tr class='line'>";
	print "	<th>"._("Add host")."</th>";
	print "	<td><a href='/route/modals/zones/add-hostnames.php?action=add&tenant=".$_params['tenant']."&zone_name=".$zone->name."' class='btn btn-sm bg-info-lt text-success' data-bs-toggle='modal' data-bs-target='#modal1'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>'." "._("Add new host")."</a></td>";
	print "</tr>";

	if ($User->get_user_permissions(3)) {
		$Nmap         = new Nmap($Database);
		$nmap_pending = $Nmap->get_zone_pending_scans((int) $zone->id);

		print "<tr class='line'>";
		print "	<th>"._("Scan hosts")."</th>";
		print "	<td>";
		print "<a href='/route/modals/zones/nmap-scan.php?tenant=".$_params['tenant']."&zone_name=".$zone->name."' class='btn btn-sm bg-info-lt text-green' data-bs-toggle='modal' data-bs-target='#modal1'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-cloud-search"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M11 18.004h-4.343c-2.572 -.004 -4.657 -2.011 -4.657 -4.487c0 -2.475 2.085 -4.482 4.657 -4.482c.393 -1.762 1.794 -3.2 3.675 -3.773c1.88 -.572 3.956 -.193 5.444 1c1.488 1.19 2.162 3.007 1.77 4.769h.99" /><path d="M15 18a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M20.2 20.2l1.8 1.8" /></svg>'." "._("Scan hosts")."</a>";

		print "</td>";
		print "</tr>";
	}
}

print "<tr class='line'>";
print "	<th>"._("Import certificate")."</th>";
print "	<td><a href='/route/modals/certificates/import.php?tenant=".$_params['tenant']."&zone_name=".$zone->name."' class='btn btn-sm bg-info-lt text-azure' data-bs-toggle='modal' data-bs-target='#modal1'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-certificate"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 15a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5" /><path d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -1 1.73" /><path d="M6 9l12 0" /><path d="M6 12l3 0" /><path d="M6 15l2 0" /></svg>'." "._("Import certificate")."</a></td>";
print "</tr>";

print "</table>";
print "</div>";
print "</div>";
print "</div>";











//
// stats
//

$stats = ["hosts"=>0, "certificates"=>[], "unknown"=>0, "expired"=>0, "expire_soon"=>0, "mismatched"=>0];

// loop
foreach ($zone_hosts as $t) {
	// add stats
	$stats['hosts']++;

	// parse cert
	$cert_parsed = $Certificates->parse_cert ($all_certs[$t->c_id]->certificate);

	// get status
	$status  = $Certificates->get_status ($cert_parsed, false, true, $t->hostname);

	// unknown
	if($status['code']==0) { $stats['unknown']++; }
	// expired
	if($status['code']==1) { $stats['expired']++; }
	// expire soon
	if($status['code']==2) { $stats['expire_soon']++; }
	// mismatched
	if($status['code']==10) { $stats['mismatched']++; }
	// add stats cert
	if($t->c_id!="") $stats['certificates'][] = $t->c_id;
	// unique
	$stats['certificates'] = array_unique($stats['certificates']);
}


// expire class - red color
$expire_class      = $stats['expired']>0 ? "circle-expire" : "";
$expire_class_soon = $stats['expire_soon']>0 ? "circle-expire circle-expire-soon" : "";
$expire_class_mism = $stats['mismatched']>0 ? "circle-expire-soon" : "";


print "<div class='col-xs-12 col-sm-12 col-md-4 col-lg-6' style='margin-top:10px;'>";
print "<div class='card'>";
print "<div class='text-center row' style='padding-bottom:15px'>";

	print " <div class='card-header'>"._("Zone statistics")."</div>";

	// hosts
	print "<div class='col-4'>";
	print "<div>";
	print "<div class='circle'>".$stats['hosts']."</div>";
	print "<div class='text-muted circle-text'>"._("Hosts")."</div>";
	print "</div>";
	print "</div>";

	// certificates
	print "<div class='col-4'>";
	print "<div>";
	print "<div class='circle'>".sizeof($stats['certificates'])."</div>";
	print "<div class='text-muted circle-text'>"._("Certificates")."</div>";
	print "</div>";
	print "</div>";

	// unknown
	print "<div class='col-4'>";
	print "<div>";
	print "<div class='circle'>".$stats['unknown']."</div>";
	print "<div class='text-muted circle-text'>"._("Not found")."</div>";
	print "</div>";
	print "</div>";

	// Expire soon
	print "<div class='col-4'>";
	print "<div>";
	print "<div class='circle  $expire_class_soon' data-dst-text='Expires soon'>".$stats['expire_soon']."</div>";
	print "<div class='text-muted circle-text'>"._("Expire soon")."</div>";
	print "</div>";
	print "</div>";

	// Expired
	print "<div class='col-4'>";
	print "<div>";
	print "<div class='circle $expire_class'  data-dst-text='Expired'>".$stats['expired']."</div>";
	print "<div class='text-muted circle-text'>"._("Expired")."</div>";
	print "</div>";
	print "</div>";

	// mismatched
	print "<div class='col-4'>";
	print "<div>";
	print "<div class='circle $expire_class_mism '  data-dst-text='mismatch'>".$stats['mismatched']."</div>";
	print "<div class='text-muted circle-text'>"._("Missmatched")."</div>";
	print "</div>";
	print "</div>";

print "</div>";
print "</div>";
print "</div>";


// axfr
if ($zone->type=="axfr") {
	$zone->tsig = $zone->tsig=="" ? "<span class='text-muted'>/</span>" : $zone->tsig;
	$zone->delete_records = $zone->delete_records==1 ? "<span class='badge text-red'>Yes</span>" : "<span class='badge badge-outline text-green'>No</span>";
	$zone->record_types = explode(",", $zone->record_types);

	print "<div class='col-6' style='margin-top:20px'>";
	print "<div class='card'>";


	print " <div class='card-header'>"._("AXFR details")." :: ".$zone->name."</div>";

	print " <div class=''>";
	print "<table class='table table-borderless table-md table-hover table-zones-details'>";
	print "<tr>";
	print "	<th style='width:220px;'>"._("Authoritative DNS")."</th>";
	print "	<td>".$zone->dns."</td>";
	print "</tr>";
	print "	<th>"._("Zone name")."</th>";
	print "	<td><strong>".$zone->aname."</strong></td>";
	print "</tr>";
	if($zone->tsig_name!="") {
		print "<tr>";
		print "	<th>"._("TSIG name")."</th>";
		print "	<td>".$zone->tsig_name."</td>";
		print "</tr>";
		print "<tr>";
		print "	<th>"._("TSIG")."</th>";
		print "	<td>".$zone->tsig."</td>";
		print "</tr>";
	}
	print "<tr>";
	print "	<th>"._("Valid records")."</th>";
	print "	<td>";
	foreach ($zone->record_types as $r) {
		print "<span class='badge text-default' style='margin-right:2px'>$r</span>";
	}
	print "</td>";
	print "</tr>";
	print "<tr>";
	print "	<th>"._("Delete records")."</th>";
	print "	<td>"._($zone->delete_records)."</td>";
	print "</tr>";
	// print "<tr>";
	// print "	<th>"._("Include patterns")."</th>";
	// print "	<td>"._($zone->regex_include)."</td>";
	// print "</tr>";
	// print "<tr>";
	// print "	<th>"._("Exclude patterns")."</th>";
	// print "	<td>"._($zone->regex_exclude)."</td>";
	// print "</tr>";

	print "<tr class='line'>";
	print "	<th>"._("Sync zone")."</th>";
	print "	<td><a href='/route/modals/zones/axfr-sync.php?&tenant=".$_params['tenant']."&zone_name=".$zone->name."' class='btn btn-sm btn-outline-success' data-bs-toggle='modal' data-bs-target='#modal1'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-refresh"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>'." "._("Sync now")."</a></td>";
	print "</tr>";

	print "</table>";

	print "</div>";
	print "</div>";
}


//
// Nmap scan history (RWA+ only)
//
if ($User->get_user_permissions(3)) {
	// $Nmap and $nmap_scans were already populated above in the button block
	if (!isset($Nmap)) {
		$Nmap = new Nmap($Database);
	}
	$nmap_scans = $Nmap->get_zone_scans((int) $zone->id);

	print "<div class='col-12' style='margin-top:20px'>";
	print "<div class='card'>";
	print "<div class='card-header'>";
	print '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-cloud-search"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M11 18.004h-4.343c-2.572 -.004 -4.657 -2.011 -4.657 -4.487c0 -2.475 2.085 -4.482 4.657 -4.482c.393 -1.762 1.794 -3.2 3.675 -3.773c1.88 -.572 3.956 -.193 5.444 1c1.488 1.19 2.162 3.007 1.77 4.769h.99"></path><path d="M15 18a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path><path d="M20.2 20.2l1.8 1.8"></path></svg>';
	print _("Network scans");
	print "</div>";

	if (empty($nmap_scans)) {
		print "<div class='card-body text-muted'>"._("No scans yet.")."</div>";
	} else {
		print "<table class='table table-sm table-hover table-borderless'>";
		print "<thead><tr>";
		print "<th>"._("Prefix")."</th>";
		print "<th class='d-none d-lg-table-cell'>"._("Port group")."</th>";
		print "<th>"._("PTR")."</th>";
		print "<th>"._("Status")."</th>";
		print "<th>"._("Found")."</th>";
		print "<th>"._("Added")."</th>";
		print "<th>"._("Requested")."</th>";
		print "<th class='d-none d-lg-table-cell'>"._("Completed")."</th>";
		print "<th class='d-none d-lg-table-cell'>"._("User")."</th>";
		print "</tr></thead>";
		print "<tbody>";

		foreach ($nmap_scans as $s) {
			switch ($s->status) {
				case "Completed": $badge = "badge bg-info-lt  text-green";  break;
				case "Scanning":  $badge = "badge bg-info-lt  text-blue";   break;
				case "Error":     $badge = "badge bg-info-lt  text-red";    break;
				default:          $badge = "badge bg-info-lt  text-muted";  break;
			}
			$ptr_icon  = $s->ptr_lookup ? "<span class='badge text-azure bg-info-lt '>"._("Yes")."</span>" : "<span class='text-muted'>—</span>";
			$completed = $s->completed  ? $s->completed : "<span class='text-muted'>—</span>";
			$error_tip = $s->status === "Error" && $s->error_msg
				? " title='" . htmlspecialchars($s->error_msg) . "' data-bs-toggle='tooltip'"
				: "";

			print "<tr>";
			$pg_display = !empty($s->pg_name) ? htmlspecialchars($s->pg_name) . " <small class='text-muted'>(" . htmlspecialchars($s->pg_ports ?? '') . ")</small>" : "<span class='text-muted'>—</span>";
			print "<td><span class='badge'>" . htmlspecialchars($s->prefix) . "</span></td>";
			print "<td class='text-muted d-none d-lg-table-cell'>{$pg_display}</td>";
			print "<td>{$ptr_icon}</td>";
			print "<td><span class='{$badge}'{$error_tip}>" . htmlspecialchars($s->status) . "</span></td>";
			print "<td>" . (int) $s->hosts_found . "</td>";
			print "<td>" . (int) $s->hosts_added . "</td>";
			print "<td class='text-muted d-none d-lg-table-cell'>" . htmlspecialchars($s->requested) . "</td>";
			print "<td class='text-muted d-none d-lg-table-cell'>" . $completed . "</td>";
			print "<td>" . htmlspecialchars($s->username ?? '') . "</td>";
			print "</tr>";
		}

		print "</tbody></table>";
	}

	print "</div>"; // card
	print "</div>"; // col
}

<?php

# validate user session
$User->validate_session ();

$show_details = 1;

// header
if(!isset($from_search)) {
	print '<div class="page-header">';
	print '<h3>'._("Zone hosts").'</h3><hr>';
	print '</div>';
}

# none
if (!is_object($zone) && !isset($from_search)) {
	print '<div class="page-header">';
	$Result->show("danger", _("Invalid zone").".");
	print '</div>';
}
else {
	# all port groups
	$all_port_groups = $SSL->get_all_port_groups ();

	# top buttons
	if(!isset($from_search)) {
		print "<div class='text-left' style='margin-bottom:10px'>";
		if(sizeof($zone_hosts)>1)
		print '<a href="/" class="btn btn-sm btn-outline-secondary toggle-show-multiple"><i class="fa fa-pencil"></i> '._("Edit multiple").'</a>';
		print '<a href="/route/error/modal.php" data-bs-toggle="modal" data-bs-target="#modal1" class="btn btn-outline-success btn-sm btn-5 d-none d-sm-inline-block"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-upload"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 9l5 -5l5 5" /><path d="M12 4l0 12" /></svg> '._("Import").'</a>';

		// actions right
		if(sizeof($zone_hosts)>0) {
		print '<div class="btn-group float-end">';
		print '<a href="/route/modals/zones/truncate.php?zone_id='.$zone->id.'&tenant='.$_params['tenant'].'" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal1"><i class="fa fa-trash" data-bs-toggle="tooltip" data-bs-placement="top" title="'._("Remove all hosts from zone").'"></i> '._("Remove all").'</a>';
		print '<a href="/route/modals/zones/zone-cert-refresh-all.php?zone_id='.$zone->id.'&tenant='.$_params['tenant'].'" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modal1"><i class="fa fa-sync" data-bs-toggle="tooltip" data-bs-placement="top" title="'._("Rescan all hosts for new certificates").'"></i> '._("Rescan all").'</a>';
		print "</div>";
		}

		print "</div>";
	}


	print "<div class='card' style='margin:$margin'>";

	print "<table class='table table-hover align-top table-sm' data-toggle='table' data-mobile-responsive='true' data-check-on-init='true' data-classes='table table-hover table-sm' data-cookie='false' data-cookie-id-table='zonehosts' data-pagination='true' data-page-size='250' data-page-list='[50,250,500,All]' data-search='true' data-fixed-rtable='false' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";

	// header
	print "<thead>";
	print "<tr>";
	print " <th class='checkbox-hidden visually-hidden' data-width='10'><input type='checkbox' class='form-check-input select-all' name='select-all'></th>";
	print "	<th class='actions d-none d-lg-table-cell text-center' data-width='30' data-width-unit='px' style='width:30px;'><i class='fa fa-check' data-bs-toggle='tooltip' data-bs-placement='top'></i></th>";
	print "	<th>"._("Hostname")."</th>";

	if(isset($from_search)) {
		if($user->admin=="1")
		print "	<th>"._("Zone")."/"._("Tenant")."</th>";
		else
		print "	<th>"._("Zone")."</th>";
	}

	if($show_details==0)
	print "	<th class='d-none d-lg-table-cell'>"._("Status")."</th>";
	print "	<th>"._("Certificate")."</th>";
	if($show_details==0)
	print "	<th class='d-none d-lg-table-cell'>"._("Domain")."</th>";
	if($show_details==0)
	print "	<th class='d-none d-xl-table-cell'>"._("Issuer")."</th>";
	if($show_details==1)
	print "	<th class='d-none d-xl-table-cell'>"._("Domain")." / "._("Issuer")."</th>";
	if($show_details==1)
	print "	<th class='d-none d-xl-table-cell'>"._("Checked / Changed")."</th>";
	if($show_details==0)
	print "	<th style='width:150px;'>"._("Valid To")."</th>";
	print "	<th style='width:50px;' data-width='50' data-width-unit='px' class='d-none d-xl-table-cell'>"._("Port")."</th>";
	print "	<th style='width:50px;' data-width='50' data-width-unit='px' class='d-none d-xl-table-cell'>"._("Port group")."</th>";
	print "	<th style='width:50px;' data-width='50' data-width-unit='px' class='d-table-cell'></th>";

	print "</tr>";
	print "</thead>";




	if(sizeof($zone_hosts)==0) {
		$colspan = isset($from_search) ? 12 : 11;
		$margin  = isset($from_search) ? "0px" : "20px 0px";

		print "<tbody>";
		print "<tr>";
		print " <td class='checkbox-hidden visually-hidden'></td>";;
		print "	<td colspan=12> <div class='alert alert-info'>"._("No hosts")."</div></td>";
		print "</tr>";
		print "</tbody>";

	}
	else {
		// body

		foreach ($zone_hosts as $t) {
			// reset zone if search !
			if (isset($from_search)) {
				// fetch zone
				$zone = $Zones->get_zone ($t->href, $t->z_id);
			}

			// null IP
			if(is_null($t->ip)) { $t->ip = "<span class='badge bg-light-lt bg-light text-muted'>Unresolved</span>"; }


			$refresh = $t->ignore=="1" ?
				"<span class='badge badge-outline text-muted' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("SSL check disabled")."'>".$url_items["scanning"]["icon"] :
				"<a href='/route/modals/zones/host_cert_refresh.php?tenant=".$zone->t_id."&zone_id=".$zone->id."&host_id=".$t->id."' data-bs-toggle='modal' data-bs-target='#modal1'><span class='badge badge-outline text-green' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Refresh now")."'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-refresh"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>';


			// date
			$t->last_check  = $t->last_check===NULL||$t->ignore==1 ? "/"  : $t->last_check;
			$t->last_change = $t->last_change===NULL||$t->ignore==1 ? "/" : $t->last_change;

			// parse cert
			$cert_parsed = $Certificates->parse_cert ($all_certs[$t->c_id]->certificate);

			// get status
			$status = $Certificates->get_status ($cert_parsed, true, true, $t->hostname);

			// text class
			$danger_class = "";
			if($status['code']==0)		{ $textclass='secondary'; $textclass_error = "secondary"; }
			elseif($status['code']==1)	{ $textclass='red'; $textclass_error = "red"; }
			elseif($status['code']==2)	{ $textclass='orange'; $textclass_error = "orange"; }
			elseif($status['code']==3)	{ $textclass='green'; $textclass_error = ""; }
			else 						{ $textclass=''; $textclass_error = ""; }

			// port
			$t->port = strlen($t->port)>0 ? $t->port : "/";

			// dates
			if($t->last_check!="/")
			$t->last_check  = date("Y-m-d H:i", strtotime($t->last_check));
			$t->last_change = date("Y-m-d H:i", strtotime($t->last_change));


			// line
			print "<tr class='table-hosts text-$textclass_error'>";

			// checkbox
			print " <td title='checkbox' class='checkbox-hidden visually-hidden'><input type='checkbox' class='form-check-input select-current' data-type='hosts' name='item-{$t->id}'></td>";

			// icon
			print '	<td><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-server"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3" /><path d="M3 15a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3l0 -2" /><path d="M7 8l0 .01" /><path d="M7 16l0 .01" /></svg></td>';


			// status icons
			$status_icons = " ";
			$status_icons .= sizeof(array_filter(explode(";", $t->h_recipients)))>0 ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user text-info"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg> ' : '';
			$status_icons .= $t->mute=="1" ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-volume-3 text-red"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /><path d="M16 10l4 4m0 -4l-4 4" /></svg> ' : '';
			$status_icons .= $t->ignore=="1" ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-rosette-discount-check-off text-red"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 12l2 2l1.5 -1.5m2 -2l.5 -.5" /><path d="M8.887 4.89a2.2 2.2 0 0 0 .863 -.53l.7 -.7a2.2 2.2 0 0 1 3.12 0l.7 .7c.412 .41 .97 .64 1.55 .64h1a2.2 2.2 0 0 1 2.2 2.2v1c0 .58 .23 1.138 .64 1.55l.7 .7a2.2 2.2 0 0 1 0 3.12l-.7 .7a2.2 2.2 0 0 0 -.528 .858m-.757 3.248a2.193 2.193 0 0 1 -1.555 .644h-1a2.2 2.2 0 0 0 -1.55 .64l-.7 .7a2.2 2.2 0 0 1 -3.12 0l-.7 -.7a2.2 2.2 0 0 0 -1.55 -.64h-1a2.2 2.2 0 0 1 -2.2 -2.2v-1a2.2 2.2 0 0 0 -.64 -1.55l-.7 -.7a2.2 2.2 0 0 1 0 -3.12l.7 -.7a2.2 2.2 0 0 0 .64 -1.55v-1c0 -.604 .244 -1.152 .638 -1.55" /><path d="M3 3l18 18" /></svg> ' : '';

			// hostname
			print "	<td>";
			print "	<strong><a style='color:var(--tblr-body-color)' href='/".$t->href."/zones/".$t->zone_name."/".$t->hostname."/'>".$t->hostname."</a>";
			print $status_icons;
			if($show_details==1)
			print "		<br><span class='visually-hi1dden text-muted' style='font-weight:normal;font-size:11px;'>{$t->ip}</span>";
			print "	</td>";

			if(isset($from_search)) {
			print "<td class='d-none d-lg-table-cell'>";
			print "	<a href='/".$t->href."/zones/".$t->zone_name."/' target='_blank'>".$t->zone_name."</a><br>";
			if($user->admin=="1")
			print "<span class='text-muted'>".$t->name."</span>";
			print "</td>";
			}

			// status
			if($show_details==0)
			print "	<td class='d-none d-lg-table-cell'>".$status['text']."</td>";
			// serial
			if($cert_parsed['serialNumberHex']!="/") {
				print "<td>";
				print "	<a class='btn btn-sm text-$textclass_error' style='color:'href='/".$t->href."/certificates/".$t->zone_name."/".$cert_parsed['serialNumber']."/' target='_blank'>".$url_items["certificates"]['icon']." ".$cert_parsed['serialNumberHex']."</a>";
				if($show_details==1)
				print "<br>".$status['text']." <span class='text-muted' style='font-size:11px;'>".$cert_parsed['custom_validTo']."</span>";
				print "</td>";
			}
			else {
				if($show_details==0)
				print "	<td class='d-none d-lg-table-cell'>".$cert_parsed['serialNumberHex']."</td>";
				else
				print "	<td class='d-none d-lg-table-cell'>".$status['text']."</td>";
			}
			// domain
			if($show_details==0)
			print "	<td class='d-none d-lg-table-cell'>".$cert_parsed['subject']['CN']."</td>";
			// issuer
			if($show_details==0)
			print "	<td class='d-none d-xl-table-cell text-muted'>".$cert_parsed['issuer']['O']."</td>";
			// domain / issuer
			if($show_details==1) {
			print "	<td class='d-none d-lg-table-cell'>";
			print $cert_parsed['subject']['CN']."<br><span class='text-muted'>".$cert_parsed['issuer']['O']."</span>";
			print "</td>";
			}
			// last check
			if($show_details==1) {
				if($cert_parsed['serialNumberHex']!="/")
				print "	<td class='d-none d-xl-table-cell text-muted' style='font-size:11px;'>".$t->last_check."<br>".$t->last_change."</td>";
				else
				print "	<td class='d-none d-xl-table-cell text-muted' style='font-size:11px;'>".$t->last_check."<br>".$t->last_change."</td>";
			}

			// valid to
			if($show_details==0)
			print "	<td>".$cert_parsed['custom_validTo']."</td>";
			// found on port
			$tls = !is_null($t->tls_version)&&$cert_parsed['serialNumberHex']!="/" ? "<span class='text-muted' style='font-size:10px;'>".$t->tls_version."</span>" : "";
			$t->port = $cert_parsed['serialNumberHex']!="/" ? $t->port : "/";
			print "	<td class='d-none d-xl-table-cell'><span class='badge'>".$t->port."</span><br>$tls</td>";
			// portgroups for scan
			print "	<td class='d-none d-xl-table-cell'><span class='badge' data-bs-toggle='tooltip'data-bs-html='true' data-bs-placement='bottom' title='tcp/".implode("<br>tcp/", $all_port_groups[$t->t_id][$t->pg_id]['ports'])."'>".$all_port_groups[$t->t_id][$t->pg_id]['name']."</span></td>";

			// new actions
			print '<td class="actions d-table-cell">';
			print '	<span class="dropdown">';
			print '		<button class="btn btn-sm dropdown-toggle align-text-top" data-bs-boundary="viewport" data-bs-toggle="dropdown" aria-expanded="false"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-settings"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg></button>';
			print '		<div class="dropdown-menu dropdown-menu-en1d" style="">';


			print '			<h6 class="dropdown-header">'._('Notifications').'</h6>';

			// recipients
			$recipients = array_filter(explode(";", $t->h_recipients));
			$recipients_badge = sizeof($recipients)>0 ? '<span class="badge bg-primary text-primary-fg ms-auto">'.sizeof($recipients).'</span>' : '';
			print '			<a class="dropdown-item" href="/route/modals/zones/host-set-recipients.php?tenant='.$zone->href.'&zone_id='.$zone->id.'&host_id='.$t->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-cog dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h2.5" /><path d="M17.001 19a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M19.001 15.5v1.5" /><path d="M19.001 21v1.5" /><path d="M22.032 17.25l-1.299 .75" /><path d="M17.27 20l-1.3 .75" /><path d="M15.97 17.25l1.3 .75" /><path d="M20.733 20l1.3 .75" /></svg>'._("Manage recipients").''.$recipients_badge;

			// mute notifications
			$mute_icon = $t->mute=="1" ? 'text-red' : '';
			if($t->mute=="1")
			print '			<a class="dropdown-item" href="/route/modals/zones/host_ignore_mute.php?type=mute&tenant='.$_params['tenant'].'&zone_id='.$zone->id.'&host_id='.$t->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-volume-3 dropdown-item-icon text-red"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /><path d="M16 10l4 4m0 -4l-4 4" /></svg>'._("Enable notification").'</a>';
			else
			print '			<a class="dropdown-item" href="/route/modals/zones/host_ignore_mute.php?type=mute&tenant='.$_params['tenant'].'&zone_id='.$zone->id.'&host_id='.$t->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline text-red icon-tabler-volume dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8a5 5 0 0 1 0 8" /><path d="M17.7 5a9 9 0 0 1 0 14" /><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /></svg>'._("Disable notification").'</a>';
			// ssl check
			if($t->ignore=="1")
			print '			<a class="dropdown-item" href="/route/modals/zones/host_ignore_mute.php?type=ignore&tenant='.$_params['tenant'].'&zone_id='.$zone->id.'&host_id='.$t->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-rosette-discount-check-off dropdown-item-icon text-red"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 12l2 2l1.5 -1.5m2 -2l.5 -.5" /><path d="M8.887 4.89a2.2 2.2 0 0 0 .863 -.53l.7 -.7a2.2 2.2 0 0 1 3.12 0l.7 .7c.412 .41 .97 .64 1.55 .64h1a2.2 2.2 0 0 1 2.2 2.2v1c0 .58 .23 1.138 .64 1.55l.7 .7a2.2 2.2 0 0 1 0 3.12l-.7 .7a2.2 2.2 0 0 0 -.528 .858m-.757 3.248a2.193 2.193 0 0 1 -1.555 .644h-1a2.2 2.2 0 0 0 -1.55 .64l-.7 .7a2.2 2.2 0 0 1 -3.12 0l-.7 -.7a2.2 2.2 0 0 0 -1.55 -.64h-1a2.2 2.2 0 0 1 -2.2 -2.2v-1a2.2 2.2 0 0 0 -.64 -1.55l-.7 -.7a2.2 2.2 0 0 1 0 -3.12l.7 -.7a2.2 2.2 0 0 0 .64 -1.55v-1c0 -.604 .244 -1.152 .638 -1.55" /><path d="M3 3l18 18" /></svg>'._("Enable SSL check").'</a>';
			else
			print '			<a class="dropdown-item" href="/route/modals/zones/host_ignore_mute.php?type=ignore&tenant='.$_params['tenant'].'&zone_id='.$zone->id.'&host_id='.$t->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-rosette-discount-check dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 7.2a2.2 2.2 0 0 1 2.2 -2.2h1a2.2 2.2 0 0 0 1.55 -.64l.7 -.7a2.2 2.2 0 0 1 3.12 0l.7 .7c.412 .41 .97 .64 1.55 .64h1a2.2 2.2 0 0 1 2.2 2.2v1c0 .58 .23 1.138 .64 1.55l.7 .7a2.2 2.2 0 0 1 0 3.12l-.7 .7a2.2 2.2 0 0 0 -.64 1.55v1a2.2 2.2 0 0 1 -2.2 2.2h-1a2.2 2.2 0 0 0 -1.55 .64l-.7 .7a2.2 2.2 0 0 1 -3.12 0l-.7 -.7a2.2 2.2 0 0 0 -1.55 -.64h-1a2.2 2.2 0 0 1 -2.2 -2.2v-1a2.2 2.2 0 0 0 -.64 -1.55l-.7 -.7a2.2 2.2 0 0 1 0 -3.12l.7 -.7a2.2 2.2 0 0 0 .64 -1.55v-1" /><path d="M9 12l2 2l4 -4" /></svg>'._("Disable SSL check").'</a>';

    		print '			<div class="dropdown-divider"></div>';
			print '			<h6 class="dropdown-header">'._('Manage').'</h6>';
			// assign port-group
			print '			<a class="dropdown-item disabled" href="/route/error/modal.php" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-category dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4h6v6h-6l0 -6" /><path d="M14 4h6v6h-6l0 -6" /><path d="M4 14h6v6h-6l0 -6" /><path d="M14 17a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /></svg>'._("Assign portgroup").'</a>';
			// refresh cert
			print '			<a class="dropdown-item" href="/route/modals/zones/host_cert_refresh.php?tenant='.$zone->t_id.'&zone_id='.$zone->id.'&host_id='.$t->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-refresh dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>'._("Refresh certificate").'</a>';
			// delete
			print '			<a class="dropdown-item" href="/route/modals/zones/delete_hostname.php?tenant='.$zone->href.'&zone_id='.$zone->id.'&host_id='.$t->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>'._("Delete host").'</a>';
			// delete dert
			$disabled = is_null($cert_parsed['serialNumber']) ? "disabled" : "";
			print '			<a class="dropdown-item '.$disabled.'"  href="/route/modals/certificates/delete.php?tenant='.$_params['tenant'].'&serial='.$cert_parsed['serialNumber'].'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-certificate-off dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12.876 12.881a3 3 0 0 0 4.243 4.243m.588 -3.42a3.012 3.012 0 0 0 -1.437 -1.423" /><path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5" /><path d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2m4 0h10a2 2 0 0 1 2 2v10" /><path d="M6 9h3m4 0h5" /><path d="M6 12h3" /><path d="M6 15h2" /><path d="M3 3l18 18" /></svg>'._("Delete certificate").'</a>';
			print "	</td>";

			// recipients
			$recipients_popover = strlen($t->h_recipients)>5 ? str_replace(";","<br>", $t->h_recipients) : "No extra recipients";


			print "</tr>";
		}
	}
	print "</tbody>";
	print "</table>";
	print "</div>";
}
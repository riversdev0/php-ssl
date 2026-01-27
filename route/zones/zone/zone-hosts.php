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
	print "<table class='table table-hover align-top table-sm' data-toggle='table' data-mobile-responsive='true' data-check-on-init='true' data-classes='table table-hover table-sm' data-cookie='false' data-cookie-id-table='zonehosts' data-pagination='true' data-page-size='250' data-page-list='[50,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";

	// header
	print "<thead>";
	print "<tr>";
	print " <th class='checkbox-hidden visually-hidden'><input type='checkbox' class='form-check-input select-all' name='select-all'></th>";
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
	print "	<th class='actions d-none d-lg-table-cell text-center' data-width='30' data-width-unit='px' style='width:30px;'><i class='fa fa-volume-high' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Notification status")."'></i></th>";
	print "	<th class='actions d-none d-lg-table-cell text-center' data-width='30' data-width-unit='px' style='width:30px;'><i class='fa fa-check' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("SSL check status")."'></i></th>";
	print "	<th class='actions d-none d-lg-table-cell text-center' data-width='30' data-width-unit='px' style='width:30px;'><i class='fa fa-refresh' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Refresh certificate")."'></i></th>";
	print "	<th class='actions d-none d-lg-table-cell text-center' data-width='30' data-width-unit='px' style='width:30px;'><i class='fa fa-user' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Additional recipiens")."'></i></th>";
	print "	<th class='actions d-none d-lg-table-cell text-center' data-width='30' data-width-unit='px' style='width:30px;'><i class='fa fa-xmark' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Remove host")."'></i></th>";

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

			// text on buttons
			$muted = $t->mute=="1" ?
				"<span class='badge badge-outline text-muted' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Notifications disabled")."'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-volume-3"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /><path d="M16 10l4 4m0 -4l-4 4" /></svg>' :
				 "<span class='badge badge-outline text-green' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Notifications enabled")."'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-volume"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8a5 5 0 0 1 0 8" /><path d="M17.7 5a9 9 0 0 1 0 14" /><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /></svg>';

			$ignore = $t->ignore=="1" ?
				"<span class='badge badge-outline text-muted' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("SSL check disabled")."'>".$url_items["scanning"]["icon"] :
				"<span class='badge badge-outline text-green' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("SSL check enabled")."'>".$url_items["scanning"]["icon"];


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
			// hostname
			print "	<td>";
			print '	<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-server"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3" /><path d="M3 15a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3l0 -2" /><path d="M7 8l0 .01" /><path d="M7 16l0 .01" /></svg>';
			print "	<strong>".$t->hostname;
			if($show_details==1)
			print "		<br><span class='visually-hi1dden text-muted' style='padding-left: 21px;font-weight:normal;font-size:11px;'>{$t->ip}</span>";
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

			// actions
			print "	<td class='actions d-none d-lg-table-cell text-center'><a href='/route/modals/zones/host_ignore_mute.php?type=mute&tenant=".$_params['tenant']."&zone_id=".$zone->id."&host_id=".$t->id."' data-bs-toggle='modal' data-bs-target='#modal1'>".$muted."</a></span></td>";
			print "	<td class='actions d-none d-lg-table-cell text-center'><a href='/route/modals/zones/host_ignore_mute.php?type=ignore&tenant=".$_params['tenant']."&zone_id=".$zone->id."&host_id=".$t->id."' data-bs-toggle='modal' data-bs-target='#modal1'>".$ignore."</a></span></td>";
			print "	<td class='actions d-none d-lg-table-cell text-center'>".$refresh."</span></td>";
			if(strlen($t->h_recipients)>5)
			print "	<td class='actions d-none d-lg-table-cell text-center'><span class='badge badge-outline text-info' data-bs-toggle='tooltip' data-bs-html='true' data-bs-placement='top' title='".str_replace(";","<br>", $t->h_recipients)."'><a href='/route/modals/zones/host_set_recipients.php?tenant=".$zone->href."&zone_id=".$zone->id."&host_id=".$t->id."' data-bs-toggle='modal' data-bs-target='#modal1'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-cog"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h2.5" /><path d="M17.001 19a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M19.001 15.5v1.5" /><path d="M19.001 21v1.5" /><path d="M22.032 17.25l-1.299 .75" /><path d="M17.27 20l-1.3 .75" /><path d="M15.97 17.25l1.3 .75" /><path d="M20.733 20l1.3 .75" /></svg>'."</a></span></td>";
			else
			print "	<td class='actions d-none d-lg-table-cell text-center'><span class='badge badge-outline text-muted' data-bs-toggle='tooltip' data-bs-html='true' data-bs-placement='top' title='No extra recipients'><a href='/route/modals/zones/host_set_recipients.php?tenant=".$zone->href."&zone_id=".$zone->id."&host_id=".$t->id."' data-bs-toggle='modal' data-bs-target='#modal1' class='text-secondary'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-cog"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h2.5" /><path d="M17.001 19a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M19.001 15.5v1.5" /><path d="M19.001 21v1.5" /><path d="M22.032 17.25l-1.299 .75" /><path d="M17.27 20l-1.3 .75" /><path d="M15.97 17.25l1.3 .75" /><path d="M20.733 20l1.3 .75" /></svg>'."</a></span></td>";

			print "	<td class='actions d-none d-lg-table-cell text-center'><a href='/route/modals/zones/delete_hostname.php?tenant=".$zone->href."&zone_id=".$zone->id."&host_id=".$t->id."' data-bs-toggle='modal' data-bs-target='#modal1'><span class='badge badge-outline text-red' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Remove host")."'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>'."</td>";

			print "</tr>";
		}
	}
	print "</tbody>";
	print "</table>";
	print "</div>";
}
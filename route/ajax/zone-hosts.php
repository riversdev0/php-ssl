<?php

/**
 *
 * POST headers:
 *
 * array(5) {
 *	 ["search"] => string(0) ""
 *	 ["sort"]   => string(0) ""
 *	 ["order"]  => string(0) ""
 *	 ["offset"] => string(1) "0"
 *	 ["limit"]  => string(2) "50"
 *	 ["zone_id"] => int
 * }
 *
 */

# functions
require('../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, false);


try {

	// validate search
	if(strlen(@$_POST['search']>0)) {
		if(!$User->validate_alphanumeric($_POST['search'])) {
			throw new exception ("Invalid search parameters");
		}
	}


	// query
	$query     = "";
	$query_all = "";
	$vars      = [];

	// Check if zone_id is provided
	$has_zone_id = isset($_POST['zone_id']) && is_numeric($_POST['zone_id']);
	
	if(!$has_zone_id) {
		// No zone_id - query all hosts
		$query 	   .= "select h.id, h.z_id, h.c_id, h.hostname, h.ip, h.port, h.pg_id, h.ignore, h.mute, h.h_recipients, h.last_check, h.last_change, h.tls_version, h.z_id, z.name as zone_name, z.t_id, t.href as tenant_href, t.name as tenant_name, c.certificate, c.expires, c.serial as cert_serial from hosts h left join zones z on h.z_id = z.id left join tenants t on z.t_id = t.id left join certificates c on h.c_id = c.id where 1=1 ";
		$query_all .= "select count(*) as cnt from hosts h left join zones z on h.z_id = z.id left join tenants t on z.t_id = t.id left join certificates c on h.c_id = c.id where 1=1 ";
	}
	else {
		// Specific zone
		$zone_id = $_POST['zone_id'];
		$query 	   .= "select h.id, h.z_id, h.c_id, h.hostname, h.ip, h.port, h.pg_id, h.ignore, h.mute, h.h_recipients, h.last_check, h.last_change, h.tls_version, h.z_id, z.name as zone_name, z.t_id, t.href as tenant_href, t.name as tenant_name, c.certificate, c.expires, c.serial as cert_serial from hosts h left join zones z on h.z_id = z.id left join tenants t on z.t_id = t.id left join certificates c on h.c_id = c.id where h.z_id = :zone_id ";
		$query_all .= "select count(*) as cnt from hosts h left join zones z on h.z_id = z.id left join tenants t on z.t_id = t.id left join certificates c on h.c_id = c.id where h.z_id = :zone_id ";
		$vars['zone_id'] = $zone_id;
	}

	// not admin ?
	if($user->admin=="0") {
		$query        .= " and z.t_id = :t_id";
		$query_all    .= " and z.t_id = :t_id";
		$vars['t_id'] = $user->t_id;
	}
	// search ?
	if(strlen($_POST['search'])>0) {
		$query         .= " and (h.hostname like :search or h.ip like :search or ifnull(c.serial,'') like :search)";
		$query_all     .= " and (h.hostname like :search or h.ip like :search or ifnull(c.serial,'') like :search)";
		$vars['search']  = "%".$_POST['search']."%";
	}

	// filter ?
	$filter = isset($_POST['filter']) ? $_POST['filter'] : "all";

	// order, sort
	if (strlen($_POST['sort'])>0 && in_array($_POST['sort'], ['id','hostname','ip','port','last_check','last_change'])) {
		$query .= " order by h.".$_POST['sort']." ";
		$query .= $_POST['order']=="desc" ? "desc" : "asc";
	}
	else {
		$query .= " order by h.hostname asc ";
	}
	// limit
	if(is_numeric($_POST['limit'])) {
		$query .= " limit ".$_POST['limit'];
	}
	// offset
	if(is_numeric($_POST['offset'])) {
		$query .= " offset  ".$_POST['offset'];
	}

	// fetch
	try {
		$hosts         = $Database->getObjectsQuery ($query, $vars);
		$hosts_all     = $Database->getObjectQuery ($query_all, $vars);
	} catch (Exception $e) {
		$result = [];
		$result['total']            = 0;
		$result['totalNotFiltered'] = 0;
		$result['rows']             = [];
		$result['error']            = $e->getMessage(). " | Query: ".$query;
		header('HTTP/1.1 200 OK');
		print json_encode($result);
		exit;
	}

	// port groups
	$all_port_groups = $SSL->get_all_port_groups ();

	// init Certificates class
	$Certificates = new Certificates ($Database);

	// get user days setting
	$expire_days = isset($user->days) ? $user->days : (isset($expired_days) ? $expired_days : 30);

	if(sizeof($hosts)>0) {
		foreach ($hosts as $h) {
			// null IP
			$h->ip_formatted = is_null($h->ip) ? "<span class='badge bg-light-lt bg-light text-muted'>Unresolved</span>" : $h->ip;

			// parse cert if exists
			$cert_parsed = $Certificates->parse_cert ($h->certificate);

			// get status
			$status = $Certificates->get_status ($cert_parsed, true, true, $h->hostname);

			// text class
			if($status['code']==0)		{ $textclass='secondary'; $danger_class = "secondary"; }
			elseif($status['code']==1)	{ $textclass='red'; $danger_class = "red"; }
			elseif($status['code']==2)	{ $textclass='orange'; $danger_class = "orange"; }
			elseif($status['code']==3)	{ $textclass='green'; $danger_class = ""; }
			else 						{ $textclass=''; $danger_class = ""; }

			// dates
			$h->last_check_formatted = $h->last_check===NULL||$h->ignore==1 ? "/" : date("Y-m-d H:i", strtotime($h->last_check));
			$h->last_change_formatted = $h->last_change===NULL||$h->ignore==1 ? "/" : date("Y-m-d H:i", strtotime($h->last_change));

			// port
			$h->port_formatted = strlen($h->port)>0 ? $h->port : "/";

			// tls version
			$h->tls_formatted = !is_null($h->tls_version) && $cert_parsed['serialNumberHex']!="/" ? "<span class='text-muted' style='font-size:10px;'>".$h->tls_version."</span>" : "";

			// domain / CN
			$h->domain = isset($cert_parsed['subject']['CN']) ? $cert_parsed['subject']['CN'] : "/";

			// issuer
			$h->issuer = isset($cert_parsed['issuer']['O']) ? $cert_parsed['issuer']['O'] : (isset($cert_parsed['issuer']['CN']) ? $cert_parsed['issuer']['CN'] : "/");

			// days valid
			$h->days_valid = isset($cert_parsed['custom_validDays']) ? $cert_parsed['custom_validDays'] : "/";
			if(is_numeric($h->days_valid)) {
				if($h->days_valid < 0) {
					$days_class = "red";
				}
				elseif($h->days_valid <= $expire_days) {
					$days_class = "orange";
				}
				else {
					$days_class = "green";
				}
				$h->status_badge = "<span class='badge bg-$days_class'></span>";
			}
			else {
				$days_class = "secondary";
				$h->status_badge = "<span class='badge'></span>";
			}

			// valid to
			$h->valid_to = isset($cert_parsed['custom_validTo']) ? $cert_parsed['custom_validTo'] : "/";

			// serial
			if($cert_parsed['serialNumberHex']!="/") {
				$h->serial_html = "<a class='btn btn-sm text-$danger_class' href='/".$h->tenant_href."/certificates/".$h->zone_name."/".$cert_parsed['serialNumber']."/' target='_blank'>".$cert_parsed['serialNumber']."</a><br>".$status['text']." <span class='text-muted' style='font-size:11px;'>".$cert_parsed['custom_validTo']."</span>";
			}
			else {
				$h->serial_html = "<span class='badge bg-light-lt bg-light text-muted'>"._("Unknown")."</span>";
			}

			// status icons
			$status_icons = "";
			$recipients = array_filter(explode(";", $h->h_recipients));
			$status_icons .= sizeof($recipients)>0 ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user text-info"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg> ' : '';
			$status_icons .= $h->mute=="1" ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-volume-3 text-red"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /><path d="M16 10l4 4m0 -4l-4 4" /></svg> ' : '';
			$status_icons .= $h->ignore=="1" ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-rosette-discount-check-off text-red"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 12l2 2l1.5 -1.5m2 -2l.5 -.5" /><path d="M8.887 4.89a2.2 2.2 0 0 0 .863 -.53l.7 -.7a2.2 2.2 0 0 1 3.12 0l.7 .7c.412 .41 .97 .64 1.55 .64h1a2.2 2.2 0 0 1 2.2 2.2v1c0 .58 .23 1.138 .64 1.55l.7 .7a2.2 2.2 0 0 1 0 3.12l-.7 .7a2.2 2.2 0 0 0 -.528 .858m-.757 3.248a2.193 2.193 0 0 1 -1.555 .644h-1a2.2 2.2 0 0 0 -1.55 .64l-.7 .7a2.2 2.2 0 0 1 -3.12 0l-.7 -.7a2.2 2.2 0 0 0 -1.55 -.64h-1a2.2 2.2 0 0 1 -2.2 -2.2v-1a2.2 2.2 0 0 0 -.64 -1.55l-.7 -.7a2.2 2.2 0 0 1 0 -3.12l.7 -.7a2.2 2.2 0 0 0 .64 -1.55v-1c0 -.604 .244 -1.152 .638 -1.55" /><path d="M3 3l18 18" /></svg> ' : '';
			$h->status_icons = $status_icons;

			// build hostname link
			$h->hostname_html = "<strong><a style='color:var(--tblr-body-color)' href='/".$h->tenant_href."/zones/".$h->zone_name."/".$h->hostname."/'>".$h->hostname."</a></strong> ".$status_icons."<br><span class='text-muted' style='font-weight:normal;font-size:11px;'>".$h->ip_formatted."</span>";

			// domain/issuer column
			$h->domain_issuer = $h->domain."<br><span class='text-muted'>".$h->issuer."</span>";

			// checked/changed column
			$h->checked_changed = "<span class='text-secondary' style='font-size:11px;'>".$h->last_check_formatted."<br>".$h->last_change_formatted."</span>";

			// port column - combined with portgroup
			$h->port_group_name = isset($all_port_groups[$h->t_id][$h->pg_id]['name']) ? $all_port_groups[$h->t_id][$h->pg_id]['name'] : "/";
			$h->port_html = "<span class='badge'>".$h->port_formatted."</span><br><span class='text-secondary' style='font-size:11px;'>".$h->port_group_name."</span>";

			// actions dropdown
			$h->actions = '<span class="dropdown">
				<button class="btn btn-sm dropdown-toggle align-text-top" data-bs-boundary="viewport" data-bs-toggle="dropdown" aria-expanded="false"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-settings"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg></button>
				<div class="dropdown-menu dropdown-menu-en1d">
					<h6 class="dropdown-header">'._('Notifications').'</h6>
					<a class="dropdown-item" href="/route/modals/zones/host-set-recipients.php?tenant='.$h->tenant_href.'&zone_id='.$h->z_id.'&host_id='.$h->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-cog dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h2.5" /><path d="M17.001 19a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M19.001 15.5v1.5" /><path d="M19.001 21v1.5" /><path d="M22.032 17.25l-1.299 .75" /><path d="M17.27 20l-1.3 .75" /><path d="M15.97 17.25l1.3 .75" /><path d="M20.733 20l1.3 .75" /></svg>'._("Manage recipients").'</a>'.
					($h->mute=="1" ?
					'<a class="dropdown-item" href="/route/modals/zones/host_ignore_mute.php?type=mute&tenant='.$h->tenant_href.'&zone_id='.$h->z_id.'&host_id='.$h->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-volume-3 dropdown-item-icon text-red"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /><path d="M16 10l4 4m0 -4l-4 4" /></svg>
					'._("Enable notification").'</a>' :
					'<a class="dropdown-item" href="/route/modals/zones/host_ignore_mute.php?type=mute&tenant='.$h->tenant_href.'&zone_id='.$h->z_id.'&host_id='.$h->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-volume dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8a5 5 0 0 1 0 8" /><path d="M17.7 5a9 9 0 0 1 0 14" /><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /></svg>'._("Disable notification").'</a>').
					($h->ignore=="1" ?
					'<a class="dropdown-item" href="/route/modals/zones/host_ignore_mute.php?type=ignore&tenant='.$h->tenant_href.'&zone_id='.$h->z_id.'&host_id='.$h->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-rosette-discount-check-off dropdown-item-icon text-red"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 12l2 2l1.5 -1.5m2 -2l.5 -.5" /><path d="M8.887 4.89a2.2 2.2 0 0 0 .863 -.53l.7 -.7a2.2 2.2 0 0 1 3.12 0l.7 .7c.412 .41 .97 .64 1.55 .64h1a2.2 2.2 0 0 1 2.2 2.2v1c0 .58 .23 1.138 .64 1.55l.7 .7a2.2 2.2 0 0 1 0 3.12l-.7 .7a2.2 2.2 0 0 0 -.528 .858m-.757 3.248a2.193 2.193 0 0 1 -1.555 .644h-1a2.2 2.2 0 0 0 -1.55 .64l-.7 .7a2.2 2.2 0 0 1 -3.12 0l-.7 -.7a2.2 2.2 0 0 0 -1.55 -.64h-1a2.2 2.2 0 0 1 -2.2 -2.2v-1a2.2 2.2 0 0 0 -.64 -1.55l-.7 -.7a2.2 2.2 0 0 1 0 -3.12l.7 -.7a2.2 2.2 0 0 0 .64 -1.55v-1c0 -.604 .244 -1.152 .638 -1.55" /><path d="M3 3l18 18" /></svg>'._("Disable SSL check").'</a>' :
					'<a class="dropdown-item" href="/route/modals/zones/host_ignore_mute.php?type=ignore&tenant='.$h->tenant_href.'&zone_id='.$h->z_id.'&host_id='.$h->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-rosette-discount-check dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 7.2a2.2 2.2 0 0 1 2.2 -2.2h1a2.2 2.2 0 0 0 1.55 -.64l.7 -.7a2.2 2.2 0 0 1 3.12 0l.7 .7c.412 .41 .97 .64 1.55 .64h1a2.2 2.2 0 0 1 2.2 2.2v1c0 .58 .23 1.138 .64 1.55l.7 .7a2.2 2.2 0 0 1 0 3.12l-.7 .7a2.2 2.2 0 0 0 -.64 1.55v1a2.2 2.2 0 0 1 -2.2 2.2h-1a2.2 2.2 0 0 0 -1.55 .64l-.7 .7a2.2 2.2 0 0 1 -3.12 0l-.7 -.7a2.2 2.2 0 0 0 -1.55 -.64h-1a2.2 2.2 0 0 1 -2.2 -2.2v-1a2.2 2.2 0 0 0 -.64 -1.55l-.7 -.7a2.2 2.2 0 0 1 0 -3.12l.7 -.7a2.2 2.2 0 0 0 .64 -1.55v-1c0 -.604 .244 -1.152 .638 -1.55" /><path d="M9 12l2 2l4 -4" /></svg>'._("Enable SSL check").'</a>').
					'<div class="dropdown-divider"></div>
					<h6 class="dropdown-header">'._('Manage').'</h6>
					<a class="dropdown-item" href="/route/modals/zones/host-assign-portgroup.php?tenant='.$h->tenant_href.'&zone_id='.$h->z_id.'&host_id='.$h->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-category dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4h6v6h-6l0 -6" /><path d="M14 4h6v6h-6l0 -6" /><path d="M4 14h6v6h-6l0 -6" /><path d="M14 17a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /></svg>'._("Assign portgroup").'</a>
					<a class="dropdown-item" href="/route/modals/zones/host_cert_refresh.php?tenant='.$h->tenant_href.'&zone_id='.$h->z_id.'&host_id='.$h->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-refresh dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>'._("Refresh certificate").'</a>
					<a class="dropdown-item" href="/route/modals/zones/delete_hostname.php?tenant='.$h->tenant_href.'&zone_id='.$h->z_id.'&host_id='.$h->id.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>'._("Delete host").'</a>'.
					(is_numeric($h->cert_serial) || strlen($h->cert_serial) > 0 ?
					'<a class="dropdown-item" href="/route/modals/certificates/delete.php?tenant='.$h->tenant_href.'&serial='.$h->cert_serial.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-certificate-off dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12.876 12.881a3 3 0 0 0 4.243 4.243m.588 -3.42a3.012 3.012 0 0 0 -1.437 -1.423" /><path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5" /><path d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2m4 0h10a2 2 0 0 1 2 2v10" /><path d="M6 9h3m4 0h5" /><path d="M6 12h3" /><path d="M6 15h2" /><path d="M3 3l18 18" /></svg>'._("Delete certificate").'</a>' : '').
				'</div>
			</span>';

			// tenant (for search results)
			$h->tenant = isset($h->tenant_name) && strlen($h->tenant_name)>0 ? $h->zone_name." / ".$h->tenant_name : "---";

			// remove unneeded fields
			unset($h->certificate);
			unset($h->h_recipients);
			unset($h->z_id);
			unset($h->t_id);
			unset($h->pg_id);
			unset($h->tenant_href);
			unset($h->zone_name);
			unset($h->tenant_name);
		}
	}

	// result
	$result = [];
	$result['total']            = $hosts_all->cnt;
	$result['totalNotFiltered'] = sizeof($hosts);
	$result['rows']             = (array) $hosts;

	header('HTTP/1.1 200 OK');
	print json_encode($result);

}
catch (Exception $e) {

	// result
	$result = [];
	$result['total']            = 0;
	$result['totalNotFiltered'] = 0;
	$result['rows']             = [];
	$result['error']            = $e->getMessage();

	header('HTTP/1.1 500 Internal Server Error');
	print json_encode($result);
}

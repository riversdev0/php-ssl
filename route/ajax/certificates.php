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

	// get user days setting (user DB value > config > default)
	$expire_days = isset($user->days) ? $user->days : (isset($expired_days) ? $expired_days : 30);

	// formulate query - fetch certificate details with hosts
	$query 	   .= "select c.id, c.serial, c.expires, c.z_id, c.t_id, c.certificate, z.name as zone_name, t.href as tenant_href from certificates c left join zones z on c.z_id = z.id left join tenants t on c.t_id = t.id left join hosts h on c.id = h.c_id where 1=1 ";
	$query_all .= "select count(distinct c.id) as cnt from certificates c left join zones z on c.z_id = z.id left join tenants t on c.t_id = t.id left join hosts h on c.id = h.c_id where 1=1 ";

	// not admin ?
	if($user->admin=="0") {
		$query        .= " and c.t_id = :t_id";
		$query_all    .= " and c.t_id = :t_id";
		$vars['t_id'] = $user->t_id;
	}
	// search ?
	if(strlen($_POST['search'])>0) {
		$query         .= " and (c.serial like :search or z.name like :search or c.expires like :search or h.hostname like :search or h.ip like :search)";
		$query_all     .= " and (c.serial like :search or z.name like :search or c.expires like :search or h.hostname like :search or h.ip like :search)";
		$vars['search']  = "%".$_POST['search']."%";
	}

	// filter ?
	$filter = isset($_POST['filter']) ? $_POST['filter'] : "list";
	if($filter=="expired") {
		$query         .= " and c.expires < NOW()";
		$query_all     .= " and c.expires < NOW()";
	}
	elseif($filter=="expire_soon") {
		$query         .= " and c.expires >= NOW() and c.expires <= DATE_ADD(NOW(), INTERVAL :expire_days DAY)";
		$query_all     .= " and c.expires >= NOW() and c.expires <= DATE_ADD(NOW(), INTERVAL :expire_days DAY)";
		$vars['expire_days'] = $expire_days;
	}
	elseif($filter=="orphaned") {
		# orphaned = certificate not linked to any host
		$query         .= " and c.id not in (select distinct c_id from hosts where c_id is not null)";
		$query_all     .= " and c.id not in (select distinct c_id from hosts where c_id is not null)";
	}

	// order, sort
	if (strlen($_POST['sort'])>0 && in_array($_POST['sort'], ['id','serial','expires','created','zone_name'])) {
		$query .= " order by ".$_POST['sort']." ";
		$query .= $_POST['order']=="desc" ? "desc" : "asc";
	}
	else {
		$query .= " order by c.id desc ";
	}
	// limit
	if(is_numeric($_POST['limit'])) {
		$query .= " limit ".(int)$_POST['limit'];
	}
	// offset
	if(is_numeric($_POST['offset'])) {
		$query .= " offset  ".(int)$_POST['offset'];
	}

	// fetch
	try {
		$certificates     = $Database->getObjectsQuery ($query, $vars);
		$certificates_all = $Database->getObjectQuery ($query_all, $vars);
	} catch (Exception $e) {
		$result = [];
		$result['total']            = 0;
		$result['totalNotFiltered'] = 0;
		$result['rows']             = [];
		$result['error']            = $e->getMessage();
		header('HTTP/1.1 200 OK');
		print json_encode($result);
		exit;
	}

	// tenants
	$all_tenants = $Tenants->get_all ();
	$all_zones = $Zones->get_all ();

	// init Certificates class
	$Certificates = new Certificates ($Database);

	// we need to reformat now
	$user_href = isset($_POST['href']) ? $_POST['href'] : "";

	if(sizeof($certificates)>0) {
		foreach ($certificates as $c) {
			// parse certificate
			$cert_parsed = $Certificates->parse_cert ($c->certificate);

			// get status
			$status = $Certificates->get_status ($cert_parsed, true);
			$status_int = $Certificates->get_status_int ($cert_parsed);

			// text class
			$danger_class = "";
			if($status_int==0)		{ $textclass=''; }
			elseif($status_int==1)	{ $textclass='red';  $danger_class = "red"; }
			elseif($status_int==2)	{ $textclass='orange'; $danger_class = "orange";  }
			elseif($status_int==3)	{ $textclass='green'; }
			else 					{ $textclass=''; }


			// common name
			$common_name = isset($cert_parsed['subject']['CN']) ? $cert_parsed['subject']['CN'] : "/";
			if(is_array($common_name)) { $common_name = $common_name[0]; }

			// issuer
			$issuer = isset($cert_parsed['issuer']['O']) ? $cert_parsed['issuer']['O'] : (isset($cert_parsed['issuer']['CN']) ? $cert_parsed['issuer']['CN'] : "/");

			// days valid
			$days_valid = isset($cert_parsed['custom_validDays']) ? $cert_parsed['custom_validDays'] : "/";

			// save t_id before unsetting
			$t_id = $c->t_id;
			$z_id = $c->z_id;

			// remove id - not needed
			unset($c->id);
			unset($c->certificate);
			unset($c->z_id);
			unset($c->t_id);

			// add new fields
			$c->status = $status['text'];
			$c->common_name = $common_name;

			$c->days_valid = $days_valid;

			// tenant name for admins - use saved t_id
			if($user->admin=="1") {
				$c->tid = isset($all_tenants[$t_id]) ? $all_tenants[$t_id]->name : $t_id;
			}
			// zone name and tenant href from query - use saved z_id
			$zone_name = isset($c->zone_name) ? $c->zone_name : "/";
			$tenant_href = isset($c->tenant_href) ? $c->tenant_href : $user_href;

			// expiry status
			if($c->days_valid < 0) {
				$c->days_valid = "<span class='badge text-red'>".$c->days_valid."</span>";
			}
			elseif($c->days_valid <= $expire_days) {
				$c->days_valid = "<span class='badge text-yellow'>".$c->days_valid."</span>";
			}
			else {
				$c->days_valid = "<span class='badge text-green'>".$c->days_valid."</span>";
			}
			// expires date
			$c->expires = "<span class='text-secondary'>".date("Y-m-d H:i:s", strtotime($c->expires))."</span>";
			// issued
			$c->issued_by = "<span class='text-secondary'>$issuer</span>";

			// zone link
			$c->zone = "<a href='/".$tenant_href."/zones/".$zone_name."/' target='_blank'>".$zone_name."</a>";

			// serial link
			if($cert_parsed['serialNumberHex']!="/") {
				$len = strlen($cert_parsed['serialNumberHex']);
				$c->serial = "<a class='btn btn-sm text-info text-$danger_class' href='/".$tenant_href."/certificates/".$zone_name."/".$cert_parsed['serialNumber']."/'>".$cert_parsed['serialNumberHex']."</a>";
			}
		}
	}

	// result
	$result = [];
	$result['total']            = $certificates_all->cnt;
	$result['totalNotFiltered'] = sizeof($certificates);
	$result['rows']             = (array) $certificates;

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

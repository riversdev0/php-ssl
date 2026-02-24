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

	// throw new exception ("cipa");

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

	// formulate query
	$query 	   .= "select `id`,`object_u_id` as `user`,`date`,`object`,`action`,`text`,`json_object_old`,`json_object_new`,`object_t_id` as tid from logs where 1=1 ";
	$query_all .= "select count(*) as cnt from logs where 1=1 ";

	// not admin ?
	if($user->admin=="0") {
		$query        .= " and object_t_id = :t_id";
		$query_all    .= " and object_t_id = :t_id";
		$vars['t_id'] = $user->t_id;
	}
	// search ?
	if(strlen($_POST['search'])>0) {
		$query         .= " and (object = :search2 or text like :search or json_object_old like :search or json_object_new like :search or date like :search)";
		$query_all     .= " and (object = :search2 or text like :search or json_object_old like :search or json_object_new like :search or date like :search)";
		$vars['search2'] = "%".$_POST['search']."%";
		$vars['search']  = "%".$_POST['search']."%";
	}
	// object filter ?
	if(strlen($_POST['object'])>0) {
		// If serial is provided for certificates, also search in host logs
		if($_POST['object'] === 'certificates' && strlen($_POST['serial']) > 0) {
			$query        .= " and ((object = :object and object_id = :object_id) or text like :serial)";
			$query_all   .= " and ((object = :object and object_id = :object_id) or text like :serial)";
			$vars['serial'] = '%'.$_POST['serial'].'%';
		} else {
			$query        .= " and object = :object";
			$query_all   .= " and object = :object";
		}
		$vars['object'] = $_POST['object'];
	}
	// object_id filter ?
	if(is_numeric($_POST['object_id'])) {
		// Skip object_id filter if serial is provided (already handled in object filter)
		if(!($_POST['object'] === 'certificates' && strlen($_POST['serial']) > 0)) {
			$query        .= " and object_id = :object_id";
			$query_all   .= " and object_id = :object_id";
		}
		$vars['object_id'] = $_POST['object_id'];
	}
	// order, sort
	if (strlen($_POST['sort'])>0 && in_array($_POST['sort'], ['id','user','object','date','text'])) {
		$query .= " order by ".$_POST['sort']." ";
		$query .= $_POST['order']=="desc" ? "desc" : "asc";
	}
	else {
		$query .= " order by id desc ";
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
	$logs     = $Database->getObjectsQuery ($query, $vars);
	$logs_all = $Database->getObjectQuery ($query_all, $vars);

	// tenants
	$all_tenants = $Tenants->get_all ();

	// we need to reformat now
	if(sizeof($logs)>0) {
		# fetch users
		$users = $User->get_all ();
		# format
		foreach ($logs as $l) {
			// all
			$l = $Log->format_log_entry ($l, $user);
			// add user
			$l->user = isset($users[$l->user]) ? $users[$l->user]->name : "System";

			// remove diffs
			unset($l->json_object_old);
			unset($l->json_object_new);
			// remove tid for non-admins
			if($user->admin!=="1") {
				unset ($l->tid);
			}
			else {
				$l->tid = $all_tenants[$l->tid]->name;
			}
		}
	}

	// result
	$result = [];
	$result['debug']  			= $query;
	$result['query']  			= $_POST;
	$result['total']            = $logs_all->cnt;
	$result['totalNotFiltered'] = sizeof($logs);
	$result['rows']             = (array) $logs;

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
	// header('HTTP/1.1 200 OK');
	print json_encode($result);
}


// print $query;
// print_r($vars);
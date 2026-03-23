<?php

/**
 *
 * Remove orphaned certificates
 *
 * $j object is passed to scrip :: $j->t_id is tenant id
 *
 */

# load classes
$Result   = new Result ();
$Common   = new Common ();
$URL      = new URL ();
$Database = new Database_PDO ();
$SSL      = new SSL ($Database);


# script can only be run from cli
if(php_sapi_name()!="cli") {
	$Common->errors[] = "This script can only be run from cli!";
	$Common->result_die ();
}

# save tenant id
$tenant_id = $j->t_id;

#
# execute
#
try {
	# remove
	$certificates = $Database->runQuery("delete FROM certificates WHERE `t_id` = ? and is_manual = 0 and id NOT IN (SELECT c_id from hosts where c_id is not NULL)", [$tenant_id]);
} catch (Exception $e) {
    // print error
	$Common->errors[] = $e->getMessage();
	$Common->show_cli ($Common->get_last_error());
}
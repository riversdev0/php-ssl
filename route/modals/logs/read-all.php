<?php

#
# Edit zone - submit
#


try {
	# functions
	require('../../../functions/autoload.php');
	# validate user session
	$User->validate_session (true, true, true);

	// validate numeric
	if(!is_numeric($_GET['id'])) {
		throw new Exception ("Invalid id");
	}
	// Update
	$User->read_all_logs();
	// header
	header("HTTP/1.1 200 OK");
	// print
	print _("Messages marked as read");
}
catch (Exception $e) {
	// header
	header("HTTP/1.1 400 Bad request");
	// print
	print $e->getMessage();
}
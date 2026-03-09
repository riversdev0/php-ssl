<?php

#
# Truncate - submit
#


# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session(true, true, true);
$User->validate_csrf_token ();
# validate permissions
$User->validate_user_permissions(3, true);




# formulate tenants
$ids = [];

foreach ($_POST as $k => $p) {
	if (strpos($k, "tenant") == 0 && $p == "on") {
		$ids[] = substr($k, 7);
	}
}



# ok, validations passed, truncate
try {

	# make sure we have some
	if (sizeof($ids) == 0) {
		throw new Exception('No tenant selected');
	}

	# validate ids for non-admin
	if ($user->admin !== "1") {
		foreach ($ids as $id) {
			if ($user->t_id !== $id) {
				throw new Exception('Invalid tenant');
			}
		}
	}

	# execute
	if ($Log->truncate_logs($ids) === false) {
		$Result->show("danger", $Log->errors, true, false, false, false);
	}
	else {
		$Result->show("success", _("Logs truncated"), false, false, false, false);
		// Write log :: object, object_id, tenant_id, user_id, action, public, text
		$Log->write ("logs", 0, $user->t_id, $user->id, "truncate", true, "Logs truncated", NULL, NULL);
	}
}
catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}
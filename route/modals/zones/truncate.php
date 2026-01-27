<?php

#
# Edit zone - truncate
#



# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, false);
# validate permissions
$User->validate_user_permissions (3, true);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant (false, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# fetch zone
$zone = $Zones->get_zone ($_GET['tenant'], $_GET['zone_id']);
# fetch hosts
$zone_hosts = $Zones->get_zone_hosts ($_GET['zone_id']);


# title
$title = _("Zone hosts removal");

# ok, validations passed, insert
try {
	// delete
	$Database->runQuery("delete from hosts where z_id = ?", $_GET['zone_id']);
	// ok
	$content[] = $Result->show("success", _("All hosts in zone removed").".", false, false, true, true);
	// header
	$header_class = "success";
	// Write log :: object, object_id, tenant_id, user_id, action, public, text
	$Log->write ("zones", $_GET['zone_id'], $user->t_id, $user->id, "truncate", true, "Zone truncated");

} catch (Exception $e) {
	// error
	$content[] = $Result->show("danger", $e->getMessage().".", false, false, true, true);
	// header
	$header_class = "danger";
}

// modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "", true, $header_class);
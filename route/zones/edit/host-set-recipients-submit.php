<?php

#
# Commit additional mail recipients
#



# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session ();
# validate permissions
$User->validate_user_permissions (2, true);

# validate tenant
$_params['tenant'] = $_POST['tenant'];
$User->validate_tenant (false, true);

# strip tags
$_POST = $User->strip_input_tags ($_POST);

# fetch items
$tenant = $Tenants->get_tenant_by_href ($_POST['tenant']);
$zone   = $Zones->get_zone ($_POST['tenant'], $_POST['zone_id']);
$host   = $Zones->get_host ($_POST['host_id']);

# validate zone id
if($Common->validate_int($_POST['zone_id'])===false || $zone===null) {
	$Result->show("danger", _("Invalid zone").".", true, false, false, false);
}
# validate tenant
elseif($tenant===null) {
	$Result->show("danger", _("Invalid tenant").".", true, false, false, false);
}
# invalid host
elseif ($host===null || $host->z_id!=$zone->id) {
	# content
	$content = [];
	$content[] = $Result->show("danger", _("Invalid host"), false, false, true);
	# btn
	$btn_text = "";
}

# create hosts to insert
foreach ($_POST as $k=>$p) {
	if (strpos($k, "hostname-")!==false) {
		if($Common->validate_mail($p) || strlen($p)===0) {
			$out[] = $p;
		}
		else {
			$Result->show("danger", _("Invalid email address").".", true, false, false, false);
		}
	}
}



# ok, validations passed, insert
try {
	foreach ($out as $o) {
		$Database->updateObject("hosts", ["id"=>$_POST['id'], "h_recipients"=>implode(";", $out)]);
	}
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}

# ok
$Result->show("success", _("Recipients updated").".", false, false, false, false);
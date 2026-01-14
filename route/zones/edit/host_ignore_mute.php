<?php

#
# Ignore host
#



# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session ();
# validate permissions
$User->validate_user_permissions (2, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant (false, true);

# get tenant
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);

// title
$title = $_GET['type']=="ignore" ? _("Ignore host check") : _("Mute host");
// content
$content = [];

# try to fetch certificate
try {
    // update
    if($_GET['type']=='ignore')
	$Database->runQuery("update hosts set `ignore` = IF(`ignore`=1, 0, 1) where id = ?", [$_GET['host_id']]);
	else
	$Database->runQuery("update hosts set `mute` = IF(`mute`=1, 0, 1) where id = ?", [$_GET['host_id']]);
	// ok
	$content[] = $Result->show("success", _("Updated").".", false, false, true, false);
	$header_class = "success";
} catch (Exception $e) {
    // print error
	$content[] = $Result->show("danger", $e->getMessage(), false, false, true, false);
	$header_class = "danger";
}
# print modal
$Modal->modal_print ($title, implode("\n", $content), "", "", true, $header_class);
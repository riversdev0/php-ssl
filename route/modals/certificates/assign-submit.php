<?php

#
# Assign certificate to host - submit
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, true);
$User->validate_csrf_token ();
# validate permissions
$User->validate_user_permissions (2, true);

# validate tenant
$_params['tenant'] = $_POST['tenant'];
$User->validate_tenant (false, true);

# strip tags
$_POST = $User->strip_input_tags ($_POST);

# validate host_id
if(!$Common->validate_int($_POST['host_id']))
$Result->show("danger", _("Invalid host") . ".", true, false, false, false);

# validate certificate_id
if(!$Common->validate_int($_POST['certificate_id']))
$Result->show("danger", _("Invalid certificate") . ".", true, false, false, false);

# fetch host
$host = $Database->getObject("hosts", $_POST['host_id']);
if(is_null($host))
$Result->show("danger", _("Invalid host") . ".", true, false, false, false);

# fetch tenant
$tenant = $Tenants->get_tenant_by_href ($_POST['tenant']);
if(is_null($tenant))
$Result->show("danger", _("Invalid tenant") . ".", true, false, false, false);

# fetch certificate — must belong to the same tenant
$cert = $Database->getObject("certificates", $_POST['certificate_id']);
if(is_null($cert) || $cert->t_id != $tenant->id)
$Result->show("danger", _("Invalid certificate") . ".", true, false, false, false);

# assign certificate to host
try {
	$old_host = clone $host;
	$Database->updateObject("hosts", ["id" => $host->id, "c_id" => $cert->id]);
	# Write log :: object, object_id, tenant_id, user_id, action, public, text
	$Log->write ("hosts", $host->id, $tenant->id, $user->id, "edit", true, "Certificate " . $cert->serial . " assigned to host " . $host->hostname, json_encode(["hosts" => ["0" => $old_host]]), NULL, true);
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}

$Result->show("success", _("Certificate assigned."), false, false, false, true);

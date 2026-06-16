<?php

#
# Nmap network scan — form submission
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session(false, true, true);
$User->validate_csrf_token();
# RWA or system admin required
$User->validate_user_permissions(3, false);

# validate tenant
$_params['tenant'] = $_POST['tenant'];
$User->validate_tenant(false, true);

# strip tags
$_POST = $User->strip_input_tags($_POST);

# fetch zone
$zone   = $Zones->get_zone($_POST['tenant'], (int) $_POST['zone_id']);
$tenant = $Tenants->get_tenant_by_href($_POST['tenant']);

if ($zone === null) {
    $Result->show("danger", _("Invalid zone") . ".", true, false, false, false);
}
if ($tenant === null) {
    $Result->show("danger", _("Invalid tenant") . ".", true, false, false, false);
}

# validate prefix
$prefix = trim($_POST['prefix'] ?? '');
if (!Nmap::validate_prefix($prefix)) {
    $Result->show("danger", _("Invalid prefix. Use IPv4 CIDR with prefix length between /16 and /32 (e.g. 10.0.0.0/24)."), true, false, false, false);
}

# validate port group
$pg_id = (int) ($_POST['pg_id'] ?? 0);
if ($pg_id < 1) {
    $Result->show("danger", _("Invalid port group."), true, false, false, false);
}
# confirm port group belongs to this tenant
$ports_all = $SSL->get_all_port_groups();
if (empty($ports_all[$tenant->id][$pg_id])) {
    $Result->show("danger", _("Invalid port group."), true, false, false, false);
}

$ptr_lookup = !empty($_POST['ptr_lookup']) && $_POST['ptr_lookup'] === '1';

# validate optional notify_email
$notify_email = trim($_POST['notify_email'] ?? '');
if ($notify_email !== '' && !filter_var($notify_email, FILTER_VALIDATE_EMAIL)) {
    $Result->show("danger", _("Invalid notification email address."), true, false, false, false);
}
$notify_email = $notify_email !== '' ? $notify_email : null;

# check nmap availability
$Nmap = new Nmap($Database);
if (!file_exists($Nmap->nmap_path) || !is_executable($Nmap->nmap_path)) {
    $Result->show("danger", _("nmap binary not found. Contact your administrator."), true, false, false, false);
}

# insert scan request
try {
    $Nmap->request_scan(
        (int) $tenant->id,
        (int) $zone->id,
        (int) $user->id,
        $prefix,
        $pg_id,
        $ptr_lookup,
        $notify_email
    );
    $Result->show("success", _("Scan requested. Results will be added to this zone after the next cron run."), false, false, false, false);
} catch (Exception $e) {
    $Result->show("danger", $e->getMessage(), true, false, false, false);
}

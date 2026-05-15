<?php
require('../../../functions/autoload.php');
$User->validate_session(false, true, false);
$User->validate_csrf_token();

$is_admin     = $user->admin === "1";
$hostname     = trim($_POST['hostname'] ?? '');
$port         = isset($_POST['port']) && is_numeric($_POST['port']) ? (int)$_POST['port'] : 443;
$tenant_id    = isset($_POST['tenant_id']) && is_numeric($_POST['tenant_id']) ? (int)$_POST['tenant_id'] : (int)$user->t_id;
$notify_email = trim($_POST['notify_email'] ?? '');

// Non-admins can only submit for their own tenant
if (!$is_admin) {
    $tenant_id = (int)$user->t_id;
}

// Validate hostname (reuse existing validator)
$hostname_check = str_replace(['http://', 'https://'], '', $hostname);
if (!$User->validate_url('http://' . $hostname_check) && !$User->validate_ip($hostname_check) && !preg_match('/^[a-zA-Z0-9._-]+$/', $hostname)) {
    $Result->show('danger', _("Invalid hostname."), true, true);
}

// Validate port range
if ($port < 1 || $port > 65535) {
    $Result->show('danger', _("Invalid port."), true, true);
}

// Validate email if provided
if ($notify_email !== '' && !filter_var($notify_email, FILTER_VALIDATE_EMAIL)) {
    $Result->show('danger', _("Invalid email address."), true, true);
}

$TestSSL = new TestSSL($Database);

try {
    $id = $TestSSL->create($hostname, $port, $tenant_id, (int)$user->id, $notify_email !== '' ? $notify_email : null);
    $Result->show('success', _("Scan requested successfully. It will start on the next cron run."), true, true);
} catch (Exception $e) {
    $Result->show('danger', $e->getMessage(), true, true);
}

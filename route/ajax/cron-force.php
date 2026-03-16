<?php

/**
 * Toggle force execution flag for a cron job.
 * Admin or same-tenant users with permission level 3+, XHR POST only.
 */

require('../../functions/autoload.php');

header('Content-Type: application/json');

// XHR POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
	http_response_code(405);
	print json_encode(['success' => false, 'error' => 'Invalid request']);
	exit;
}

// Require session
$User->validate_session();

// strip
$_POST = $User->strip_input_tags($_POST);

$cron_id = intval($_POST['id'] ?? 0);
if ($cron_id <= 0) {
	http_response_code(400);
	print json_encode(['success' => false, 'error' => 'Invalid cron ID']);
	exit;
}

$cronjob = $Database->getObject("cron", $cron_id);
if ($cronjob === null) {
	http_response_code(404);
	print json_encode(['success' => false, 'error' => 'Cron job not found']);
	exit;
}

// Permission: admin or same tenant
if ($user->admin !== '1' && $user->t_id != $cronjob->t_id) {
	http_response_code(403);
	print json_encode(['success' => false, 'error' => 'Access denied']);
	exit;
}

// Toggle force
$new_force = ($cronjob->force == 1) ? 0 : 1;
$ok = $Cron->set_force_execution($cron_id, $new_force);

print json_encode(['success' => $ok, 'force' => $new_force]);

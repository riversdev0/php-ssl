<?php

/**
 * AJAX: update ignore_updates / ignore_expiry flags on a CA.
 * POST: ca_id, ignore_updates (0|1), ignore_expiry (0|1)
 */

ob_start();
require('../../../functions/autoload.php');
ob_clean();
header('Content-Type: application/json');

$User->validate_session(false, false, false);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    print json_encode(['status' => 'error', 'message' => _("Invalid request.")]);
    exit;
}

if ((int)$user->permission < 3 && $user->admin !== "1") {
    print json_encode(['status' => 'error', 'message' => _("Permission denied.")]);
    exit;
}

$ca_id          = (int)($_POST['ca_id']          ?? 0);
$ignore_updates = (int)($_POST['ignore_updates']  ?? 0) ? 1 : 0;
$ignore_expiry  = (int)($_POST['ignore_expiry']   ?? 0) ? 1 : 0;

if (!$ca_id) {
    print json_encode(['status' => 'error', 'message' => _("Invalid CA.")]);
    exit;
}

// Fetch CA with tenant scope check
if ($user->admin === "1") {
    $ca = $Database->getObjectQuery("SELECT id FROM cas WHERE id = ?", [$ca_id]);
} else {
    $ca = $Database->getObjectQuery("SELECT id FROM cas WHERE id = ? AND t_id = ?", [$ca_id, (int)$user->t_id]);
}

if (!$ca) {
    print json_encode(['status' => 'error', 'message' => _("CA not found.")]);
    exit;
}

$Database->runQuery(
    "UPDATE cas SET ignore_updates = ?, ignore_expiry = ? WHERE id = ?",
    [$ignore_updates, $ignore_expiry, $ca_id]
);

print json_encode(['status' => 'ok', 'message' => _("Saved.")]);

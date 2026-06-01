<?php

/**
 * Delete a CA.
 * POST JSON: { ca_id }
 */

ob_start();
require('../../../functions/autoload.php');
ob_clean();
header('Content-Type: application/json');

$User->validate_session(false, false, false);
$User->validate_user_permissions(3);

$body  = json_decode(file_get_contents('php://input'), true);
$ca_id = (int)($body['ca_id'] ?? 0);

if ($ca_id <= 0) {
    print json_encode(['status' => 'error', 'message' => _("Invalid CA ID.")]);
    exit;
}

if ($user->admin == "1") {
    $ca = $Database->getObjectQuery("SELECT * FROM cas WHERE id = ?", [$ca_id]);
} else {
    $ca = $Database->getObjectQuery("SELECT * FROM cas WHERE id = ? AND t_id = ?", [$ca_id, $user->t_id]);
}

if (!$ca) {
    print json_encode(['status' => 'error', 'message' => _("CA not found.")]);
    exit;
}

try {
    $pkey_id = $ca->pkey_id;
    $Database->runQuery("DELETE FROM cas WHERE id = ?", [$ca_id]);
    if ($pkey_id) {
        $Database->runQuery("DELETE FROM pkey WHERE id = ?", [$pkey_id]);
    }
    $Log->write("cas", $ca_id, $ca->t_id, $user->id, "delete", false, "CA deleted: {$ca->name}");
} catch (Exception $e) {
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

print json_encode(['status' => 'ok']);

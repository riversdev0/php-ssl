<?php

/**
 * Delete a CSR record (and its orphaned pkey if no certificate uses it).
 *
 * POST JSON: { csr_id: int }
 */

ob_start();
require('../../../functions/autoload.php');
ob_clean();
header('Content-Type: application/json');

$User->validate_session(false, false, false);
# validate permissions
$User->validate_user_permissions (3, true);

$body   = json_decode(file_get_contents('php://input'), true);
$csr_id = (int)($body['csr_id'] ?? 0);

if ($csr_id <= 0) {
    http_response_code(400);
    print json_encode(['status' => 'error', 'message' => _("Invalid request.")]);
    exit;
}

if ($user->admin == "1") {
    $csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ?", [$csr_id]);
} else {
    $csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ? AND t_id = ?", [$csr_id, $user->t_id]);
}

if (!$csr) {
    http_response_code(404);
    print json_encode(['status' => 'error', 'message' => _("CSR not found.")]);
    exit;
}

try {
    $pkey_id = $csr->pkey_id;
    $Database->runQuery("DELETE FROM csrs WHERE id = ?", [$csr_id]);

    if ($pkey_id) {
        $pkey_used = $Database->getObjectQuery("SELECT id FROM certificates WHERE pkey_id = ? LIMIT 1", [$pkey_id]);
        if (!$pkey_used) {
            $Database->runQuery("DELETE FROM pkey WHERE id = ?", [$pkey_id]);
        }
    }

    $Log->write("csrs", $csr_id, $csr->t_id, $user->id, "delete", false, "CSR deleted for CN: {$csr->cn}");

    print json_encode(['status' => 'ok', 'message' => _("CSR deleted.")]);
} catch (Exception $e) {
    http_response_code(500);
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

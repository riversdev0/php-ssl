<?php

/**
 * Remove the stored private key for a certificate.
 *
 * POST JSON: { certificate_id: int }
 */

ob_start();
require('../../functions/autoload.php');
ob_clean();
header('Content-Type: application/json');

$User->validate_session(false, false, false);

$body    = json_decode(file_get_contents('php://input'), true);
$cert_id = (int) ($body['certificate_id'] ?? 0);

if ($cert_id <= 0) {
    http_response_code(400);
    print json_encode(['status' => 'error', 'message' => _("Invalid request.")]);
    exit;
}

// Fetch cert — enforce tenant access
if ($user->admin == "1") {
    $cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ?", [$cert_id]);
} else {
    $cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ? AND t_id = ?", [$cert_id, $user->t_id]);
}

if (!$cert || !$cert->pkey_id) {
    http_response_code(404);
    print json_encode(['status' => 'error', 'message' => _("Certificate or private key not found.")]);
    exit;
}

try {
    $Database->runQuery("UPDATE pkey SET private_key_enc = NULL WHERE id = ?", [$cert->pkey_id]);

    $Log->write("certificates", $cert_id, $cert->t_id, $user->id, "pkey_delete", false, "Private key removed");

    print json_encode(['status' => 'ok', 'message' => _("Private key removed.")]);
} catch (Exception $e) {
    http_response_code(500);
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

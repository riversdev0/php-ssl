<?php

/**
 * Upload and store an encrypted private key for a certificate.
 *
 * POST JSON: { certificate_id: int, pem: string }
 */

ob_start();
require('../../functions/autoload.php');
ob_clean();
header('Content-Type: application/json');

$User->validate_session(false, false, false);

$body       = json_decode(file_get_contents('php://input'), true);
$cert_id    = (int) ($body['certificate_id'] ?? 0);
$pem        = trim($body['pem'] ?? '');
$passphrase = $body['passphrase'] ?? null;

if ($cert_id <= 0 || empty($pem)) {
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

if (!$cert) {
    http_response_code(404);
    print json_encode(['status' => 'error', 'message' => _("Certificate not found.")]);
    exit;
}

// Check encryption key is configured for this tenant
global $private_key_encryption_key;
if (empty($private_key_encryption_key[$cert->t_id])) {
    http_response_code(500);
    print json_encode(['status' => 'error', 'message' => _("Private key encryption is not configured for this tenant.")]);
    exit;
}

// Validate PEM looks like a private key
if (strpos($pem, '-----BEGIN') === false || strpos($pem, 'PRIVATE KEY') === false) {
    http_response_code(422);
    print json_encode(['status' => 'error', 'message' => _("The uploaded data does not appear to be a PEM private key.")]);
    exit;
}

// Validate private key matches certificate's public key
if (!$Certificates->pkey_matches_cert($pem, $cert->certificate, $passphrase)) {
    http_response_code(422);
    print json_encode(['status' => 'error', 'message' => _("Private key does not match this certificate.")]);
    exit;
}

// Encrypt
$encrypted = $Certificates->pkey_encrypt($pem, (int) $cert->t_id);
if ($encrypted === null) {
    http_response_code(500);
    print json_encode(['status' => 'error', 'message' => _("Encryption failed.")]);
    exit;
}

try {
    if ($cert->pkey_id) {
        // Update existing pkey row
        $Database->runQuery("UPDATE pkey SET private_key_enc = ? WHERE id = ?", [$encrypted, $cert->pkey_id]);
        $pkey_id = $cert->pkey_id;
    } else {
        // Create new pkey row
        $Database->runQuery("INSERT INTO pkey (private_key_enc) VALUES (?)", [$encrypted]);
        $pkey_id = $Database->lastInsertId();
    }
    // Link all certificates with the same serial in this tenant to the same pkey
    $Database->runQuery("UPDATE certificates SET pkey_id = ? WHERE serial = ? AND t_id = ?", [$pkey_id, $cert->serial, $cert->t_id]);

    $Log->write("certificates", $cert_id, $cert->t_id, $user->id, "pkey_upload", false, "Private key uploaded");

    print json_encode(['status' => 'ok', 'message' => _("Private key stored successfully.")]);
} catch (Exception $e) {
    http_response_code(500);
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

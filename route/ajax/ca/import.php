<?php

/**
 * Import a CA/intermediate CA certificate + private key.
 * POST JSON: { name, cert_pem, key_pem, passphrase?, t_id? (admin only) }
 */

ob_start();
require('../../../functions/autoload.php');
ob_clean();
header('Content-Type: application/json');

$User->validate_session(false, false, false);
# validate permissions
$User->validate_user_permissions (3, true);

global $private_key_encryption_key;

$body = json_decode(file_get_contents('php://input'), true);

$name     = trim($body['name']     ?? '');
$cert_pem = trim($body['cert_pem'] ?? '');
$key_pem  = trim($body['key_pem']  ?? '');
$passphrase = $body['passphrase'] ?? null;

// Determine tenant
if ($user->admin === "1" && !empty($body['t_id'])) {
    $t_id = (int)$body['t_id'];
    if (!$Database->getObject("tenants", $t_id)) {
        print json_encode(['status' => 'error', 'message' => _("Invalid tenant.")]);
        exit;
    }
} else {
    $t_id = (int)$user->t_id;
}

if (empty($name)) {
    print json_encode(['status' => 'error', 'message' => _("Name is required.")]);
    exit;
}
if (empty($cert_pem)) {
    print json_encode(['status' => 'error', 'message' => _("CA certificate is required.")]);
    exit;
}
if (empty($key_pem)) {
    print json_encode(['status' => 'error', 'message' => _("CA private key is required.")]);
    exit;
}

// Require encryption configured for this tenant
if (empty($private_key_encryption_key[$t_id])) {
    print json_encode(['status' => 'error', 'message' => _("Private key encryption is not configured for this tenant.")]);
    exit;
}

// Parse and validate CA certificate
$cert_parsed = openssl_x509_parse($cert_pem);
if ($cert_parsed === false) {
    print json_encode(['status' => 'error', 'message' => _("Cannot parse certificate. Provide a valid PEM-encoded certificate.")]);
    exit;
}

// Verify it is a CA certificate
$basic_constraints = $cert_parsed['extensions']['basicConstraints'] ?? '';
if (strpos($basic_constraints, 'CA:TRUE') === false) {
    print json_encode(['status' => 'error', 'message' => _("The certificate is not a CA certificate (basicConstraints CA:TRUE missing).")]);
    exit;
}

// Parse private key
$pkey_res = @openssl_pkey_get_private($key_pem, $passphrase ?? '');
if ($pkey_res === false) {
    print json_encode(['status' => 'error', 'message' => _("Cannot parse private key. Check the passphrase if encrypted.")]);
    exit;
}

// Verify key matches certificate
if (!openssl_x509_check_private_key($cert_pem, $pkey_res)) {
    print json_encode(['status' => 'error', 'message' => _("Private key does not match the certificate.")]);
    exit;
}

// Build subject string for display
$subj = $cert_parsed['subject'] ?? [];
$subject_parts = [];
foreach (['CN', 'O', 'OU', 'C'] as $k) {
    if (!empty($subj[$k])) $subject_parts[] = $subj[$k];
}
$subject_str = implode(', ', $subject_parts);
$expires     = date('Y-m-d H:i:s', $cert_parsed['validTo_time_t']);
$serial_hex  = strtolower($cert_parsed['serialNumberHex'] ?? '');

// Encrypt and store private key
$encrypted = $Certificates->pkey_encrypt($key_pem, $t_id);
if ($encrypted === null) {
    print json_encode(['status' => 'error', 'message' => _("Private key encryption failed.")]);
    exit;
}

try {
    $Database->runQuery("INSERT INTO pkey (private_key_enc) VALUES (?)", [$encrypted]);
    $pkey_id = $Database->lastInsertId();

    $Database->runQuery(
        "INSERT INTO cas (t_id, name, certificate, pkey_id, subject, expires, serial) VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$t_id, $name, $cert_pem, $pkey_id, $subject_str ?: null, $expires, $serial_hex ?: null]
    );
    $ca_id = $Database->lastInsertId();

    $Log->write("cas", $ca_id, $t_id, $user->id, "add", false, "CA imported: {$name}");
} catch (Exception $e) {
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

print json_encode(['status' => 'ok', 'ca_id' => $ca_id]);

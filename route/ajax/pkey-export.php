<?php

/**
 * Export private key as PEM (optionally encrypted) or PKCS12.
 *
 * POST JSON: { certificate_id: int, format: 'pem'|'p12', password: string }
 * Returns:   { status: 'ok', data: base64, filename: string, mime: string }
 */

ob_start();
require('../../functions/autoload.php');
ob_clean();
header('Content-Type: application/json');

$User->validate_session(false, false, false);

$body     = json_decode(file_get_contents('php://input'), true);
$cert_id  = (int) ($body['certificate_id'] ?? 0);
$format   = $body['format'] ?? 'pem';
$password = (string) ($body['password'] ?? '');

if ($cert_id <= 0 || !in_array($format, ['pem', 'p12'], true)) {
    http_response_code(400);
    print json_encode(['status' => 'error', 'message' => _("Invalid request.")]);
    exit;
}

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

$pkey_row = $Database->getObjectQuery("SELECT * FROM pkey WHERE id = ?", [$cert->pkey_id]);
if (!$pkey_row || empty($pkey_row->private_key_enc)) {
    http_response_code(404);
    print json_encode(['status' => 'error', 'message' => _("No private key stored for this certificate.")]);
    exit;
}

$pem_key = $Certificates->pkey_decrypt($pkey_row->private_key_enc, (int) $cert->t_id);
if ($pem_key === false || empty($pem_key)) {
    http_response_code(500);
    print json_encode(['status' => 'error', 'message' => _("Decryption failed. Check that the encryption key in config.php is correct.")]);
    exit;
}

$cert_info = openssl_x509_parse($cert->certificate);
$cn        = $cert_info['subject']['CN'] ?? 'certificate';
$basename  = preg_replace('/[^a-zA-Z0-9._-]/', '_', $cn);

if ($format === 'p12') {
    $cert_res = openssl_x509_read($cert->certificate);
    $key_res  = openssl_pkey_get_private($pem_key);
    if ($cert_res === false || $key_res === false) {
        http_response_code(500);
        print json_encode(['status' => 'error', 'message' => _("Failed to parse certificate or key.")]);
        exit;
    }
    $p12_data = '';
    if (!openssl_pkcs12_export($cert_res, $p12_data, $key_res, $password)) {
        http_response_code(500);
        print json_encode(['status' => 'error', 'message' => _("Failed to generate PKCS12 file.")]);
        exit;
    }
    $Log->write("certificates", $cert_id, $cert->t_id, $user->id, "pkey_download", false, "PKCS12 exported");
    print json_encode([
        'status'   => 'ok',
        'data'     => base64_encode($p12_data),
        'filename' => $basename . '.p12',
        'mime'     => 'application/x-pkcs12',
    ]);
} else {
    if ($password !== '') {
        $key_res = openssl_pkey_get_private($pem_key);
        if ($key_res === false || !openssl_pkey_export($key_res, $exported_pem, $password)) {
            http_response_code(500);
            print json_encode(['status' => 'error', 'message' => _("Failed to encrypt private key.")]);
            exit;
        }
    } else {
        $exported_pem = $pem_key;
    }
    $Log->write("certificates", $cert_id, $cert->t_id, $user->id, "pkey_download", false, "Private key exported");
    print json_encode([
        'status'   => 'ok',
        'data'     => base64_encode($exported_pem),
        'filename' => $basename . '.key',
        'mime'     => 'application/x-pem-file',
    ]);
}

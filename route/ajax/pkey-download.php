<?php

/**
 * Download the decrypted private key for a certificate.
 *
 * GET ?cert_id=<int>
 * Serves the PEM file as a download — never rendered in HTML.
 */

ob_start();
require('../../functions/autoload.php');
ob_clean();

$User->validate_session(false, false, false);

$cert_id = (int) ($_GET['cert_id'] ?? 0);

if ($cert_id <= 0) {
    http_response_code(400);
    exit('Invalid request.');
}

// Fetch cert — enforce tenant access
if ($user->admin == "1") {
    $cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ?", [$cert_id]);
} else {
    $cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ? AND t_id = ?", [$cert_id, $user->t_id]);
}

if (!$cert || !$cert->pkey_id) {
    http_response_code(404);
    exit('Not found.');
}

$pkey = $Database->getObjectQuery("SELECT * FROM pkey WHERE id = ?", [$cert->pkey_id]);

if (!$pkey || empty($pkey->private_key_enc)) {
    http_response_code(404);
    exit('No private key stored for this certificate.');
}

$pem = $Certificates->pkey_decrypt($pkey->private_key_enc, (int) $cert->t_id);

if ($pem === false || empty($pem)) {
    http_response_code(500);
    exit('Decryption failed. Check that the encryption key in config.php is correct.');
}

// Derive filename from cert CN
$cert_info = openssl_x509_parse($cert->certificate);
$cn        = $cert_info['subject']['CN'] ?? 'certificate';
$filename  = preg_replace('/[^a-zA-Z0-9._-]/', '_', $cn) . '.key';

$Log->write("certificates", $cert_id, $cert->t_id, $user->id, "pkey_download", false, "Private key downloaded");

header('Content-Type: application/x-pem-file');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pem));
header('Cache-Control: no-store');
print $pem;

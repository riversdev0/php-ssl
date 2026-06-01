<?php

/**
 * Download the full certificate chain as a PEM file.
 *
 * GET ?cert_id=<int>
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

if ($user->admin == "1") {
    $cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ?", [$cert_id]);
} else {
    $cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ? AND t_id = ?", [$cert_id, $user->t_id]);
}

if (!$cert || empty($cert->chain)) {
    http_response_code(404);
    exit('Not found.');
}

$cert_info = openssl_x509_parse($cert->certificate);
$cn        = $cert_info['subject']['CN'] ?? 'certificate';
$filename  = preg_replace('/[^a-zA-Z0-9._-]/', '_', $cn) . '-chain.pem';

header('Content-Type: application/x-pem-file');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($cert->chain));
header('Cache-Control: no-store');
print $cert->chain;

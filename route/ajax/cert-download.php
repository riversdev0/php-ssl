<?php

/**
 * Download a certificate PEM by cert_id.
 *
 * GET ?cert_id=<int>
 */

require('../../functions/autoload.php');
$User->validate_session(false, false, false);

$cert_id = (int)($_GET['cert_id'] ?? 0);

if ($cert_id <= 0) {
    http_response_code(400);
    print _("Invalid request.");
    exit;
}

if ($user->admin == "1") {
    $cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ?", [$cert_id]);
} else {
    $cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ? AND t_id = ?", [$cert_id, $user->t_id]);
}

if (!$cert || empty($cert->certificate)) {
    http_response_code(404);
    print _("Certificate not found.");
    exit;
}

$parsed   = openssl_x509_parse($cert->certificate);
$cn       = $parsed['subject']['CN'] ?? ('cert_' . $cert_id);
$filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $cn);

header('Content-Type: application/x-pem-file');
header('Content-Disposition: attachment; filename="' . $filename . '.crt"');
print $cert->certificate;

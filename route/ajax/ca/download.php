<?php

/**
 * Download CA certificate (.crt) or decrypted private key (.key).
 *
 * GET ?ca_id=<int>&type=crt|pkey
 */

require('../../../functions/autoload.php');
$User->validate_session(false, false, false);

$ca_id = (int)($_GET['ca_id'] ?? 0);
$type  = ($_GET['type'] ?? 'crt') === 'pkey' ? 'pkey' : 'crt';

if ($ca_id <= 0) {
    http_response_code(400);
    print _("Invalid request.");
    exit;
}

if ($user->admin == "1") {
    $ca = $Database->getObjectQuery("SELECT * FROM cas WHERE id = ?", [$ca_id]);
} else {
    $ca = $Database->getObjectQuery("SELECT * FROM cas WHERE id = ? AND t_id = ?", [$ca_id, $user->t_id]);
}

if (!$ca) {
    http_response_code(404);
    print _("CA not found.");
    exit;
}

$filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $ca->name);

if ($type === 'crt') {
    header('Content-Type: application/x-pem-file');
    header('Content-Disposition: attachment; filename="' . $filename . '.crt"');
    print $ca->certificate;
    exit;
}

// Private key download — requires permission level 3+
if ($type === 'pkey') {
    $User->validate_user_permissions(3);
}

if (!$ca->pkey_id) {
    http_response_code(404);
    print _("Private key not available.");
    exit;
}

$pkey_row = $Database->getObjectQuery("SELECT * FROM pkey WHERE id = ?", [$ca->pkey_id]);
if (!$pkey_row || empty($pkey_row->private_key_enc)) {
    http_response_code(404);
    print _("Private key not available.");
    exit;
}

$key_pem = $Certificates->pkey_decrypt($pkey_row->private_key_enc, (int)$ca->t_id);
if ($key_pem === false || empty($key_pem)) {
    http_response_code(500);
    print _("Failed to decrypt private key.");
    exit;
}

header('Content-Type: application/x-pem-file');
header('Content-Disposition: attachment; filename="' . $filename . '.key"');
print $key_pem;

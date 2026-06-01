<?php

/**
 * Download CSR PEM or decrypted private key for a CSR record.
 *
 * GET ?csr_id=<int>&type=csr|pkey
 */

require('../../../functions/autoload.php');
$User->validate_session(false, false, false);

$csr_id = (int)($_GET['csr_id'] ?? 0);
$type   = ($_GET['type'] ?? 'csr') === 'pkey' ? 'pkey' : 'csr';

if ($csr_id <= 0) {
    http_response_code(400);
    print _("Invalid request.");
    exit;
}

if ($user->admin == "1") {
    $csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ?", [$csr_id]);
} else {
    $csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ? AND t_id = ?", [$csr_id, $user->t_id]);
}

if (!$csr) {
    http_response_code(404);
    print _("CSR not found.");
    exit;
}

$filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $csr->cn);

if ($type === 'csr') {
    header('Content-Type: application/pkcs10');
    header('Content-Disposition: attachment; filename="' . $filename . '.csr"');
    print $csr->csr_pem;
    exit;
}

// Private key download — requires permission level 3+
if ($type === 'pkey') {
    $User->validate_user_permissions(3);
}

if (!$csr->pkey_id) {
    http_response_code(404);
    print _("Private key not available.");
    exit;
}

$pkey_row = $Database->getObjectQuery("SELECT * FROM pkey WHERE id = ?", [$csr->pkey_id]);
if (!$pkey_row || empty($pkey_row->private_key_enc)) {
    http_response_code(404);
    print _("Private key not available.");
    exit;
}

$key_pem = $Certificates->pkey_decrypt($pkey_row->private_key_enc, (int)$csr->t_id);
if ($key_pem === false || empty($key_pem)) {
    http_response_code(500);
    print _("Failed to decrypt private key.");
    exit;
}

header('Content-Type: application/x-pem-file');
header('Content-Disposition: attachment; filename="' . $filename . '.key"');
print $key_pem;

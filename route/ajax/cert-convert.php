<?php

#
# Convert PFX (PKCS#12) binary to PEM certificate
# Used by the manual import modal to extract a certificate from an uploaded .pfx file
#

# functions
require('../../functions/autoload.php');
# validate user session
$User->validate_session(false, true, true);

header('Content-Type: application/json');

# validate CSRF
$submitted = $_POST['csrf_token'] ?? '';
$expected  = $_SESSION['csrf_token'] ?? '';
if (empty($expected) || !hash_equals($expected, $submitted)) {
	print json_encode(['error' => _("Invalid or missing CSRF token.")]);
	exit;
}

# validate upload
if (empty($_FILES['pfx_file']['tmp_name']) || $_FILES['pfx_file']['error'] !== UPLOAD_ERR_OK) {
	print json_encode(['error' => _("No file uploaded or upload error.")]);
	exit;
}

$pfx_data   = file_get_contents($_FILES['pfx_file']['tmp_name']);
$passphrase = $_POST['passphrase'] ?? '';

$pem = $SSL->pfx_to_pem($pfx_data, $passphrase);

if ($pem === false) {
	print json_encode(['error' => _("Failed to read PFX file. Check the passphrase and make sure the file is a valid PKCS#12 archive.")]);
	exit;
}

print json_encode(['pem' => $pem]);

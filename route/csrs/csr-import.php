<?php

/**
 * AJAX: import an existing CSR + private key (optional signed certificate).
 *
 * POST JSON: { csr_pem, key_pem, cert_pem? (optional), zone_id? (required with cert_pem) }
 * Returns:   { status, csr_id, cn, pkey_stored }
 *
 * Private key is stored encrypted when $private_key_encryption_key is configured.
 * If encryption is not configured the key is accepted for validation only (not stored).
 */

ob_start();
require('../../functions/autoload.php');
ob_clean();
header('Content-Type: application/json');

$User->validate_session(false, false, false);

$body    = json_decode(file_get_contents('php://input'), true);
$csr_pem        = trim($body['csr_pem']        ?? '');
$key_pem        = trim($body['key_pem']        ?? '');
$cert_pem       = trim($body['cert_pem']       ?? '');
$zone_id        = (int)($body['zone_id']       ?? 0);
$key_passphrase = $body['key_passphrase']      ?? null;

if (empty($csr_pem)) {
    print json_encode(['status' => 'error', 'message' => _("CSR is required.")]);
    exit;
}

// Require private key unless this is explicitly an external (no-key) import
if (empty($key_pem) && empty($body['external'])) {
    print json_encode(['status' => 'error', 'message' => _("Private key is required for internal CSR import.")]);
    exit;
}

// Parse CSR subject
$csr_subject = openssl_csr_get_subject($csr_pem, true);
if ($csr_subject === false) {
    print json_encode(['status' => 'error', 'message' => _("Cannot parse CSR. Please provide a valid PEM-encoded CSR.")]);
    exit;
}

$cn       = $csr_subject['CN']           ?? '';
$org      = $csr_subject['O']            ?? $csr_subject['organizationName']       ?? '';
$ou       = $csr_subject['OU']           ?? $csr_subject['organizationalUnitName'] ?? '';
$country  = $csr_subject['C']            ?? $csr_subject['countryName']            ?? '';
$state    = $csr_subject['ST']           ?? $csr_subject['stateOrProvinceName']    ?? '';
$locality = $csr_subject['L']            ?? $csr_subject['localityName']           ?? '';
$email    = $csr_subject['emailAddress'] ?? '';

if (empty($cn)) {
    print json_encode(['status' => 'error', 'message' => _("CSR has no Common Name.")]);
    exit;
}

// Get public key from CSR
$csr_pub = openssl_csr_get_public_key($csr_pem);
if ($csr_pub === false) {
    print json_encode(['status' => 'error', 'message' => _("Cannot read public key from CSR.")]);
    exit;
}
$csr_pub_details = openssl_pkey_get_details($csr_pub);

// Parse and validate private key if provided
$pkey_res     = false;
$pkey_details = [];
if (!empty($key_pem)) {
    $pkey_res = openssl_pkey_get_private($key_pem, $key_passphrase ?? '');
    if ($pkey_res === false) {
        $msg = strpos($key_pem, 'ENCRYPTED') !== false
            ? _("Cannot parse encrypted private key. Check the passphrase.")
            : _("Cannot parse private key. Ensure it is a valid PEM.");
        print json_encode(['status' => 'error', 'message' => $msg]);
        exit;
    }
    $pkey_details = openssl_pkey_get_details($pkey_res);

    // Confirm key matches CSR
    if (($csr_pub_details['key'] ?? '') !== ($pkey_details['key'] ?? '')) {
        print json_encode(['status' => 'error', 'message' => _("Private key does not match the CSR.")]);
        exit;
    }
}

// Determine key algo and size from CSR public key
$details_src = !empty($pkey_details) ? $pkey_details : $csr_pub_details;
if (($details_src['type'] ?? -1) === OPENSSL_KEYTYPE_EC) {
    $key_algo = 'EC';
    $curve    = $details_src['ec']['curve_name'] ?? 'prime256v1';
    $key_size = ($curve === 'secp384r1') ? 384 : 256;
} else {
    $key_algo = 'RSA';
    $key_size = $details_src['bits'] ?? 4096;
}

// Extract SANs and extensions by parsing the CSR DER directly.
$sans_list  = $SSL->csr_extract_sans($csr_pem);
$ext_data   = $SSL->csr_extract_extensions($csr_pem);
$extensions_json = !empty($ext_data) ? json_encode($ext_data) : null;

// SANs to store (exclude CN which is always implied)
$sans_stored = array_values(array_filter($sans_list, fn($s) => $s !== $cn));

// Validate cert + zone before any DB writes
$cert_parsed = null;
$zone        = null;
if (!empty($cert_pem)) {
    if ($zone_id <= 0) {
        print json_encode(['status' => 'error', 'message' => _("Zone is required when importing a certificate.")]);
        exit;
    }

    if ($user->admin == "1") {
        $zone = $Database->getObjectQuery("SELECT * FROM zones WHERE id = ?", [$zone_id]);
    } else {
        $zone = $Database->getObjectQuery("SELECT * FROM zones WHERE id = ? AND t_id = ?", [$zone_id, (int)$user->t_id]);
    }
    if (!$zone) {
        print json_encode(['status' => 'error', 'message' => _("Zone not found.")]);
        exit;
    }

    $cert_parsed = openssl_x509_parse($cert_pem);
    if ($cert_parsed === false) {
        print json_encode(['status' => 'error', 'message' => _("Cannot parse certificate. Please provide a valid PEM-encoded certificate.")]);
        exit;
    }

    if ($pkey_res !== false && !openssl_x509_check_private_key($cert_pem, $pkey_res)) {
        print json_encode(['status' => 'error', 'message' => _("Certificate does not match the private key.")]);
        exit;
    }

    $serial = $cert_parsed['serialNumber'];
}

// Store private key if encryption is configured
global $private_key_encryption_key;
$t_id    = (int)$user->t_id;
$pkey_id = null;

if (!empty($private_key_encryption_key[$t_id])) {
    $encrypted = $Certificates->pkey_encrypt($key_pem, $t_id);
    if ($encrypted === null) {
        print json_encode(['status' => 'error', 'message' => _("Private key encryption failed.")]);
        exit;
    }
    $Database->runQuery("INSERT INTO pkey (private_key_enc) VALUES (?)", [$encrypted]);
    $pkey_id = $Database->lastInsertId();
}

$source = empty($body['external']) ? 'internal' : 'external';

// Insert CSR record
$Database->runQuery(
    "INSERT INTO csrs (t_id, cn, sans, key_algo, key_size, country, state, locality, org, ou, email, status, source, csr_pem, extensions, pkey_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)",
    [
        $t_id, $cn,
        !empty($sans_stored) ? implode("\n", $sans_stored) : null,
        $key_algo, $key_size,
        $country ?: null, $state ?: null, $locality ?: null,
        $org ?: null, $ou ?: null, $email ?: null,
        $csr_pem, $source, $extensions_json, $pkey_id,
    ]
);
$csr_db_id = $Database->lastInsertId();

$Log->write("csrs", $csr_db_id, $t_id, $user->id, "add", false, "CSR imported for CN: {$cn}");

// Import certificate if provided
if ($cert_parsed !== null && $zone !== null) {
    $serial  = $cert_parsed['serialNumber'];
    $expires = date("Y-m-d H:i:s", $cert_parsed['validTo_time_t']);

    // Link existing cert if already in the zone (e.g. scanned by cron), otherwise insert
    $existing = $Database->getObjectQuery("SELECT id FROM certificates WHERE z_id = ? AND serial = ?", [$zone->id, $serial]);
    if ($existing) {
        $cert_id = $existing->id;
    } else {
        $insert = [
            'z_id'        => $zone->id,
            't_id'        => $zone->t_id,
            'serial'      => $serial,
            'certificate' => $cert_pem,
            'expires'     => $expires,
            'is_manual'   => 1,
        ];
        if ($pkey_id !== null) {
            $insert['pkey_id'] = $pkey_id;
        }
        $cert_id = $Database->insertObject("certificates", $insert);
        $Log->write("certificates", $cert_id, $zone->t_id, $user->id, "add", true, "Certificate serial " . $serial . " imported via CSR import");
    }

    $Database->runQuery("UPDATE csrs SET cert_id = ?, status = 'signed' WHERE id = ?", [$cert_id, $csr_db_id]);
    $Log->write("csrs", $csr_db_id, $t_id, $user->id, "edit", false, "Signed certificate imported for CN " . $cn);
} elseif ($pkey_res !== false) {
    // No cert provided but key available — scan certificates for a key match
    // Admins may have saved the CSR under their own t_id while the cert lives in another tenant
    if ($user->admin == "1") {
        $tenant_certs = $Database->getObjectsQuery(
            "SELECT id, certificate, t_id FROM certificates WHERE certificate != '' AND certificate IS NOT NULL",
            []
        );
    } else {
        $tenant_certs = $Database->getObjectsQuery(
            "SELECT id, certificate, t_id FROM certificates WHERE t_id = ? AND certificate != '' AND certificate IS NOT NULL",
            [$t_id]
        );
    }
    foreach ($tenant_certs as $tc) {
        if (!empty($tc->certificate) && openssl_x509_check_private_key($tc->certificate, $pkey_res)) {
            $Database->runQuery("UPDATE csrs SET cert_id = ?, status = 'signed' WHERE id = ?", [$tc->id, $csr_db_id]);
            // Also store pkey_id on the certificate if we encrypted the key
            if ($pkey_id !== null) {
                $Database->runQuery(
                    "UPDATE certificates SET pkey_id = ? WHERE id = ?
                     AND (pkey_id IS NULL OR pkey_id IN (SELECT id FROM pkey WHERE private_key_enc IS NULL OR private_key_enc = ''))",
                    [$pkey_id, $tc->id]
                );
            }
            $Log->write("csrs", $csr_db_id, $t_id, $user->id, "edit", false, "Linked to existing certificate id " . $tc->id . " by key match");
            break;
        }
    }
}

print json_encode([
    'status'      => 'ok',
    'csr_id'      => $csr_db_id,
    'cn'          => $cn,
    'pkey_stored' => $pkey_id !== null,
]);

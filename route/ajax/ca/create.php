<?php

/**
 * Generate a self-signed CA certificate and store it.
 * POST JSON: { name, cn, key_algo, key_size, days, org?, ou?, country?, state?, locality?, t_id? (admin) }
 */

ob_start();
require('../../../functions/autoload.php');
ob_clean();
header('Content-Type: application/json');

# session
$User->validate_session(false, false, false);
# validate permissions
$User->validate_user_permissions (3, true);

global $private_key_encryption_key;

$body     = json_decode(file_get_contents('php://input'), true);
$name     = trim($body['name']    ?? '');
$cn       = trim($body['cn']      ?? '');
$key_algo = ($body['key_algo'] ?? 'RSA') === 'EC' ? 'EC' : 'RSA';
$key_size = (int)($body['key_size'] ?? 4096);
$days     = (int)($body['days']   ?? 3650);
$org          = trim($body['org']      ?? '');
$ou           = trim($body['ou']       ?? '');
$country      = strtoupper(substr(trim($body['country']  ?? ''), 0, 2));
$state        = trim($body['state']    ?? '');
$locality     = trim($body['locality'] ?? '');
$parent_ca_id = isset($body['parent_ca_id']) && $body['parent_ca_id'] !== null ? (int)$body['parent_ca_id'] : null;
$pathlen      = isset($body['pathlen'])      && $body['pathlen']      !== null ? max(0, (int)$body['pathlen']) : null;

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
    print json_encode(['status' => 'error', 'message' => _("Display name is required.")]);
    exit;
}
if (empty($cn)) {
    print json_encode(['status' => 'error', 'message' => _("Common Name is required.")]);
    exit;
}
if ($days < 1 || $days > 36500) {
    print json_encode(['status' => 'error', 'message' => _("Validity must be between 1 and 36500 days.")]);
    exit;
}
if (empty($private_key_encryption_key[$t_id])) {
    print json_encode(['status' => 'error', 'message' => _("Private key encryption is not configured for this tenant.")]);
    exit;
}

// Validate key size
if ($key_algo === 'RSA') {
    if (!in_array($key_size, [2048, 4096])) $key_size = 4096;
} else {
    if (!in_array($key_size, [256, 384])) $key_size = 256;
}

// Generate private key
if ($key_algo === 'EC') {
    $curve      = ($key_size === 384) ? 'secp384r1' : 'prime256v1';
    $pkey_config = ['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => $curve];
} else {
    $pkey_config = ['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => $key_size];
}

$pkey = openssl_pkey_new($pkey_config);
if ($pkey === false) {
    print json_encode(['status' => 'error', 'message' => _("Key generation failed.") . " " . openssl_error_string()]);
    exit;
}

// Build distinguished name
$dn = ['CN' => $cn];
if (!empty($country))  $dn['C']  = $country;
if (!empty($state))    $dn['ST'] = $state;
if (!empty($locality)) $dn['L']  = $locality;
if (!empty($org))      $dn['O']  = $org;
if (!empty($ou))       $dn['OU'] = $ou;

// Resolve parent CA if intermediate
$parent_cert_pem = null;
$parent_pkey_res = null;
if ($parent_ca_id !== null) {
    $parent_ca = $Database->getObjectsQuery(
        "SELECT ca.id, ca.certificate, ca.t_id, pk.private_key_enc FROM cas ca
         INNER JOIN pkey pk ON ca.pkey_id = pk.id
         WHERE ca.id = ? AND pk.private_key_enc IS NOT NULL AND pk.private_key_enc != ''",
        [$parent_ca_id]
    );
    if (empty($parent_ca)) {
        print json_encode(['status' => 'error', 'message' => _("Parent CA not found or has no private key.")]);
        exit;
    }
    $parent_ca = $parent_ca[0];
    // Tenant access check
    if ($user->admin !== "1" && (int)$parent_ca->t_id !== $t_id) {
        print json_encode(['status' => 'error', 'message' => _("Access denied to parent CA.")]);
        exit;
    }
    $parent_cert_pem = $parent_ca->certificate;
    $parent_key_pem  = $Certificates->pkey_decrypt($parent_ca->private_key_enc, (int)$parent_ca->t_id);
    if (empty($parent_key_pem)) {
        print json_encode(['status' => 'error', 'message' => _("Failed to decrypt parent CA private key.")]);
        exit;
    }
    if (!openssl_pkey_get_private($parent_key_pem)) {
        print json_encode(['status' => 'error', 'message' => _("Parent CA private key is invalid.")]);
        exit;
    }
    $parent_pkey_res = $parent_key_pem;
}

// Write temp OpenSSL config with CA extensions
$tmp_conf = tempnam(sys_get_temp_dir(), 'phpssl_ca_');
$bc_pathlen = ($parent_ca_id !== null && $pathlen !== null) ? ', pathlen:' . $pathlen : '';
$conf  = "[req]\ndistinguished_name = req_dn\nreq_extensions = v3_ca\nprompt = no\n";
$conf .= "[req_dn]\n";
$conf .= "[v3_ca]\n";
$conf .= "basicConstraints = critical, CA:TRUE" . $bc_pathlen . "\n";
$conf .= "keyUsage = critical, keyCertSign, cRLSign\n";
$conf .= "subjectKeyIdentifier = hash\n";
if ($parent_ca_id !== null) {
    $conf .= "authorityKeyIdentifier = keyid:always,issuer\n";
}
file_put_contents($tmp_conf, $conf);

// Create CSR
$csr = openssl_csr_new($dn, $pkey, ['digest_alg' => 'sha256', 'config' => $tmp_conf]);
if ($csr === false) {
    @unlink($tmp_conf);
    print json_encode(['status' => 'error', 'message' => _("CSR generation failed.") . " " . openssl_error_string()]);
    exit;
}

// Sign: null = self-signed root, parent cert = intermediate
$serial     = random_int(1, PHP_INT_MAX);
$sign_pkey  = $parent_pkey_res !== null ? $parent_pkey_res : $pkey;
$sign_cert  = $parent_cert_pem;
$signed = openssl_csr_sign(
    $csr,
    $sign_cert,
    $sign_pkey,
    $days,
    ['config' => $tmp_conf, 'digest_alg' => 'sha256', 'x509_extensions' => 'v3_ca'],
    $serial
);
@unlink($tmp_conf);

if ($signed === false) {
    $err_msg = $parent_ca_id !== null ? _("Intermediate CA signing failed.") : _("Self-signing failed.");
    print json_encode(['status' => 'error', 'message' => $err_msg . " " . openssl_error_string()]);
    exit;
}

openssl_x509_export($signed, $cert_pem);
openssl_pkey_export($pkey, $key_pem);

if (empty($cert_pem) || empty($key_pem)) {
    print json_encode(['status' => 'error', 'message' => _("Failed to export certificate or key.")]);
    exit;
}

// Parse result for subject and expiry
$cert_parsed = openssl_x509_parse($cert_pem);
$subj_parts  = [];
foreach (['CN', 'O', 'OU', 'C'] as $k) {
    if (!empty($cert_parsed['subject'][$k])) $subj_parts[] = $cert_parsed['subject'][$k];
}
$subject_str = implode(', ', $subj_parts);
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
        "INSERT INTO cas (t_id, name, certificate, pkey_id, parent_ca_id, subject, expires, serial) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$t_id, $name, $cert_pem, $pkey_id, $parent_ca_id, $subject_str ?: null, $expires, $serial_hex ?: null]
    );
    $ca_id = $Database->lastInsertId();

    $ca_type = $parent_ca_id !== null ? "intermediate" : "self-signed";
    $Log->write("cas", $ca_id, $t_id, $user->id, "add", false, "CA created: {$name} ({$ca_type}, {$key_algo} {$key_size}, {$days} days)");
} catch (Exception $e) {
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

print json_encode(['status' => 'ok', 'ca_id' => $ca_id]);

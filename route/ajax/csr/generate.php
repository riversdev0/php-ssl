<?php

/**
 * Generate a CSR and private key server-side.
 *
 * POST JSON: { cn, sans, key_algo, key_size, key_usage[], ext_key_usage[], country, state, locality, org, ou, email }
 * Returns:   { status, csr_id, csr_pem, pkey_pem (only when not stored), pkey_stored }
 *
 * Private key is stored encrypted when $private_key_encryption_key is configured for the tenant.
 * Otherwise it is returned once in the response for client-side download.
 */

ob_start();
require('../../../functions/autoload.php');
ob_clean();
header('Content-Type: application/json');

$User->validate_session(false, false, false);
# validate permissions
$User->validate_user_permissions (3, true);

$body          = json_decode(file_get_contents('php://input'), true);
$source_csr_id = (int)($body['source_csr_id'] ?? 0);
$cn            = trim($body['cn'] ?? '');
$sans_raw = trim($body['sans'] ?? '');
$key_algo = ($body['key_algo'] ?? 'RSA') === 'EC' ? 'EC' : 'RSA';
$key_size = (int)($body['key_size'] ?? 2048);
$country  = strtoupper(substr(trim($body['country'] ?? ''), 0, 2));
$state    = trim($body['state'] ?? '');
$locality = trim($body['locality'] ?? '');
$org      = trim($body['org'] ?? '');
$ou       = trim($body['ou'] ?? '');
$email    = trim($body['email'] ?? '');

// Validate and whitelist extension selections
$allowed_ku = ['digitalSignature', 'contentCommitment', 'keyEncipherment', 'dataEncipherment', 'keyAgreement', 'keyCertSign', 'cRLSign'];
$allowed_eku = ['serverAuth', 'clientAuth', 'codeSigning', 'emailProtection', 'timeStamping', 'OCSPSigning'];

$key_usage     = array_values(array_intersect((array)($body['key_usage']     ?? []), $allowed_ku));
$ext_key_usage = array_values(array_intersect((array)($body['ext_key_usage'] ?? []), $allowed_eku));

if (empty($cn)) {
    http_response_code(400);
    print json_encode(['status' => 'error', 'message' => _("Common name is required.")]);
    exit;
}

$valid_hostname = function(string $s): bool {
    if (filter_var($s, FILTER_VALIDATE_IP)) return true;
    return (bool)preg_match('/^(\*\.)?[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/i', $s);
};

if ($key_algo === 'RSA') {
    if (!in_array($key_size, [2048, 4096])) $key_size = 2048;
} else {
    if (!in_array($key_size, [256, 384])) $key_size = 256;
}

// Parse SANs — skip entries that are not valid hostnames/IPs
$sans_list = [];
if (!empty($sans_raw)) {
    foreach (preg_split('/[\r\n,]+/', $sans_raw) as $s) {
        $s = trim($s);
        if ($s !== '' && $valid_hostname($s)) $sans_list[] = $s;
    }
}
// Only add CN as SAN if it is a valid hostname or IP
if ($valid_hostname($cn) && !in_array($cn, $sans_list)) {
    array_unshift($sans_list, $cn);
}

// Build SAN extension string
$san_parts = [];
foreach (array_unique($sans_list) as $s) {
    $san_parts[] = filter_var($s, FILTER_VALIDATE_IP) ? "IP:$s" : "DNS:$s";
}
$san_string = implode(', ', $san_parts);

// Write temporary OpenSSL config with extensions
$has_extensions = !empty($san_string) || !empty($key_usage) || !empty($ext_key_usage);
$tmp_conf = tempnam(sys_get_temp_dir(), 'phpssl_csr_');
$conf  = "[req]\ndistinguished_name = req_dn\nprompt = no\n";
if ($has_extensions) $conf .= "req_extensions = v3_req\n";
$conf .= "[req_dn]\n";
if ($has_extensions) {
    $conf .= "[v3_req]\n";
    if (!empty($san_string))   $conf .= "subjectAltName = {$san_string}\n";
    if (!empty($key_usage))    $conf .= "keyUsage = critical," . implode(', ', $key_usage) . "\n";
    if (!empty($ext_key_usage)) $conf .= "extendedKeyUsage = " . implode(', ', $ext_key_usage) . "\n";
}
file_put_contents($tmp_conf, $conf);

// Key generation config — do not pass the CSR config file here; it's a req-only
// config and OpenSSL fails to parse it as a full config during key generation.
if ($key_algo === 'EC') {
    $curve = ($key_size === 384) ? 'secp384r1' : 'prime256v1';
    $pkey_config = ['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => $curve];
} else {
    $pkey_config = ['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => $key_size];
}

$pkey = openssl_pkey_new($pkey_config);
if ($pkey === false) {
    @unlink($tmp_conf);
    http_response_code(500);
    print json_encode(['status' => 'error', 'message' => _("Key generation failed.") . " " . openssl_error_string()]);
    exit;
}

// Build distinguished name
$dn = ['CN' => $cn];
if (!empty($country))  $dn['C']            = $country;
if (!empty($state))    $dn['ST']           = $state;
if (!empty($locality)) $dn['L']            = $locality;
if (!empty($org))      $dn['O']            = $org;
if (!empty($ou))       $dn['OU']           = $ou;
if (!empty($email))    $dn['emailAddress'] = $email;

$csr = openssl_csr_new($dn, $pkey, ['digest_alg' => 'sha256', 'config' => $tmp_conf]);
if ($csr === false) {
    @unlink($tmp_conf);
    http_response_code(500);
    print json_encode(['status' => 'error', 'message' => _("CSR generation failed.") . " " . openssl_error_string()]);
    exit;
}

openssl_csr_export($csr, $csr_pem);
openssl_pkey_export($pkey, $key_pem, null, ['config' => $tmp_conf]);
@unlink($tmp_conf);

if (empty($csr_pem) || empty($key_pem)) {
    http_response_code(500);
    print json_encode(['status' => 'error', 'message' => _("Failed to export key or CSR.")]);
    exit;
}

// Store private key — encrypted if configured, otherwise return to client once
global $private_key_encryption_key;

if ($user->admin == "1" && !empty($body['t_id'])) {
    $t_id = (int)$body['t_id'];
    if (!$Database->getObject("tenants", $t_id)) {
        print json_encode(['status' => 'error', 'message' => _("Invalid tenant.")]);
        exit;
    }
} else {
    $t_id = (int)$user->t_id;
}
$pkey_id       = null;
$pkey_returned = null;

if (!empty($private_key_encryption_key[$t_id])) {
    $encrypted = $Certificates->pkey_encrypt($key_pem, $t_id);
    if ($encrypted === null) {
        http_response_code(500);
        print json_encode(['status' => 'error', 'message' => _("Private key encryption failed.")]);
        exit;
    }
    $Database->runQuery("INSERT INTO pkey (private_key_enc) VALUES (?)", [$encrypted]);
    $pkey_id = $Database->lastInsertId();
} else {
    $pkey_returned = $key_pem;
}

// SANs to store (exclude CN which is always implied)
$sans_stored = array_filter($sans_list, fn($s) => $s !== $cn);

// Extensions JSON to store
$extensions_data = [];
if (!empty($key_usage))     $extensions_data['keyUsage']    = $key_usage;
if (!empty($ext_key_usage)) $extensions_data['extKeyUsage'] = $ext_key_usage;
$extensions_json = !empty($extensions_data) ? json_encode($extensions_data) : null;

$Database->runQuery(
    "INSERT INTO csrs (t_id, cn, sans, key_algo, key_size, country, state, locality, org, ou, email, status, source, csr_pem, extensions, pkey_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'internal', ?, ?, ?)",
    [
        $t_id, $cn,
        !empty($sans_stored) ? implode("\n", array_values($sans_stored)) : null,
        $key_algo, $key_size,
        $country ?: null, $state ?: null, $locality ?: null,
        $org ?: null, $ou ?: null, $email ?: null,
        $csr_pem, $extensions_json, $pkey_id,
    ]
);
$csr_db_id = $Database->lastInsertId();

$Log->write("csrs", $csr_db_id, $t_id, $user->id, "generate", false, "CSR generated for CN: {$cn}");

// Mark source CSR as renewed
if ($source_csr_id > 0) {
    if ($user->admin == "1") {
        $src = $Database->getObjectQuery("SELECT id FROM csrs WHERE id = ?", [$source_csr_id]);
    } else {
        $src = $Database->getObjectQuery("SELECT id FROM csrs WHERE id = ? AND t_id = ?", [$source_csr_id, $t_id]);
    }
    if ($src) {
        $Database->runQuery("UPDATE csrs SET renewed_by = ? WHERE id = ?", [$csr_db_id, $source_csr_id]);
    }
}

print json_encode([
    'status'      => 'ok',
    'csr_id'      => $csr_db_id,
    'csr_pem'     => $csr_pem,
    'pkey_pem'    => $pkey_returned,
    'pkey_stored' => $pkey_id !== null,
]);

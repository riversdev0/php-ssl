<?php

/**
 * Sign a CSR with a tenant CA.
 * POST JSON: { csr_id, ca_id, days, zone_id, override_extensions, key_usage[]?, ext_key_usage[]? }
 * Returns: { status, cert_id }
 */

ob_start();
require('../../../functions/autoload.php');
ob_clean();
header('Content-Type: application/json');

$User->validate_session(false, false, false);
# validate permissions
$User->validate_user_permissions (3, true);

$body  = json_decode(file_get_contents('php://input'), true);
$csr_id            = (int)($body['csr_id']  ?? 0);
$ca_id             = (int)($body['ca_id']   ?? 0);
$days              = (int)($body['days']    ?? 365);
$zone_id           = (int)($body['zone_id'] ?? 0);
$override_ext      = !empty($body['override_extensions']);
$notify_email      = trim($body['notify_email'] ?? '');

$allowed_ku  = ['digitalSignature','contentCommitment','keyEncipherment','dataEncipherment','keyAgreement','keyCertSign','cRLSign'];
$allowed_eku = ['serverAuth','clientAuth','codeSigning','emailProtection','timeStamping','OCSPSigning'];

if ($days < 1 || $days > 3650) {
    print json_encode(['status' => 'error', 'message' => _("Validity must be between 1 and 3650 days.")]);
    exit;
}

// Fetch CSR
if ($user->admin == "1") {
    $csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ?", [$csr_id]);
} else {
    $csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ? AND t_id = ?", [$csr_id, $user->t_id]);
}
if (!$csr) {
    print json_encode(['status' => 'error', 'message' => _("CSR not found.")]);
    exit;
}
$t_id = (int)$csr->t_id;

// Fetch CA (must belong to CSR's tenant)
$ca = $Database->getObjectQuery(
    "SELECT ca.*, pk.private_key_enc FROM cas ca
     INNER JOIN pkey pk ON ca.pkey_id = pk.id
     WHERE ca.id = ? AND ca.t_id = ? AND pk.private_key_enc IS NOT NULL AND pk.private_key_enc != ''",
    [$ca_id, $t_id]
);
if (!$ca) {
    print json_encode(['status' => 'error', 'message' => _("CA not found or has no private key.")]);
    exit;
}

// Fetch and validate zone
$zone = $Database->getObject("zones", $zone_id);
if (!$zone) {
    print json_encode(['status' => 'error', 'message' => _("Zone not found.")]);
    exit;
}
// Non-admin: zone must belong to user's tenant
if ($user->admin != "1" && $zone->t_id != $user->t_id) {
    print json_encode(['status' => 'error', 'message' => _("Access denied.")]);
    exit;
}

// Decrypt CA private key
$pkey_pem = $Certificates->pkey_decrypt($ca->private_key_enc, $t_id);
if ($pkey_pem === false) {
    print json_encode(['status' => 'error', 'message' => _("Failed to decrypt CA private key.")]);
    exit;
}
$ca_pkey = openssl_pkey_get_private($pkey_pem);
if ($ca_pkey === false) {
    print json_encode(['status' => 'error', 'message' => _("Cannot load CA private key.")]);
    exit;
}

// Determine extensions
if ($override_ext) {
    $key_usage     = array_values(array_intersect((array)($body['key_usage']     ?? []), $allowed_ku));
    $ext_key_usage = array_values(array_intersect((array)($body['ext_key_usage'] ?? []), $allowed_eku));
} else {
    $stored = !empty($csr->extensions) ? (json_decode($csr->extensions, true) ?? []) : [];
    $key_usage     = $stored['keyUsage']    ?? [];
    $ext_key_usage = $stored['extKeyUsage'] ?? [];
}

// Extract SANs from CSR
$sans_list = [];
if (!empty($csr->sans)) {
    $sans_list = array_values(array_filter(array_map('trim', explode("\n", $csr->sans))));
}
if (!empty($csr->csr_pem)) {
    $pem_sans = $SSL->csr_extract_sans($csr->csr_pem);
    if (!empty($pem_sans)) $sans_list = $pem_sans;
}
if (!in_array($csr->cn, $sans_list)) {
    array_unshift($sans_list, $csr->cn);
}
$sans_list = array_unique($sans_list);

$san_parts = [];
foreach ($sans_list as $s) {
    $san_parts[] = filter_var($s, FILTER_VALIDATE_IP) ? "IP:{$s}" : "DNS:{$s}";
}
$san_string = implode(', ', $san_parts);

// Build temp OpenSSL config
$tmp_conf = tempnam(sys_get_temp_dir(), 'phpssl_sign_');
$conf  = "[req]\ndistinguished_name = req_dn\nprompt = no\n";
$conf .= "[req_dn]\n";
$conf .= "[v3_issued]\n";
$conf .= "basicConstraints = CA:FALSE\n";
if (!empty($san_string)) {
    $conf .= "subjectAltName = {$san_string}\n";
}
if (!empty($key_usage)) {
    $conf .= "keyUsage = critical," . implode(', ', $key_usage) . "\n";
}
if (!empty($ext_key_usage)) {
    $conf .= "extendedKeyUsage = " . implode(', ', $ext_key_usage) . "\n";
}
file_put_contents($tmp_conf, $conf);

// Random serial (63-bit positive integer)
$serial = random_int(1, PHP_INT_MAX);

// Sign the CSR
$signed = openssl_csr_sign(
    $csr->csr_pem,
    $ca->certificate,
    $ca_pkey,
    $days,
    ['config' => $tmp_conf, 'digest_alg' => 'sha256', 'x509_extensions' => 'v3_issued'],
    $serial
);
@unlink($tmp_conf);

if ($signed === false) {
    print json_encode(['status' => 'error', 'message' => _("Signing failed.") . " " . openssl_error_string()]);
    exit;
}

openssl_x509_export($signed, $cert_pem);
if (empty($cert_pem)) {
    print json_encode(['status' => 'error', 'message' => _("Failed to export signed certificate.")]);
    exit;
}

// Parse result to get actual serial and expiry
$cert_parsed = openssl_x509_parse($cert_pem);
$serial_str  = $cert_parsed['serialNumber'] ?? (string)$serial;
$expires     = date('Y-m-d H:i:s', $cert_parsed['validTo_time_t']);

// Check for serial collision in this zone
$existing = $Database->getObjectQuery(
    "SELECT id FROM certificates WHERE z_id = ? AND serial = ?",
    [$zone->id, $serial_str]
);
if ($existing) {
    print json_encode(['status' => 'error', 'message' => _("Serial number collision — please try again.")]);
    exit;
}

try {
    $insert = [
        'z_id'      => $zone->id,
        't_id'      => $zone->t_id,
        'serial'    => $serial_str,
        'certificate' => $cert_pem,
        'expires'   => $expires,
        'is_manual' => 1,
    ];
    // Link CSR's private key to the certificate if available
    if (!empty($csr->pkey_id)) {
        $insert['pkey_id'] = $csr->pkey_id;
    }
    $cert_id = $Database->insertObject("certificates", $insert);

    // Update CSR: mark as signed, link certificate
    $Database->runQuery(
        "UPDATE csrs SET status = 'signed', cert_id = ? WHERE id = ?",
        [$cert_id, $csr->id]
    );

    $Log->write(
        "csrs", $csr->id, $t_id, $user->id, "sign", false,
        "CSR signed by CA '{$ca->name}', cert serial {$serial_str}, zone {$zone->name}"
    );
    $Log->write(
        "certificates", $cert_id, $zone->t_id, $user->id, "add", true,
        "Certificate serial {$serial_str} issued from CSR for {$csr->cn} by CA '{$ca->name}'"
    );
} catch (Exception $e) {
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// Send notification email if requested
$mail_error = null;
if (!empty($notify_email) && filter_var($notify_email, FILTER_VALIDATE_EMAIL)) {
    try {
        $Mail = new Mail_send();

        $cn_safe      = htmlspecialchars($csr->cn);
        $ca_safe      = htmlspecialchars($ca->name);
        $issued_on    = date('Y-m-d H:i:s');
        $valid_until  = $expires;
        $serial_disp  = htmlspecialchars($serial_str);
        $pem_escaped  = htmlspecialchars($cert_pem);

        $body = [];
        $body[] = "<table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width:680px;margin:0 auto'>";
        $body[] = "<tr><td style='padding:20px 0 10px 0'>{$Mail->font_title}" . _("New certificate issued") . "</font></td></tr>";
        $body[] = "<tr><td style='padding:0 0 20px 0'>{$Mail->font_norm}";
        $body[] = sprintf(_("A new TLS certificate for <b>%s</b> has been successfully issued and is ready for use."), $cn_safe);
        $body[] = "</font></td></tr>";

        // Details table
        $th = "style='text-align:left;padding:4px 12px 4px 0;white-space:nowrap;vertical-align:top;border-bottom:1px solid #eee'";
        $td = "style='padding:4px 0 4px 12px;vertical-align:top;border-bottom:1px solid #eee'";
        $body[] = "<tr><td>";
        $body[] = "<table border='0' cellpadding='0' cellspacing='0' style='margin-bottom:20px'>";
        $body[] = "<tr><th {$th}>{$Mail->font_norm}" . _("Common Name")    . "</font></th><td {$td}>{$Mail->font_bold}{$cn_safe}</font></td></tr>";
        $body[] = "<tr><th {$th}>{$Mail->font_norm}" . _("Issued by")      . "</font></th><td {$td}>{$Mail->font_norm}{$ca_safe}</font></td></tr>";
        $body[] = "<tr><th {$th}>{$Mail->font_norm}" . _("Serial")         . "</font></th><td {$td}>{$Mail->font_norm}{$serial_disp}</font></td></tr>";
        $body[] = "<tr><th {$th}>{$Mail->font_norm}" . _("Issued on")      . "</font></th><td {$td}>{$Mail->font_norm}{$issued_on}</font></td></tr>";
        $body[] = "<tr><th {$th}>{$Mail->font_norm}" . _("Valid until")    . "</font></th><td {$td}>{$Mail->font_norm}{$valid_until}</font></td></tr>";
        $body[] = "</table>";
        $body[] = "</td></tr>";

        // Inline PEM
        $body[] = "<tr><td style='padding:0 0 6px 0'>{$Mail->font_norm}<b>" . _("Certificate (PEM)") . ":</b></font></td></tr>";
        $body[] = "<tr><td style='padding:0 0 20px 0'>";
        $body[] = "<pre style='background:#f5f5f5;border:1px solid #ddd;padding:10px;font-size:11px;font-family:monospace;word-break:break-all;white-space:pre-wrap'>{$pem_escaped}</pre>";
        $body[] = "</td></tr>";
        $body[] = "</table>";

        $message = $Mail->generate_message($Mail->set_body($body));

        // Attach certificate as file
        $safe_cn  = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $csr->cn);
        $filename = "{$safe_cn}.crt";
        $Mail->Php_mailer->addStringAttachment($cert_pem, $filename, 'base64', 'application/x-pem-file');

        // CC all tenant recipients
        $tenant = $Database->getObject("tenants", $t_id);
        $cc_recipients = [];
        if ($tenant && !empty($tenant->recipients)) {
            $cc_recipients = array_values(array_filter(
                array_map('trim', explode(";", str_replace(",", ";", $tenant->recipients))),
                function($e) { return filter_var($e, FILTER_VALIDATE_EMAIL) !== false; }
            ));
            // Don't CC the primary recipient if already in To
            $cc_recipients = array_values(array_filter($cc_recipients, function($e) use ($notify_email) {
                return strtolower($e) !== strtolower($notify_email);
            }));
        }

        $subject = _("New certificate issued") . ": " . $csr->cn;
        $Mail->send($subject, [$notify_email], $cc_recipients, [], $message, false);
    } catch (Exception $e) {
        $mail_error = $e->getMessage();
    }
}

print json_encode(array_filter([
    'status'     => 'ok',
    'cert_id'    => $cert_id,
    'mail_error' => $mail_error,
], fn($v) => $v !== null));

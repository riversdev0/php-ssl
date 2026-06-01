<?php

/**
 * AJAX: import a signed certificate and link it to a CSR.
 */

require('../../../functions/autoload.php');
$User->validate_session(false, true, false);

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    print json_encode(['status' => 'error', 'message' => _("Invalid request.")]);
    exit;
}

$csr_id  = (int) ($body['csr_id']  ?? 0);
$zone_id = (int) ($body['zone_id'] ?? 0);
$pem     = trim($body['pem']       ?? '');

if ($csr_id <= 0 || $zone_id <= 0 || $pem === '') {
    print json_encode(['status' => 'error', 'message' => _("Invalid input.")]);
    exit;
}

// Fetch and authorise CSR
if ($user->admin == "1") {
    $csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ?", [$csr_id]);
} else {
    $csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ? AND t_id = ?", [$csr_id, $user->t_id]);
}

if (!$csr) {
    print json_encode(['status' => 'error', 'message' => _("CSR not found.")]);
    exit;
}

if ($csr->status === 'signed') {
    print json_encode(['status' => 'error', 'message' => _("This CSR already has a linked certificate.")]);
    exit;
}

// Fetch and authorise zone
if ($user->admin == "1") {
    $zone = $Database->getObjectQuery("SELECT * FROM zones WHERE id = ?", [$zone_id]);
} else {
    $zone = $Database->getObjectQuery("SELECT * FROM zones WHERE id = ? AND t_id = ?", [$zone_id, $user->t_id]);
}

if (!$zone) {
    print json_encode(['status' => 'error', 'message' => _("Zone not found.")]);
    exit;
}

// Parse certificate
$cert_parsed = openssl_x509_parse($pem);
if ($cert_parsed === false) {
    print json_encode(['status' => 'error', 'message' => _("Cannot parse certificate. Please provide a valid PEM-encoded certificate.")]);
    exit;
}

// Verify certificate public key matches the CSR
if (!empty($csr->csr_pem)) {
    $csr_pub  = openssl_csr_get_public_key($csr->csr_pem);
    $cert_pub = openssl_get_publickey($pem);
    if ($csr_pub && $cert_pub) {
        $d1 = openssl_pkey_get_details($csr_pub);
        $d2 = openssl_pkey_get_details($cert_pub);
        if (($d1['key'] ?? '') !== ($d2['key'] ?? '')) {
            print json_encode(['status' => 'error', 'message' => _("Certificate does not match this CSR.")]);
            exit;
        }
    }
}

$serial  = $cert_parsed['serialNumber'];
$expires = date("Y-m-d H:i:s", $cert_parsed['validTo_time_t']);

try {
    // If cert already exists in this zone (e.g. scanned by cron), link it rather than re-insert
    $existing = $Database->getObjectQuery("SELECT id FROM certificates WHERE z_id = ? AND serial = ?", [$zone->id, $serial]);
    if ($existing) {
        $cert_id = $existing->id;
    } else {
        $insert = [
            "z_id"        => $zone->id,
            "t_id"        => $zone->t_id,
            "serial"      => $serial,
            "certificate" => $pem,
            "expires"     => $expires,
            "is_manual"   => 1,
        ];
        if (!empty($csr->pkey_id)) {
            $insert["pkey_id"] = (int)$csr->pkey_id;
        }
        $cert_id = $Database->insertObject("certificates", $insert);
        $Log->write("certificates", $cert_id, $zone->t_id, $user->id, "add", true, "Certificate serial " . $serial . " imported via CSR upload");
    }

    // Update CSR record
    $Database->runQuery(
        "UPDATE csrs SET cert_id = ?, status = 'signed' WHERE id = ?",
        [$cert_id, $csr_id]
    );

    $Log->write("csrs", $csr_id, $csr->t_id, $user->id, "edit", false, "Signed certificate imported for CN " . $csr->cn);

    print json_encode(['status' => 'ok']);
} catch (Exception $e) {
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

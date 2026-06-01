<?php

/**
 * Modal: parsed CSR details.
 */

require('../../../functions/autoload.php');
$User->validate_session(false, false, false);

$csr_id = (int) ($_GET['csr_id'] ?? 0);

if ($csr_id <= 0) {
    $Modal->modal_print(_("Error"), "<div class='alert alert-danger'>"._("Invalid request.")."</div>", "", "", false, "danger");
    exit;
}

if ($user->admin == "1") {
    $csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ?", [$csr_id]);
} else {
    $csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ? AND t_id = ?", [$csr_id, $user->t_id]);
}

if (!$csr) {
    $Modal->modal_print(_("Error"), "<div class='alert alert-danger'>"._("CSR not found.")."</div>", "", "", false, "danger");
    exit;
}

// Parse CSR PEM
$parsed_subject = [];
$parsed_key     = null;
if (!empty($csr->csr_pem)) {
    $parsed_subject = openssl_csr_get_subject($csr->csr_pem) ?: [];
    $pub = openssl_csr_get_public_key($csr->csr_pem);
    if ($pub) {
        $parsed_key = openssl_pkey_get_details($pub);
    }
}

$status_map = [
    'pending'   => ['label' => _("Pending"),   'cls' => 'secondary'],
    'submitted' => ['label' => _("Submitted"), 'cls' => 'info'],
    'signed'    => ['label' => _("Signed"),    'cls' => 'success'],
];
$sm          = $status_map[$csr->status] ?? ['label' => $csr->status, 'cls' => 'secondary'];
$status_html = "<span class='badge bg-{$sm['cls']}-lt text-{$sm['cls']}'>{$sm['label']}</span>";

$key_label = $csr->key_algo === 'EC'
    ? 'EC ' . ($csr->key_size == 256 ? 'P-256' : 'P-384')
    : 'RSA ' . $csr->key_size . ' bit';

// Build SANs list — prefer stored column, fall back to PEM parsing for older imports
$sans_items  = [];
$stored_sans = !empty($csr->sans) ? array_filter(array_map('trim', explode("\n", $csr->sans))) : [];
if (empty($stored_sans) && !empty($csr->csr_pem)) {
    foreach ($SSL->csr_extract_sans($csr->csr_pem) as $s) {
        if ($s !== $csr->cn) $sans_items[] = htmlspecialchars($s);
    }
} else {
    foreach ($stored_sans as $s) {
        $sans_items[] = htmlspecialchars($s);
    }
}
// Always include CN first
array_unshift($sans_items, '<b>' . htmlspecialchars($csr->cn) . '</b> <span class="text-muted small">(CN)</span>');

// Subject fields from DB (more reliable than parsed)
$subject_rows = [
    [_("Common name"),   $csr->cn],
    [_("Organization"),  $csr->org],
    [_("Unit"),          $csr->ou],
    [_("Country"),       $csr->country],
    [_("State"),         $csr->state],
    [_("Locality"),      $csr->locality],
    [_("Email"),         $csr->email],
];

$rows = "<table class='table table-sm table-borderless align-middle mb-0'>";

// Subject section
$rows .= "<tr><td colspan='2' class='pb-1 pt-2'><span class='text-muted small fw-bold text-uppercase'>" . _("Subject") . "</span></td></tr>";
foreach ($subject_rows as [$label, $val]) {
    if (empty($val)) continue;
    $rows .= "<tr>";
    $rows .= "<th style='width:130px;font-weight:500;color:#6c757d;padding:2px 8px 2px 0;white-space:nowrap;'>" . $label . "</th>";
    $rows .= "<td style='padding:2px 0;'>" . htmlspecialchars($val) . "</td>";
    $rows .= "</tr>";
}

// SANs section
$rows .= "<tr><td colspan='2' class='pb-1 pt-3'><span class='text-muted small fw-bold text-uppercase'>" . _("Subject alternative names") . "</span></td></tr>";
$rows .= "<tr><td colspan='2' style='padding:2px 0;'>";
$rows .= "<div style='line-height:1.8;'>";
foreach ($sans_items as $s) {
    $rows .= "<span class='badge bg-azure-lt border me-1 mb-1'>{$s}</span>";
}
$rows .= "</div></td></tr>";

// Extensions section
$ext_data = !empty($csr->extensions) ? json_decode($csr->extensions, true) : [];
if (!empty($ext_data)) {
    $ku_labels = [
        'digitalSignature'  => _("Digital Signature"),
        'contentCommitment' => _("Content Commitment"),
        'keyEncipherment'   => _("Key Encipherment"),
        'dataEncipherment'  => _("Data Encipherment"),
        'keyAgreement'      => _("Key Agreement"),
        'keyCertSign'       => _("Certificate Sign"),
        'cRLSign'           => _("CRL Sign"),
    ];
    $eku_labels = [
        'serverAuth'      => _("TLS Web Server Auth"),
        'clientAuth'      => _("TLS Web Client Auth"),
        'codeSigning'     => _("Code Signing"),
        'emailProtection' => _("Email Protection"),
        'timeStamping'    => _("Time Stamping"),
        'OCSPSigning'     => _("OCSP Signing"),
    ];
    $rows .= "<tr><td colspan='2' class='pb-1 pt-3'><span class='text-muted small fw-bold text-uppercase'>" . _("Requested extensions") . "</span></td></tr>";
    if (!empty($ext_data['keyUsage'])) {
        $rows .= "<tr><th style='width:130px;font-weight:500;color:#6c757d;padding:2px 8px 2px 0;vertical-align:top'>" . _("Key Usage") . "</th>";
        $rows .= "<td style='padding:2px 0;'>";
        foreach ($ext_data['keyUsage'] as $v) {
            $label = $ku_labels[$v] ?? $v;
            $rows .= "<span class='badge bg-azure-lt me-1 mb-1'>" . htmlspecialchars($label) . "</span>";
        }
        $rows .= "</td></tr>";
    }
    if (!empty($ext_data['extKeyUsage'])) {
        $rows .= "<tr><th style='font-weight:500;color:#6c757d;padding:2px 8px 2px 0;vertical-align:top'>" . _("Ext. Key Usage") . "</th>";
        $rows .= "<td style='padding:2px 0;'>";
        foreach ($ext_data['extKeyUsage'] as $v) {
            $label = $eku_labels[$v] ?? $v;
            $rows .= "<span class='badge bg-azure-lt me-1 mb-1'>" . htmlspecialchars($label) . "</span>";
        }
        $rows .= "</td></tr>";
    }
}

// Key + status section
$rows .= "<tr><td colspan='2' class='pb-1 pt-3'><span class='text-muted small fw-bold text-uppercase'>" . _("Key & status") . "</span></td></tr>";
$rows .= "<tr><th style='width:130px;font-weight:500;color:#6c757d;padding:2px 8px 2px 0;'>" . _("Key") . "</th><td style='padding:2px 0;'>" . htmlspecialchars($key_label) . "</td></tr>";
if ((int)$user->permission >= 3) {
    $dl_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>';
    if (!empty($csr->pkey_id)) {
        $pkey_val = "<span class='badge bg-green-lt me-2'>" . _("Stored") . "</span>"
                  . "<a class='btn btn-sm bg-info-lt text-info py-0' href='/route/ajax/csr-download.php?csr_id={$csr_id}&type=pkey'>{$dl_icon} .key</a>";
    } else {
        $pkey_val = "<span class='badge bg-secondary-lt text-secondary'>" . _("Not stored") . "</span>";
    }
    $rows .= "<tr><th style='font-weight:500;color:#6c757d;padding:2px 8px 2px 0;'>" . _("Private key") . "</th><td style='padding:2px 0;'>{$pkey_val}</td></tr>";
}
$rows .= "<tr><th style='font-weight:500;color:#6c757d;padding:2px 8px 2px 0;'>" . _("Status") . "</th><td style='padding:2px 0;'>" . $status_html . "</td></tr>";
$rows .= "<tr><th style='font-weight:500;color:#6c757d;padding:2px 8px 2px 0;'>" . _("Created") . "</th><td style='padding:2px 0;'><span class='text-secondary'>" . date('Y-m-d H:i', strtotime($csr->created)) . "</span></td></tr>";

$rows .= "</table>";

$Modal->modal_print(_("CSR details"), $rows, "", "", false, "info");

<?php

/**
 * Modal: show full details for a CA certificate.
 * GET: ca_id
 */

require('../../../functions/autoload.php');
$User->validate_session(false, false, false);

$ca_id = (int)($_GET['ca_id'] ?? 0);
if (!$ca_id) {
    $Modal->modal_print(_("CA Details"), "<div class='alert alert-danger'>" . _("Invalid CA.") . "</div>", "", "", false, "");
    exit;
}

// Fetch CA (tenant-scoped)
if ($user->admin === "1") {
    $ca = $Database->getObjectsQuery(
        "SELECT ca.*, pk.private_key_enc IS NOT NULL AND pk.private_key_enc != '' AS has_pkey,
                pca.name AS parent_ca_name
         FROM cas ca
         LEFT JOIN pkey pk ON ca.pkey_id = pk.id
         LEFT JOIN cas pca ON ca.parent_ca_id = pca.id
         WHERE ca.id = ?", [$ca_id]
    );
} else {
    $ca = $Database->getObjectsQuery(
        "SELECT ca.*, pk.private_key_enc IS NOT NULL AND pk.private_key_enc != '' AS has_pkey,
                pca.name AS parent_ca_name
         FROM cas ca
         LEFT JOIN pkey pk ON ca.pkey_id = pk.id
         LEFT JOIN cas pca ON ca.parent_ca_id = pca.id
         WHERE ca.id = ? AND ca.t_id = ?", [$ca_id, (int)$user->t_id]
    );
}

if (empty($ca)) {
    $Modal->modal_print(_("CA Details"), "<div class='alert alert-danger'>" . _("CA not found.") . "</div>", "", "", false, "");
    exit;
}
$ca = $ca[0];

$parsed = !empty($ca->certificate) ? @openssl_x509_parse($ca->certificate) : false;

// Helpers
function ca_fmt_ts($ts) {
    return $ts ? date('Y-m-d H:i:s', $ts) . ' <span class="text-muted small">UTC</span>' : '&mdash;';
}
function ca_fmt_dn($dn) {
    if (!is_array($dn)) return htmlspecialchars((string)$dn);
    $parts = [];
    $order = ['CN', 'O', 'OU', 'C', 'ST', 'L'];
    foreach ($order as $k) {
        if (!empty($dn[$k])) $parts[] = $k . '=' . htmlspecialchars($dn[$k]);
    }
    foreach ($dn as $k => $v) {
        if (!in_array($k, $order) && !empty($v)) $parts[] = $k . '=' . htmlspecialchars($v);
    }
    return implode(', ', $parts);
}

$now    = time();
$exp_ts = $parsed['validTo_time_t'] ?? 0;
if (!$exp_ts) {
    $exp_html = "<span class='text-muted'>&mdash;</span>";
} elseif ($exp_ts < $now) {
    $exp_html = "<span class='badge bg-danger-lt text-danger me-1'>" . _("Expired") . "</span>" . date('Y-m-d H:i:s', $exp_ts);
} elseif (($exp_ts - $now) < 30 * 86400) {
    $exp_html = "<span class='badge bg-warning-lt text-warning me-1'>" . _("Expiring soon") . "</span>" . date('Y-m-d H:i:s', $exp_ts);
} else {
    $exp_html = date('Y-m-d H:i:s', $exp_ts);
}

// Key info from parsed cert
$has_cert     = !empty($ca->certificate);
$pkey_res     = $has_cert ? openssl_pkey_get_public($ca->certificate) : false;
$pkey_details = $pkey_res ? openssl_pkey_get_details($pkey_res) : [];
$key_type_map = [OPENSSL_KEYTYPE_RSA => 'RSA', OPENSSL_KEYTYPE_EC => 'EC (ECDSA)', OPENSSL_KEYTYPE_DSA => 'DSA'];
$key_type_str = $key_type_map[$pkey_details['type'] ?? -1] ?? _("Unknown");
if (!empty($pkey_details['bits'])) $key_type_str .= ' ' . $pkey_details['bits'] . ' bit';
if (!empty($pkey_details['ec']['curve_name'])) $key_type_str .= ' (' . $pkey_details['ec']['curve_name'] . ')';

// Extensions
$extensions = $parsed['extensions'] ?? [];

$row = function($label, $val) {
    return "<tr><th style='width:170px;white-space:nowrap'>{$label}</th><td>{$val}</td></tr>";
};

$content  = "<table class='table table-borderless table-sm align-middle mb-0'>";

$content .= "<tr><td colspan='2' class='py-1'><small class='text-muted text-uppercase fw-bold'>" . _("General") . "</small></td></tr>";
$content .= $row(_("Display name"),    "<strong>" . htmlspecialchars($ca->name) . "</strong>");
if ($user->admin === "1") {
    $all_tenants = $Tenants->get_all();
    $tname = isset($all_tenants[(int)$ca->t_id]) ? htmlspecialchars($all_tenants[(int)$ca->t_id]->name) : $ca->t_id;
    $content .= $row(_("Tenant"), $tname);
}
$content .= $row(_("Parent CA"),       $ca->parent_ca_name ? htmlspecialchars($ca->parent_ca_name) : "<span class='text-muted'>" . _("None (self-signed root)") . "</span>");
if ($user->admin === "1" || (int)$user->permission >= 3) {
    $pkey_val = $ca->has_pkey
        ? "<span class='badge bg-green-lt me-2'>" . _("Stored — can sign") . "</span>"
          . "<a class='btn btn-sm bg-info-lt text-info py-0' href='/route/ajax/ca/download.php?ca_id={$ca_id}&type=pkey'>"
          . "<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2' /><path d='M7 11l5 5l5 -5' /><path d='M12 4l0 12' /></svg> .key</a>"
        : "<span class='badge bg-danger-lt text-danger'>" . _("Not stored") . "</span>";
    $content .= $row(_("Private key"), $pkey_val);
}
$content .= $row(_("Created"),         htmlspecialchars($ca->created ?? '&mdash;'));

if ($parsed) {
    $content .= "<tr><td colspan='2' class='pt-3 pb-1'><small class='text-muted text-uppercase fw-bold'>" . _("Subject") . "</small></td></tr>";
    $content .= $row(_("Common Name"),      htmlspecialchars($parsed['subject']['CN']  ?? '&mdash;'));
    $content .= $row(_("Organization"),     htmlspecialchars($parsed['subject']['O']   ?? '&mdash;'));
    $content .= $row(_("Org. Unit"),        htmlspecialchars($parsed['subject']['OU']  ?? '&mdash;'));
    $content .= $row(_("Country"),          htmlspecialchars($parsed['subject']['C']   ?? '&mdash;'));
    $content .= $row(_("State / Province"), htmlspecialchars($parsed['subject']['ST']  ?? '&mdash;'));
    $content .= $row(_("Locality / City"),  htmlspecialchars($parsed['subject']['L']   ?? '&mdash;'));

    $content .= "<tr><td colspan='2' class='pt-3 pb-1'><small class='text-muted text-uppercase fw-bold'>" . _("Issuer") . "</small></td></tr>";
    $content .= $row(_("Issuer DN"),        "<span class='font-monospace small'>" . ca_fmt_dn($parsed['issuer'] ?? []) . "</span>");

    $content .= "<tr><td colspan='2' class='pt-3 pb-1'><small class='text-muted text-uppercase fw-bold'>" . _("Validity") . "</small></td></tr>";
    $content .= $row(_("Not Before"),       ca_fmt_ts($parsed['validFrom_time_t'] ?? 0));
    $content .= $row(_("Not After"),        $exp_html);

    $content .= "<tr><td colspan='2' class='pt-3 pb-1'><small class='text-muted text-uppercase fw-bold'>" . _("Key & Serial") . "</small></td></tr>";
    $content .= $row(_("Key algorithm"),    htmlspecialchars($key_type_str));
    $content .= $row(_("Serial number"),    "<span class='font-monospace small'>" . htmlspecialchars($parsed['serialNumberHex'] ?? $parsed['serialNumber'] ?? '&mdash;') . "</span>");

    if (!empty($extensions)) {
        $content .= "<tr><td colspan='2' class='pt-3 pb-1'><small class='text-muted text-uppercase fw-bold'>" . _("Extensions") . "</small></td></tr>";
        foreach ($extensions as $ext_name => $ext_val) {
            $val_str = is_array($ext_val) ? implode(', ', $ext_val) : (string)$ext_val;
            $content .= $row(htmlspecialchars($ext_name), "<span class='font-monospace small'>" . nl2br(htmlspecialchars($val_str)) . "</span>");
        }
    }
} else {
    $content .= "<tr><td colspan='2' class='pt-3 pb-1'><span class='text-muted small'>" . _("No certificate data available for this CA.") . "</span></td></tr>";
}

$content .= "</table>";

// Notification flags (admins and permission >= 3 can edit)
$can_edit_flags = ($user->admin === "1" || (int)$user->permission >= 3);
$chk_updates = $ca->ignore_updates ? " checked" : "";
$chk_expiry  = $ca->ignore_expiry  ? " checked" : "";

$content .= "<div class='mt-3 border-top pt-3'>";
$content .= "<small class='text-muted text-uppercase fw-bold d-block mb-2'>" . _("Notification settings") . "</small>";
$content .= "<div class='d-flex gap-3 flex-wrap'>";
$content .= "<label class='d-flex align-items-center gap-2" . ($can_edit_flags ? "" : " text-muted") . "'>";
$content .= "<input type='checkbox' id='ca-flag-ignore-updates' class='form-check-input m-0'" . $chk_updates . ($can_edit_flags ? "" : " disabled") . " data-ca-id='{$ca_id}' data-flag='ignore_updates'>";
$content .= _("Ignore update notifications") . "</label>";
$content .= "<label class='d-flex align-items-center gap-2" . ($can_edit_flags ? "" : " text-muted") . "'>";
$content .= "<input type='checkbox' id='ca-flag-ignore-expiry' class='form-check-input m-0'" . $chk_expiry . ($can_edit_flags ? "" : " disabled") . " data-ca-id='{$ca_id}' data-flag='ignore_expiry'>";
$content .= _("Ignore expiry notifications") . "</label>";
$content .= "</div>";
if ($can_edit_flags) {
    $content .= "<button type='button' class='btn btn-sm btn-primary mt-2' id='ca-flags-save'>" . _("Save") . "</button>";
    $content .= "<span id='ca-flags-result' class='ms-2 small'></span>";
}
$content .= "</div>";

if ($has_cert) {
    $content .= "<div class='mt-2'>";
    $content .= "<button type='button' class='btn btn-sm bg-info-lt text-info' id='ca-view-copy-pem'>";
    $content .= "<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z' /><path d='M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1' /></svg> ";
    $content .= _("Copy PEM") . "</button>";
    $content .= "<textarea id='ca-view-pem-raw' style='display:none'>" . htmlspecialchars($ca->certificate) . "</textarea>";
    $content .= "</div>";
}

$Modal->modal_print(htmlspecialchars($ca->name), $content, "", "", false, "");
?>
<script>
<?php if ($has_cert): ?>
document.getElementById('ca-view-copy-pem').addEventListener('click', function() {
    var pem = document.getElementById('ca-view-pem-raw').value;
    var btn = this;
    navigator.clipboard.writeText(pem).then(function() {
        var label = btn.lastChild;
        var orig  = label.textContent;
        label.textContent = ' ' + <?php print json_encode(_("Copied!")); ?>;
        setTimeout(function() { label.textContent = ' ' + orig; }, 1500);
    });
});
<?php endif; ?>
<?php if ($can_edit_flags): ?>
document.getElementById('ca-flags-save').addEventListener('click', function() {
    var btn    = this;
    var result = document.getElementById('ca-flags-result');
    btn.disabled = true;
    $.post('/route/ajax/ca/update-flags.php', {
        ca_id:          <?php print (int)$ca_id; ?>,
        ignore_updates: document.getElementById('ca-flag-ignore-updates').checked ? 1 : 0,
        ignore_expiry:  document.getElementById('ca-flag-ignore-expiry').checked  ? 1 : 0
    }, function(data) {
        btn.disabled = false;
        if (data.success) {
            result.className = 'ms-2 small text-success';
            result.textContent = <?php print json_encode(_("Saved.")); ?>;
        } else {
            result.className = 'ms-2 small text-danger';
            result.textContent = data.message || <?php print json_encode(_("Error.")); ?>;
        }
        setTimeout(function() { result.textContent = ''; }, 2500);
    }, 'json').fail(function() {
        btn.disabled = false;
        result.className = 'ms-2 small text-danger';
        result.textContent = <?php print json_encode(_("Request failed.")); ?>;
    });
});
<?php endif; ?>
</script>

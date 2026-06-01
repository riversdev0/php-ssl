<?php

/**
 * Modal: sign a CSR with a tenant CA.
 * GET ?csr_id=<int>
 */

require('../../../functions/autoload.php');
$User->validate_session(false, false, false);

$csr_id = (int)($_GET['csr_id'] ?? 0);
if ($csr_id <= 0) {
    $Modal->modal_print(_("Error"), "<div class='alert alert-danger'>" . _("Invalid request.") . "</div>", "", "", false, "danger");
    exit;
}

if ($user->admin == "1") {
    $csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ?", [$csr_id]);
} else {
    $csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ? AND t_id = ?", [$csr_id, $user->t_id]);
}

if (!$csr) {
    $Modal->modal_print(_("Error"), "<div class='alert alert-danger'>" . _("CSR not found.") . "</div>", "", "", false, "danger");
    exit;
}

// Load CAs for this tenant (must have a private key); root CAs first
$t_id = (int)$csr->t_id;
$cas  = $Database->getObjectsQuery(
    "SELECT ca.id, ca.name, ca.subject, ca.parent_ca_id, pca.name AS parent_ca_name FROM cas ca
     INNER JOIN pkey pk ON ca.pkey_id = pk.id
     LEFT JOIN cas pca ON ca.parent_ca_id = pca.id
     WHERE ca.t_id = ? AND pk.private_key_enc IS NOT NULL AND pk.private_key_enc != ''
     ORDER BY ca.parent_ca_id IS NOT NULL ASC, ca.name ASC",
    [$t_id]
);

if (empty($cas)) {
    $Modal->modal_print(
        _("No Certificate Authorities"),
        "<div class='alert alert-warning'>"
        . _("No CA with a private key found for this tenant.")
        . " <a href='/route/modals/cas/import.php' data-bs-toggle='modal' data-bs-target='#modal1'>" . _("Import a CA") . "</a>."
        . "</div>",
        "", "", false, "warning"
    );
    exit;
}

// Load zones (for storing the resulting certificate)
$all_zones   = $Zones->get_all();
$all_tenants = $Tenants->get_all();

// Parse existing extensions from CSR
$csr_ext     = !empty($csr->extensions) ? (json_decode($csr->extensions, true) ?? []) : [];
$csr_ku      = $csr_ext['keyUsage']    ?? [];
$csr_eku     = $csr_ext['extKeyUsage'] ?? [];

$ku_options = [
    'digitalSignature'  => _("Digital Signature"),
    'contentCommitment' => _("Content Commitment"),
    'keyEncipherment'   => _("Key Encipherment"),
    'dataEncipherment'  => _("Data Encipherment"),
    'keyAgreement'      => _("Key Agreement"),
    'keyCertSign'       => _("Certificate Sign"),
    'cRLSign'           => _("CRL Sign"),
];
$eku_options = [
    'serverAuth'      => _("TLS Web Server Auth"),
    'clientAuth'      => _("TLS Web Client Auth"),
    'codeSigning'     => _("Code Signing"),
    'emailProtection' => _("Email Protection"),
    'timeStamping'    => _("Time Stamping"),
    'OCSPSigning'     => _("OCSP Signing"),
];

$cn_esc  = htmlspecialchars($csr->cn);
$content = "<p class='text-secondary mb-3'>" . sprintf(_("Sign CSR for <b>%s</b>"), $cn_esc) . "</p>";

$content .= "<table class='table table-borderless table-sm align-middle'>";

// CA select
$content .= "<tr><th style='width:130px'>" . _("Certificate Authority") . "</th><td>";
$roots         = array_filter($cas, function($c) { return $c->parent_ca_id === null; });
$intermediates = array_filter($cas, function($c) { return $c->parent_ca_id !== null; });
$ca_option = function($ca) {
    $label = htmlspecialchars($ca->name);
    if ($ca->subject) $label .= " — " . htmlspecialchars($ca->subject);
    return "<option value='" . (int)$ca->id . "'>{$label}</option>";
};
$content .= "<select id='sign-ca-id' class='form-select form-select-sm'>";
if (!empty($roots) && !empty($intermediates)) {
    $content .= "<optgroup label='" . _("Root CAs") . "'>";
    foreach ($roots as $ca) $content .= $ca_option($ca);
    $content .= "</optgroup>";
    $content .= "<optgroup label='" . _("Intermediate CAs") . "'>";
    foreach ($intermediates as $ca) {
        $label = htmlspecialchars($ca->name);
        if ($ca->parent_ca_name) $label .= " (" . htmlspecialchars($ca->parent_ca_name) . ")";
        if ($ca->subject) $label .= " — " . htmlspecialchars($ca->subject);
        $content .= "<option value='" . (int)$ca->id . "'>{$label}</option>";
    }
    $content .= "</optgroup>";
} else {
    foreach ($cas as $ca) $content .= $ca_option($ca);
}
$content .= "</select></td></tr>";

// Validity days
$content .= "<tr><th>" . _("Validity (days)") . "</th><td>";
$content .= "<input type='number' id='sign-days' class='form-control form-control-sm' style='width:100px' value='365' min='1' max='3650'>";
$content .= "</td></tr>";

// Zone
$content .= "<tr><th>" . _("Zone") . "</th><td>";
if (empty($all_zones)) {
    $content .= "<span class='text-danger'>" . _("No zones available.") . "</span>";
} else {
    $content .= "<select id='sign-zone-id' class='form-select form-select-sm'>";
    if ($user->admin == "1") {
        $by_tenant = [];
        foreach ($all_zones as $z) { $by_tenant[$z->t_id][] = $z; }
        // Show CSR's tenant first
        if (!empty($by_tenant[$t_id])) {
            $content .= "<optgroup label='" . htmlspecialchars($all_tenants[$t_id]->name ?? '', ENT_QUOTES) . " ★'>";
            foreach ($by_tenant[$t_id] as $z) {
                $content .= "<option value='" . (int)$z->id . "'>" . htmlspecialchars($z->name) . "</option>";
            }
            $content .= "</optgroup>";
        }
        foreach ($all_tenants as $t) {
            if ($t->id == $t_id || empty($by_tenant[$t->id])) continue;
            $content .= "<optgroup label='" . htmlspecialchars($t->name, ENT_QUOTES) . "'>";
            foreach ($by_tenant[$t->id] as $z) {
                $content .= "<option value='" . (int)$z->id . "'>" . htmlspecialchars($z->name) . "</option>";
            }
            $content .= "</optgroup>";
        }
    } else {
        foreach ($all_zones as $z) {
            $content .= "<option value='" . (int)$z->id . "'>" . htmlspecialchars($z->name) . "</option>";
        }
    }
    $content .= "</select>";
}
$content .= "</td></tr>";

// Notify email
$content .= "<tr><th>" . _("Send cert to") . "</th><td>";
$content .= "<input type='email' id='sign-notify-email' class='form-control form-control-sm' placeholder='" . _("e.g. admin@example.com (optional)") . "'>";
$content .= "</td></tr>";

$content .= "</table>";

// Extensions section
$content .= "<hr>";
$content .= "<div class='d-flex align-items-center justify-content-between mb-2'>";
$content .= "<span class='fw-bold small text-muted text-uppercase'>" . _("Extensions") . "</span>";
$content .= "<div class='form-check form-switch mb-0'>";
$content .= "<input class='form-check-input' type='checkbox' id='sign-override-ext'>";
$content .= "<label class='form-check-label small' for='sign-override-ext'>" . _("Override CSR extensions") . "</label>";
$content .= "</div></div>";

// Read-only CSR extensions view
$content .= "<div id='sign-ext-readonly'>";
if (empty($csr_ku) && empty($csr_eku)) {
    $content .= "<p class='text-muted small'>" . _("No extensions stored in CSR.") . "</p>";
} else {
    if (!empty($csr_ku)) {
        $content .= "<div class='mb-1'><span class='text-muted small'>" . _("Key Usage") . ":</span> ";
        foreach ($csr_ku as $v) {
            $content .= "<span class='badge bg-blue-lt me-1'>" . htmlspecialchars($ku_options[$v] ?? $v) . "</span>";
        }
        $content .= "</div>";
    }
    if (!empty($csr_eku)) {
        $content .= "<div><span class='text-muted small'>" . _("Extended Key Usage") . ":</span> ";
        foreach ($csr_eku as $v) {
            $content .= "<span class='badge bg-azure-lt me-1'>" . htmlspecialchars($eku_options[$v] ?? $v) . "</span>";
        }
        $content .= "</div>";
    }
}
$content .= "</div>";

// Override checkboxes (hidden by default)
$content .= "<div id='sign-ext-override' style='display:none'>";
$content .= "<div class='mb-2'><span class='text-muted small fw-bold text-uppercase'>" . _("Key Usage") . "</span></div>";
$content .= "<div class='d-flex flex-wrap gap-2 mb-3'>";
foreach ($ku_options as $val => $label) {
    $checked = in_array($val, $csr_ku) ? " checked" : "";
    $content .= "<label class='d-flex align-items-center gap-1' style='cursor:pointer;white-space:nowrap'>";
    $content .= "<input type='checkbox' class='form-check-input sign-ku' value='{$val}'{$checked}> " . htmlspecialchars($label);
    $content .= "</label>";
}
$content .= "</div>";
$content .= "<div class='mb-2'><span class='text-muted small fw-bold text-uppercase'>" . _("Extended Key Usage") . "</span></div>";
$content .= "<div class='d-flex flex-wrap gap-2'>";
foreach ($eku_options as $val => $label) {
    $checked = in_array($val, $csr_eku) ? " checked" : "";
    $content .= "<label class='d-flex align-items-center gap-1' style='cursor:pointer;white-space:nowrap'>";
    $content .= "<input type='checkbox' class='form-check-input sign-eku' value='{$val}'{$checked}> " . htmlspecialchars($label);
    $content .= "</label>";
}
$content .= "</div>";
$content .= "</div>";

$content .= "<div class='mt-3 d-flex align-items-center gap-2'>";
$content .= "<button type='button' class='btn btn-sm bg-danger-lt text-danger' id='sign-reject-btn'>";
$content .= "<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M18 6l-12 12' /><path d='M6 6l12 12' /></svg> ";
$content .= _("Reject") . "</button>";
$content .= "</div>";
$content .= "<div id='sign-result' class='mt-3'></div>";

$Modal->modal_print(_("Sign CSR"), $content, _("Sign"), "", false, "purple");
?>
<script>
(function () {
    var overrideCb = document.getElementById('sign-override-ext');
    var readonly   = document.getElementById('sign-ext-readonly');
    var override   = document.getElementById('sign-ext-override');

    overrideCb.addEventListener('change', function () {
        readonly.style.display  = this.checked ? 'none' : '';
        override.style.display  = this.checked ? '' : 'none';
    });

    document.getElementById('sign-reject-btn').addEventListener('click', function () {
        if (!confirm(<?php print json_encode(_("Reject this CSR? Status will be set to Rejected.")); ?>)) return;
        var btn     = this;
        var $result = $('#sign-result');
        btn.disabled = true;
        fetch('/route/ajax/csr/reject.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csr_id: <?php print (int)$csr_id; ?> })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status === 'ok') {
                $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("CSR rejected.")); ?></div>");
                setTimeout(function () { $('#modal1').modal('hide'); }, 800);
            } else {
                $result.html("<div class='alert alert-danger p-2'>" + (data.message || '<?php print addslashes(_("Error.")); ?>') + "</div>");
                btn.disabled = false;
            }
        })
        .catch(function () {
            $result.html("<div class='alert alert-danger p-2'><?php print addslashes(_("Request failed.")); ?></div>");
            btn.disabled = false;
        });
    });

    $(document).off('click.csrSign').on('click.csrSign', '.modal-execute', function () {
        var caId   = parseInt(document.getElementById('sign-ca-id').value);
        var days   = parseInt(document.getElementById('sign-days').value);
        var zoneEl = document.getElementById('sign-zone-id');
        var zoneId = zoneEl ? parseInt(zoneEl.value) : 0;
        var $result = $('#sign-result');

        if (!days || days < 1) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("Validity days must be at least 1.")); ?></div>");
            return false;
        }

        var notifyEmail = (document.getElementById('sign-notify-email').value || '').trim();
        var payload = {
            csr_id:              <?php print (int)$csr_id; ?>,
            ca_id:               caId,
            days:                days,
            zone_id:             zoneId,
            override_extensions: overrideCb.checked,
            notify_email:        notifyEmail || null,
        };

        if (overrideCb.checked) {
            payload.key_usage     = [];
            payload.ext_key_usage = [];
            document.querySelectorAll('.sign-ku:checked').forEach(function (el) { payload.key_usage.push(el.value); });
            document.querySelectorAll('.sign-eku:checked').forEach(function (el) { payload.ext_key_usage.push(el.value); });
        }

        var $btn = $(this).prop('disabled', true).text(<?php print json_encode(_("Signing...")); ?>);
        $result.html('');

        fetch('/route/ajax/csr/sign.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status === 'ok') {
                var msg = "<div class='alert alert-success p-2'><?php print addslashes(_("Certificate signed and imported successfully.")); ?></div>";
                if (data.mail_error) {
                    msg += "<div class='alert alert-warning p-2'><?php print addslashes(_("Certificate issued but email could not be sent:")); ?> " + data.mail_error + "</div>";
                } else if (notifyEmail) {
                    msg += "<div class='alert alert-info p-2'><?php print addslashes(_("Notification email sent to")); ?> " + notifyEmail + "</div>";
                }
                $result.html(msg);
                setTimeout(function () {
                    $('#modal1').modal('hide');
                    if (typeof window.refreshCsrTable === 'function') window.refreshCsrTable();
                }, notifyEmail ? 1800 : 900);
            } else {
                $result.html("<div class='alert alert-danger p-2'>" + (data.message || '<?php print addslashes(_("Signing failed.")); ?>') + "</div>");
                $btn.prop('disabled', false).text(<?php print json_encode(_("Sign")); ?>);
            }
        })
        .catch(function () {
            $result.html("<div class='alert alert-danger p-2'><?php print addslashes(_("Request failed.")); ?></div>");
            $btn.prop('disabled', false).text(<?php print json_encode(_("Sign")); ?>);
        });

        return false;
    });
})();
</script>

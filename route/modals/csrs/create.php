<?php

/**
 * Modal: generate a new CSR with optional template pre-fill.
 */

require('../../../functions/autoload.php');
$User->validate_session(false, false, false);

// Pre-fill from an existing certificate (renew flow) or an existing CSR
$renew_cert_id = (int)($_GET['cert_id'] ?? 0);
$renew_csr_id  = (int)($_GET['csr_id']  ?? 0);
$prefill = ['cn' => '', 'sans' => '', 'key_algo' => 'RSA', 'key_size' => 4096,
            'country' => '', 'state' => '', 'locality' => '', 'org' => '', 'ou' => '', 'email' => '',
            'key_usage' => null, 'ext_key_usage' => null];
$is_renew   = false;
$renew_t_id = 0;

if ($renew_csr_id > 0) {
    if ($user->admin == "1") {
        $renew_csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ?", [$renew_csr_id]);
    } else {
        $renew_csr = $Database->getObjectQuery("SELECT * FROM csrs WHERE id = ? AND t_id = ?", [$renew_csr_id, $user->t_id]);
    }
    if ($renew_csr) {
        $is_renew              = true;
        $renew_t_id            = (int)$renew_csr->t_id;
        $prefill['cn']         = $renew_csr->cn       ?? '';
        $prefill['country']    = $renew_csr->country  ?? '';
        $prefill['state']      = $renew_csr->state    ?? '';
        $prefill['locality']   = $renew_csr->locality ?? '';
        $prefill['org']        = $renew_csr->org      ?? '';
        $prefill['ou']         = $renew_csr->ou       ?? '';
        $prefill['email']      = $renew_csr->email    ?? '';
        $prefill['key_algo']   = $renew_csr->key_algo ?? 'RSA';
        $prefill['key_size']   = (int)($renew_csr->key_size ?? 4096);
        $sans_stored = trim($renew_csr->sans ?? '');
        if (empty($sans_stored) && !empty($renew_csr->csr_pem)) {
            $pem_sans    = $SSL->csr_extract_sans($renew_csr->csr_pem);
            $sans_stored = implode("\n", array_filter($pem_sans, fn($s) => $s !== $renew_csr->cn));
        }
        $prefill['sans'] = $sans_stored;
        if (!empty($renew_csr->extensions)) {
            $ext = json_decode($renew_csr->extensions, true) ?? [];
        } elseif (!empty($renew_csr->csr_pem)) {
            $ext = $SSL->csr_extract_extensions($renew_csr->csr_pem);
        } else {
            $ext = [];
        }
        $prefill['key_usage']     = $ext['keyUsage']    ?? [];
        $prefill['ext_key_usage'] = $ext['extKeyUsage'] ?? [];
    }
}

if ($renew_cert_id > 0) {
    if ($user->admin == "1") {
        $renew_cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ?", [$renew_cert_id]);
    } else {
        $renew_cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ? AND t_id = ?", [$renew_cert_id, $user->t_id]);
    }
    if ($renew_cert) {
        $is_renew   = true;
        $renew_t_id = (int)$renew_cert->t_id;
        $parsed     = openssl_x509_parse($renew_cert->certificate);
        $subj       = $parsed['subject'] ?? [];
        $prefill['cn']      = $subj['CN'] ?? '';
        $prefill['country'] = $subj['C']  ?? '';
        $prefill['state']   = $subj['ST'] ?? '';
        $prefill['locality']= $subj['L']  ?? '';
        $prefill['org']     = $subj['O']  ?? '';
        $prefill['ou']      = $subj['OU'] ?? '';
        $prefill['email']   = $subj['emailAddress'] ?? '';

        // Extract SANs (excluding the CN itself)
        // openssl_x509_parse returns "IP Address:x.x.x.x" not "IP:x.x.x.x"
        $san_raw   = $parsed['extensions']['subjectAltName'] ?? '';
        $sans_list = [];
        foreach (explode(',', $san_raw) as $entry) {
            $entry = trim($entry);
            if (preg_match('/^DNS:(.+)$/i', $entry, $m)) {
                if ($m[1] !== $prefill['cn']) $sans_list[] = $m[1];
            } elseif (preg_match('/^IP(?:\s+Address)?:(.+)$/i', $entry, $m)) {
                $sans_list[] = trim($m[1]);
            }
        }
        $prefill['sans'] = implode("\n", $sans_list);

        // Infer key algo/size from public key
        $pub = openssl_pkey_get_public($renew_cert->certificate);
        if ($pub) {
            $details = openssl_pkey_get_details($pub);
            if (($details['type'] ?? null) === OPENSSL_KEYTYPE_EC) {
                $prefill['key_algo'] = 'EC';
                $prefill['key_size'] = ($details['bits'] ?? 256) >= 384 ? 384 : 256;
            } else {
                $prefill['key_algo'] = 'RSA';
                $prefill['key_size'] = in_array((int)($details['bits'] ?? 4096), [2048, 4096]) ? (int)$details['bits'] : 4096;
            }
        }

        // Always reset to empty for cert renew — if cert has no extensions, show nothing (not defaults)
        $prefill['key_usage']     = [];
        $prefill['ext_key_usage'] = [];

        // Extract KU and EKU from certificate extensions
        $ku_map = [
            'Digital Signature'  => 'digitalSignature',
            'Non Repudiation'    => 'contentCommitment',
            'Content Commitment' => 'contentCommitment',
            'Key Encipherment'   => 'keyEncipherment',
            'Data Encipherment'  => 'dataEncipherment',
            'Key Agreement'      => 'keyAgreement',
            'Certificate Sign'   => 'keyCertSign',
            'CRL Sign'           => 'cRLSign',
        ];
        $eku_map = [
            'TLS Web Server Authentication' => 'serverAuth',
            'TLS Web Client Authentication' => 'clientAuth',
            'Code Signing'                  => 'codeSigning',
            'E-mail Protection'             => 'emailProtection',
            'Email Protection'              => 'emailProtection',
            'Time Stamping'                 => 'timeStamping',
            'OCSP Signing'                  => 'OCSPSigning',
        ];
        $ku_raw  = $parsed['extensions']['keyUsage']         ?? '';
        $eku_raw = $parsed['extensions']['extendedKeyUsage'] ?? '';
        if (!empty($ku_raw)) {
            foreach (explode(',', $ku_raw) as $entry) {
                $entry = trim(ltrim(trim($entry), 'critical,'));
                if (isset($ku_map[$entry])) $prefill['key_usage'][] = $ku_map[$entry];
            }
            $prefill['key_usage'] = array_values(array_unique($prefill['key_usage']));
        }
        if (!empty($eku_raw)) {
            foreach (explode(',', $eku_raw) as $entry) {
                $entry = trim(ltrim(trim($entry), 'critical,'));
                if (isset($eku_map[$entry])) $prefill['ext_key_usage'][] = $eku_map[$entry];
            }
            $prefill['ext_key_usage'] = array_values(array_unique($prefill['ext_key_usage']));
        }
    }
}

// Load templates for this tenant
if ($user->admin == "1") {
    $templates = $Database->getObjectsQuery("SELECT * FROM csr_templates ORDER BY name");
} else {
    $templates = $Database->getObjectsQuery("SELECT * FROM csr_templates WHERE t_id = ? ORDER BY name", [$user->t_id]);
}
if (!$templates) $templates = [];

$content = '';

if ($is_renew) {
    $content .= "<div class='alert alert-info p-2 mb-2' style='font-size:13px'>"
              . "<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon me-1'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4' /><path d='M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4' /></svg>"
              . _("Fields pre-filled from the existing certificate. Review and adjust before generating.")
              . "</div>";
}

// Template selector
if (!empty($templates)) {
    $content .= "<div class='mb-3'>";
    $content .= "<label class='form-label text-secondary small'>" . _("Load from template") . "</label>";
    $content .= "<select id='csr-tpl-sel' class='form-select form-select-sm'>";
    $content .= "<option value=''>" . _("— manual input —") . "</option>";
    foreach ($templates as $tpl) {
        $data = htmlspecialchars(json_encode([
            'key_algo'      => $tpl->key_algo,
            'key_size'      => $tpl->key_size,
            'key_usage'     => !empty($tpl->key_usage)     ? json_decode($tpl->key_usage, true)     : null,
            'ext_key_usage' => !empty($tpl->ext_key_usage) ? json_decode($tpl->ext_key_usage, true) : null,
            'country'       => $tpl->country,
            'state'         => $tpl->state,
            'locality'      => $tpl->locality,
            'org'           => $tpl->org,
            'ou'            => $tpl->ou,
            'email'         => $tpl->email,
        ]), ENT_QUOTES);
        $content .= "<option value='{$tpl->id}' data-tpl='{$data}'>" . htmlspecialchars($tpl->name) . "</option>";
    }
    $content .= "</select>";
    $content .= "</div>";
}

$content .= "<form id='modal-form'>";
$content .= "<table class='table table-borderless table-sm align-middle'>";

$p = function(string $key) use ($prefill): string {
    return htmlspecialchars($prefill[$key] ?? '', ENT_QUOTES);
};

// Tenant selector — admin only
if ($user->admin == "1") {
    $all_tenants_for_form = $Tenants->get_all();
    $content .= "<tr><th style='width:140px'>" . _("Tenant") . " <span class='text-danger'>*</span></th><td>";
    $content .= "<select id='csr-tenant' name='t_id' class='form-select form-select-sm'>";
    foreach ($all_tenants_for_form as $t) {
        $sel = ($renew_t_id > 0 && (int)$t->id === $renew_t_id) ? " selected" : "";
        $content .= "<option value='" . (int)$t->id . "'{$sel}>" . htmlspecialchars($t->name) . "</option>";
    }
    $content .= "</select></td></tr>";
}

// CN
$content .= "<tr>";
$content .= "<th style='width:140px;white-space:nowrap'>" . _("Common name") . " <span class='text-danger'>*</span></th>";
$content .= "<td><input type='text' id='csr-cn' name='cn' class='form-control form-control-sm' placeholder='example.com' value='" . $p('cn') . "'></td>";
$content .= "</tr>";

// SANs
$content .= "<tr>";
$content .= "<th style='vertical-align:top;padding-top:8px'>" . _("Alt. names (SANs)") . "</th>";
$content .= "<td>";
$content .= "<textarea id='csr-sans' name='sans' class='form-control form-control-sm' rows='3' style='font-family:monospace;font-size:11px;' placeholder='sub.example.com&#10;*.example.com'>" . $p('sans') . "</textarea>";
$content .= "<small class='text-muted'>" . _("One per line — CN is always included automatically") . "</small>";
$content .= "</td></tr>";

// Key algorithm
$is_ec     = $prefill['key_algo'] === 'EC';
$algo_rsa  = $is_ec ? '' : 'selected';
$algo_ec   = $is_ec ? 'selected' : '';
$content .= "<tr>";
$content .= "<th>" . _("Key algorithm") . "</th>";
$content .= "<td><select id='csr-algo' name='key_algo' class='form-select form-select-sm' style='width:auto'>";
$content .= "<option value='RSA' {$algo_rsa}>RSA</option><option value='EC' {$algo_ec}>EC (ECDSA)</option>";
$content .= "</select></td></tr>";

// Key size (RSA and EC selects toggled by JS)
$sz_rsa   = !$is_ec ? $prefill['key_size'] : 4096;
$sz_ec    = $is_ec  ? $prefill['key_size'] : 256;
$content .= "<tr>";
$content .= "<th>" . _("Key size") . "</th>";
$content .= "<td>";
$content .= "<select id='csr-size-rsa' name='key_size' class='form-select form-select-sm' style='width:auto;" . ($is_ec ? 'display:none' : '') . "'>";
$content .= "<option value='2048'" . ($sz_rsa == 2048 ? ' selected' : '') . ">2048 bit</option>";
$content .= "<option value='4096'" . ($sz_rsa == 4096 ? ' selected' : '') . ">4096 bit</option>";
$content .= "</select>";
$content .= "<select id='csr-size-ec' name='key_size_ec' class='form-select form-select-sm' style='width:auto;" . ($is_ec ? '' : 'display:none') . "'>";
$content .= "<option value='256'" . ($sz_ec == 256 ? ' selected' : '') . ">P-256 (256 bit)</option>";
$content .= "<option value='384'" . ($sz_ec == 384 ? ' selected' : '') . ">P-384 (384 bit)</option>";
$content .= "</select>";
$content .= "</td></tr>";

// Extensions (collapsible, open by default)
$content .= "<tr><td colspan='2' style='padding-bottom:0;padding-top:8px'>";
$content .= "<hr><a class='text-secondary small' style='cursor:pointer;text-decoration:none' id='csr-ext-toggle'>" . _("Requested extensions") . " ▴</a>";
$content .= "</td></tr>";
$content .= "<tr id='csr-ext-rows'><td colspan='2' style='padding:4px 0 0 0'>";
$content .= "<table class='table table-borderless table-sm mb-0'>";

$ku_options = [
    'digitalSignature'  => _("Digital Signature"),
    'contentCommitment' => _("Content Commitment"),
    'keyEncipherment'   => _("Key Encipherment"),
    'dataEncipherment'  => _("Data Encipherment"),
    'keyAgreement'      => _("Key Agreement"),
    'keyCertSign'       => _("Certificate Sign"),
    'cRLSign'           => _("CRL Sign"),
];
$ku_default  = ['digitalSignature', 'keyEncipherment'];
$ku_initial  = $prefill['key_usage']     !== null ? $prefill['key_usage']     : $ku_default;

$content .= "<tr><td colspan='2'><span class='text-muted small fw-bold text-uppercase'>" . _("Key Usage") . "</span></td></tr>";
$content .= "<tr><td colspan='2'><div class='d-flex flex-wrap gap-2 pb-1'>";
foreach ($ku_options as $val => $label) {
    $checked = in_array($val, $ku_initial) ? " checked" : "";
    $content .= "<label class='d-flex align-items-center gap-1' style='cursor:pointer;white-space:nowrap'>";
    $content .= "<input type='checkbox' class='form-check-input csr-ku' value='{$val}'{$checked}> " . htmlspecialchars($label);
    $content .= "</label>";
}
$content .= "</div></td></tr>";

$eku_options = [
    'serverAuth'      => _("TLS Web Server Auth"),
    'clientAuth'      => _("TLS Web Client Auth"),
    'codeSigning'     => _("Code Signing"),
    'emailProtection' => _("Email Protection"),
    'timeStamping'    => _("Time Stamping"),
    'OCSPSigning'     => _("OCSP Signing"),
];
$eku_default  = ['serverAuth', 'clientAuth'];
$eku_initial  = $prefill['ext_key_usage'] !== null ? $prefill['ext_key_usage'] : $eku_default;

$content .= "<tr><td colspan='2' style='padding-top:6px'><span class='text-muted small fw-bold text-uppercase'>" . _("Extended Key Usage") . "</span></td></tr>";
$content .= "<tr><td colspan='2'><div class='d-flex flex-wrap gap-2'>";
foreach ($eku_options as $val => $label) {
    $checked = in_array($val, $eku_initial) ? " checked" : "";
    $content .= "<label class='d-flex align-items-center gap-1' style='cursor:pointer;white-space:nowrap'>";
    $content .= "<input type='checkbox' class='form-check-input csr-eku' value='{$val}'{$checked}> " . htmlspecialchars($label);
    $content .= "</label>";
}
$content .= "</div></td></tr>";

$content .= "</table>";
$content .= "</td></tr>";

// Subject fields (collapsible)
$content .= "<tr><td colspan='2' style='padding-bottom:0;padding-top:4px'>";
$content .= "<hr><a class='text-secondary small' style='cursor:pointer;text-decoration:none' id='csr-org-toggle'>" . _("Subject / organisation fields") . " ▾</a>";
$content .= "</td></tr>";
$org_open  = ($is_renew && ($prefill['org'] || $prefill['country'])) ? '' : 'display:none';
$org_arrow = $org_open === '' ? '▴' : '▾';
$content   = str_replace(
    _("Subject / organisation fields") . " ▾",
    _("Subject / organisation fields") . " {$org_arrow}",
    $content
);
$content .= "<tr id='csr-org-rows' style='{$org_open}'><td colspan='2' style='padding:0'>";
$content .= "<table class='table table-borderless table-sm align-middle mb-0'>";
$content .= "<tr><th style='width:140px'>" . _("Country (2 letters)") . "</th><td><input type='text' id='csr-country' name='country' class='form-control form-control-sm' maxlength='2' style='width:60px' value='" . $p('country') . "'></td></tr>";
$content .= "<tr><th>" . _("State / Province") . "</th><td><input type='text' id='csr-state' name='state' class='form-control form-control-sm' value='" . $p('state') . "'></td></tr>";
$content .= "<tr><th>" . _("Locality / City") . "</th><td><input type='text' id='csr-locality' name='locality' class='form-control form-control-sm' value='" . $p('locality') . "'></td></tr>";
$content .= "<tr><th>" . _("Organization") . "</th><td><input type='text' id='csr-org' name='org' class='form-control form-control-sm' value='" . $p('org') . "'></td></tr>";
$content .= "<tr><th>" . _("Org. Unit") . "</th><td><input type='text' id='csr-ou' name='ou' class='form-control form-control-sm' value='" . $p('ou') . "'></td></tr>";
$content .= "<tr><th>" . _("Email") . "</th><td><input type='text' id='csr-email' name='email' class='form-control form-control-sm' value='" . $p('email') . "'></td></tr>";
$content .= "</table>";
$content .= "</td></tr>";

$content .= "</table>";
$content .= "</form>";

$content .= "<div id='csr-gen-result' class='mt-2'></div>";

$modal_title = $is_renew ? _("Renew certificate — generate CSR") : _("Generate CSR");
$Modal->modal_id = "#modal2";
$Modal->modal_print($modal_title, $content, _("Generate CSR"), "", false, "azure");
?>

<script>
(function () {
    var algoSel = document.getElementById('csr-algo');
    var rsaSel  = document.getElementById('csr-size-rsa');
    var ecSel   = document.getElementById('csr-size-ec');

    function syncAlgo() {
        var isEc = algoSel.value === 'EC';
        rsaSel.style.display = isEc ? 'none' : 'inline-block';
        rsaSel.disabled      = isEc;
        ecSel.style.display  = isEc ? 'inline-block' : 'none';
        ecSel.disabled       = !isEc;
    }
    algoSel.addEventListener('change', syncAlgo);
    syncAlgo();

    // Toggle extensions section
    var extToggle = document.getElementById('csr-ext-toggle');
    var extRows   = document.getElementById('csr-ext-rows');
    extToggle.addEventListener('click', function () {
        var hidden = extRows.style.display === 'none';
        extRows.style.display = hidden ? '' : 'none';
        extToggle.textContent = extToggle.textContent.replace(hidden ? '▾' : '▴', hidden ? '▴' : '▾');
    });

    // Toggle org fields
    var orgToggle = document.getElementById('csr-org-toggle');
    var orgRows   = document.getElementById('csr-org-rows');
    orgToggle.addEventListener('click', function () {
        var hidden = orgRows.style.display === 'none';
        orgRows.style.display = hidden ? '' : 'none';
        orgToggle.textContent = orgToggle.textContent.replace(hidden ? '▾' : '▴', hidden ? '▴' : '▾');
    });

    <?php if (!empty($templates)): ?>
    document.getElementById('csr-tpl-sel').addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        var raw = opt.getAttribute('data-tpl');
        if (!raw) return;
        var tpl = JSON.parse(raw);
        if (tpl.key_algo) { algoSel.value = tpl.key_algo; syncAlgo(); }
        if (tpl.key_size) {
            (tpl.key_algo === 'EC' ? ecSel : rsaSel).value = String(tpl.key_size);
        }
        ['country','state','locality','org','ou','email'].forEach(function(f) {
            var el = document.getElementById('csr-' + f);
            if (el) el.value = tpl[f] || '';
        });
        if (tpl.org || tpl.country) {
            orgRows.style.display = '';
            orgToggle.textContent = orgToggle.textContent.replace('▾', '▴');
        }
        var ku = Array.isArray(tpl.key_usage) ? tpl.key_usage : [];
        document.querySelectorAll('.csr-ku').forEach(function(cb) {
            cb.checked = ku.indexOf(cb.value) !== -1;
        });
        var eku = Array.isArray(tpl.ext_key_usage) ? tpl.ext_key_usage : [];
        document.querySelectorAll('.csr-eku').forEach(function(cb) {
            cb.checked = eku.indexOf(cb.value) !== -1;
        });
    });
    <?php endif; ?>

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function downloadBlob(text, filename) {
        var blob = new Blob([text], {type: 'text/plain'});
        var url  = URL.createObjectURL(blob);
        var a    = document.createElement('a');
        a.href = url; a.download = filename; a.click();
        URL.revokeObjectURL(url);
    }

    var _csrPem = '', _pkeyPem = '';

    window.copyCsrText  = function() { navigator.clipboard && navigator.clipboard.writeText(_csrPem); };
    window.copyPkeyText = function() { navigator.clipboard && navigator.clipboard.writeText(_pkeyPem); };
    window.dlCsr        = function(fn) { downloadBlob(_csrPem, fn); };
    window.dlPkey       = function(fn) { downloadBlob(_pkeyPem, fn); };

    $(document).off('click.csrGen').on('click.csrGen', '.modal-execute', function () {
        var cn = (document.getElementById('csr-cn').value || '').trim();
        var $result = $('#csr-gen-result');

        if (!cn) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("Common name is required.")); ?></div>");
            return false;
        }

        var isEc = algoSel.value === 'EC';
        var keyUsage = [], extKeyUsage = [];
        document.querySelectorAll('.csr-ku:checked').forEach(function(el)  { keyUsage.push(el.value); });
        document.querySelectorAll('.csr-eku:checked').forEach(function(el) { extKeyUsage.push(el.value); });

        var payload = {
            cn:            cn,
            sans:          (document.getElementById('csr-sans').value || '').trim(),
            key_algo:      algoSel.value,
            key_size:      parseInt(isEc ? ecSel.value : rsaSel.value),
            key_usage:     keyUsage,
            ext_key_usage: extKeyUsage,
            country:       (document.getElementById('csr-country').value  || '').trim(),
            state:         (document.getElementById('csr-state').value    || '').trim(),
            locality:      (document.getElementById('csr-locality').value || '').trim(),
            org:           (document.getElementById('csr-org').value      || '').trim(),
            ou:            (document.getElementById('csr-ou').value       || '').trim(),
            email:         (document.getElementById('csr-email').value    || '').trim(),
        };
        <?php if ($user->admin == "1"): ?>
        var tenantSel = document.getElementById('csr-tenant');
        if (tenantSel) payload.t_id = parseInt(tenantSel.value);
        <?php endif; ?>
        <?php if ($renew_csr_id > 0): ?>
        payload.source_csr_id = <?php print $renew_csr_id; ?>;
        <?php endif; ?>

        var $btn = $(this).prop('disabled', true).text(<?php print json_encode(_("Generating...")); ?>);
        $result.html('');

        fetch('/route/ajax/csr/generate.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status !== 'ok') {
                $result.html("<div class='alert alert-danger p-2'>" + escHtml(data.message || <?php print json_encode(_("Generation failed.")); ?>) + "</div>");
                $btn.prop('disabled', false).text(<?php print json_encode(_("Generate CSR")); ?>);
                return;
            }

            _csrPem  = data.csr_pem;
            _pkeyPem = data.pkey_pem || '';
            var fnBase = cn.replace(/[^a-zA-Z0-9._-]/g,'_');

            var dl_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>';
            var cp_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 8m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /><path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2" /></svg>';

            var html = '<div class="mb-3">';
            html += '<div class="d-flex align-items-center justify-content-between mb-1">';
            html += '<b><?php print addslashes(_("CSR (Certificate Signing Request)")); ?></b>';
            html += '<div class="btn-group btn-group-sm">';
            html += '<button type="button" class="btn btn-sm bg-info-lt" onclick="copyCsrText()">' + cp_icon + ' <?php print addslashes(_("Copy")); ?></button>';
            html += '<button type="button" class="btn btn-sm bg-success-lt" onclick="dlCsr(\'' + fnBase + '.csr\')">' + dl_icon + ' <?php print addslashes(_("Download .csr")); ?></button>';
            html += '</div></div>';
            html += '<textarea class="form-control" rows="7" style="font-family:monospace;font-size:11px;" readonly>' + escHtml(data.csr_pem) + '</textarea>';
            html += '</div>';

            if (data.pkey_stored) {
                html += '<div class="alert alert-success p-2"><?php print addslashes(_("Private key stored securely (encrypted). Download it anytime from the CSR list.")); ?></div>';
            } else if (data.pkey_pem) {
                html += '<div class="mb-2">';
                html += '<div class="d-flex align-items-center justify-content-between mb-1">';
                html += '<b class="text-warning"><?php print addslashes(_("Private key — save it now, it will not be stored")); ?></b>';
                html += '<div class="btn-group btn-group-sm">';
                html += '<button type="button" class="btn btn-sm bg-info-lt" onclick="copyPkeyText()">' + cp_icon + ' <?php print addslashes(_("Copy")); ?></button>';
                html += '<button type="button" class="btn btn-sm bg-warning-lt" onclick="dlPkey(\'' + fnBase + '.key\')">' + dl_icon + ' <?php print addslashes(_("Download .key")); ?></button>';
                html += '</div></div>';
                html += '<textarea class="form-control" rows="7" style="font-family:monospace;font-size:11px;" readonly>' + escHtml(data.pkey_pem) + '</textarea>';
                html += '<small class="text-danger"><?php print addslashes(_("Encryption not configured for this tenant — the private key is shown once. Download or copy it now.")); ?></small>';
                html += '</div>';
            }

            $('.modal-body').html(html);
            $('.modal-execute').hide();
            if (typeof window.refreshCsrTable === 'function') window.refreshCsrTable();
        })
        .catch(function() {
            $result.html("<div class='alert alert-danger p-2'><?php print addslashes(_("Request failed.")); ?></div>");
            $btn.prop('disabled', false).text(<?php print json_encode(_("Generate CSR")); ?>);
        });

        return false;
    });
})();
</script>

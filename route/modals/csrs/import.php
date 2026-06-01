<?php

/**
 * Modal: import an existing CSR + private key (and optional signed certificate).
 */

require('../../../functions/autoload.php');
$User->validate_session(false, false, false);

$external    = !empty($_GET['external']);
$all_zones   = $Zones->get_all();
$has_zones   = !empty($all_zones);
$zone_options = '';
if ($user->admin == "1") {
    $by_tenant = [];
    foreach ($all_zones as $z) {
        $by_tenant[$z->tenant_name][] = $z;
    }
    foreach ($by_tenant as $tenant_name => $zones) {
        $zone_options .= "<optgroup label='" . htmlspecialchars($tenant_name) . "'>";
        foreach ($zones as $z) {
            $zone_options .= "<option value='" . (int)$z->id . "'>" . htmlspecialchars($z->name) . "</option>";
        }
        $zone_options .= "</optgroup>";
    }
} else {
    foreach ($all_zones as $z) {
        $zone_options .= "<option value='" . (int)$z->id . "'>" . htmlspecialchars($z->name) . "</option>";
    }
}

$content  = "";

// Tenant selector — admin only
if ($user->admin == "1") {
    $all_tenants = $Tenants->get_all();
    $content .= "<div class='mb-2'>";
    $content .= "<label class='form-label fw-bold'>" . _("Tenant") . " <span class='text-danger'>*</span></label>";
    $content .= "<select id='import-tenant-id' class='form-select form-select-sm'>";
    foreach ($all_tenants as $t) {
        $content .= "<option value='" . (int)$t->id . "'>" . htmlspecialchars($t->name) . "</option>";
    }
    $content .= "</select>";
    $content .= "</div>";
}

// CSR
$content .= "<div class='mb-2'>";
$content .= "<label class='form-label fw-bold'>" . _("CSR (PEM)") . " <span class='text-danger'>*</span></label>";
$content .= "<input type='file' id='import-csr-file' class='form-control form-control-sm mb-1' accept='.pem,.csr'>";
$content .= "<textarea id='import-csr-pem' class='form-control' rows='5' style='font-family:monospace;font-size:11px;'"
          . " placeholder='-----BEGIN CERTIFICATE REQUEST-----\n...\n-----END CERTIFICATE REQUEST-----'></textarea>";
$content .= "</div>";

// Private key (hidden for external imports)
if (!$external) {
    $content .= "<div class='mb-2'>";
    $content .= "<label class='form-label fw-bold'>" . _("Private key (PEM)") . " <span class='text-danger'>*</span></label>";
    $content .= "<input type='file' id='import-key-file' class='form-control form-control-sm mb-1' accept='.pem,.key'>";
    $content .= "<textarea id='import-key-pem' class='form-control' rows='5' style='font-family:monospace;font-size:11px;'"
              . " placeholder='-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----'></textarea>";
    $content .= "</div>";

    $content .= "<div id='import-passphrase-wrap' class='mb-2' style='display:none'>";
    $content .= "<label class='form-label'>" . _("Key passphrase") . " <span class='text-danger'>*</span></label>";
    $content .= "<input type='password' id='import-key-passphrase' class='form-control form-control-sm'"
              . " placeholder='" . _("Passphrase for encrypted private key") . "'>";
    $content .= "</div>";
}

// Certificate (optional)
$content .= "<div class='mb-2'>";
$content .= "<label class='form-label fw-bold'>" . _("Certificate (PEM)") . " <span class='text-muted fw-normal small ms-1'>" . _("optional") . "</span></label>";
$content .= "<input type='file' id='import-cert-file' class='form-control form-control-sm mb-1' accept='.pem,.crt,.cer'>";
$content .= "<textarea id='import-cert-pem' class='form-control' rows='5' style='font-family:monospace;font-size:11px;'"
          . " placeholder='-----BEGIN CERTIFICATE-----\n...\n-----END CERTIFICATE-----'></textarea>";
$content .= "</div>";

// Zone selector (revealed when cert PEM is entered)
if ($has_zones) {
    $content .= "<div id='import-zone-wrap' class='mb-2' style='display:none'>";
    $content .= "<label class='form-label'>" . _("Import certificate into zone") . " <span class='text-danger'>*</span></label>";
    $content .= "<select id='import-zone-id' class='form-select form-select-sm'>"
              . "<option value='0'>— " . _("select zone") . " —</option>"
              . $zone_options . "</select>";
    $content .= "<div class='form-text'>" . _("Required when a certificate is provided.") . "</div>";
    $content .= "</div>";
}

$content .= "<div id='import-csr-result' class='mt-2'></div>";

$Modal->modal_print(_("Import CSR"), $content, _("Import"), "", false, "info");
?>
<script>
(function () {
    function wireFileInput(fileId, taId, onChange) {
        document.getElementById(fileId).addEventListener('change', function () {
            var f = this.files[0];
            if (!f) return;
            var r = new FileReader();
            r.onload = function (e) {
                document.getElementById(taId).value = e.target.result;
                if (onChange) onChange(e.target.result);
            };
            r.readAsText(f);
        });
    }
    function togglePassphrase(val) {
        var encrypted = val.indexOf('ENCRYPTED') !== -1;
        document.getElementById('import-passphrase-wrap').style.display = encrypted ? '' : 'none';
        if (!encrypted) document.getElementById('import-key-passphrase').value = '';
    }

    wireFileInput('import-csr-file', 'import-csr-pem');
    <?php if (!$external): ?>
    wireFileInput('import-key-file', 'import-key-pem', togglePassphrase);
    <?php endif; ?>
    <?php if ($has_zones): ?>
    wireFileInput('import-cert-file', 'import-cert-pem', function (val) {
        document.getElementById('import-zone-wrap').style.display = val.trim() ? '' : 'none';
    });
    <?php else: ?>
    wireFileInput('import-cert-file', 'import-cert-pem');
    <?php endif; ?>

    <?php if (!$external): ?>
    document.getElementById('import-key-pem').addEventListener('input', function () {
        togglePassphrase(this.value);
    });
    <?php endif; ?>
    <?php if ($has_zones): ?>
    document.getElementById('import-cert-pem').addEventListener('input', function () {
        document.getElementById('import-zone-wrap').style.display = this.value.trim() ? '' : 'none';
    });
    <?php endif; ?>

    $(document).off('click.csrImport').on('click.csrImport', '.modal-execute', function () {
        var csrPem     = document.getElementById('import-csr-pem').value.trim();
        var keyPem     = <?php print $external ? "''" : "document.getElementById('import-key-pem').value.trim()"; ?>;
        var certPem    = document.getElementById('import-cert-pem').value.trim();
        var passphrase = <?php print $external ? "''" : "document.getElementById('import-key-passphrase').value"; ?>;
        var $result    = $('#import-csr-result');

        if (!csrPem) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("Please provide the CSR PEM.")); ?></div>");
            return false;
        }
        <?php if (!$external): ?>
        if (!keyPem) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("Please provide the private key PEM.")); ?></div>");
            return false;
        }
        <?php endif; ?>
        var payload = { csr_pem: csrPem<?php print $external ? ', external: true' : ''; ?> };
        <?php if ($user->admin == "1"): ?>
        payload.t_id = parseInt(document.getElementById('import-tenant-id').value);
        <?php endif; ?>
        if (keyPem) {
            payload.key_pem = keyPem;
            if (keyPem.indexOf('ENCRYPTED') !== -1) payload.key_passphrase = passphrase;
        }
        if (certPem) {
            <?php if ($has_zones): ?>
            var zoneId = parseInt(document.getElementById('import-zone-id').value);
            if (!zoneId) {
                $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("Please select a zone for the certificate.")); ?></div>");
                return false;
            }
            payload.zone_id = zoneId;
            <?php endif; ?>
            payload.cert_pem = certPem;
        }

        var $btn = $(this).prop('disabled', true);
        $result.html('');

        fetch('/route/ajax/csr/import.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status === 'ok') {
                $('#modal2').modal('hide');
                if (typeof window.refreshCsrTable === 'function') { window.refreshCsrTable(); }
            } else {
                $result.html("<div class='alert alert-danger p-2'>" + (data.message || '<?php print addslashes(_("Import failed.")); ?>') + "</div>");
                $btn.prop('disabled', false);
            }
        })
        .catch(function () {
            $result.html("<div class='alert alert-danger p-2'><?php print addslashes(_("Import failed.")); ?></div>");
            $btn.prop('disabled', false);
        });

        return false;
    });
})();
</script>

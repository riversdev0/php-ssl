<?php

/**
 * Modal: import a CA or intermediate CA certificate + private key.
 */

require('../../../functions/autoload.php');
$User->validate_session(false, false, false);

global $private_key_encryption_key;
$csrf_token = $User->create_csrf_token();

$content  = "<form id='modal-form'>";
$content .= "<table class='table table-borderless table-sm align-middle'>";

// Tenant selector — admin only
if ($user->admin === "1") {
    $all_tenants = $Tenants->get_all();
    $content .= "<tr><th style='width:130px'>" . _("Tenant") . " <span class='text-danger'>*</span></th><td>";
    $content .= "<select id='ca-tenant' name='t_id' class='form-select form-select-sm'>";
    foreach ($all_tenants as $t) {
        $has_enc = !empty($private_key_encryption_key[$t->id]);
        $label   = htmlspecialchars($t->name) . ($has_enc ? '' : ' (' . _("no encryption") . ')');
        $content .= "<option value='" . (int)$t->id . "' data-has-enc='" . ($has_enc ? '1' : '0') . "'>" . $label . "</option>";
    }
    $content .= "</select></td></tr>";
}

$content .= "<tr><th>" . _("Name") . " <span class='text-danger'>*</span></th>";
$content .= "<td><input type='text' id='ca-name' class='form-control form-control-sm' placeholder='" . _("e.g. Internal Root CA") . "'></td></tr>";

$content .= "</table>";
$content .= "</form>";

// CA certificate section
$content .= "<hr><p class='mb-1 fw-bold small text-muted text-uppercase'>" . _("CA Certificate") . "</p>";
$content .= "<p class='text-secondary small mb-2'>" . _("Select a .pem/.crt file, or a .p12/.pfx to auto-fill both certificate and private key.") . "</p>";
$content .= "<div class='mb-2'><input type='file' id='ca-cert-file' class='form-control form-control-sm' accept='.pem,.crt,.cer,.p12,.pfx'></div>";
$content .= "<div id='ca-pfx-passphrase-wrap' style='display:none' class='mb-2'>";
$content .= "<label class='form-label small'>" . _("P12/PFX passphrase") . "</label>";
$content .= "<div class='input-group input-group-sm'>";
$content .= "<input type='password' id='ca-pfx-passphrase' class='form-control form-control-sm' placeholder='" . _("Leave empty if not set") . "'>";
$content .= "<button type='button' class='btn btn-sm btn-secondary' id='ca-pfx-extract'>" . _("Extract") . "</button>";
$content .= "</div></div>";
$content .= "<textarea id='ca-cert-pem' class='form-control form-control-sm mb-2' rows='5'"
          . " style='font-family:monospace;font-size:11px;'"
          . " placeholder='-----BEGIN CERTIFICATE-----\n...\n-----END CERTIFICATE-----'></textarea>";

// Private key section
$content .= "<hr><p class='mb-1 fw-bold small text-muted text-uppercase'>" . _("CA Private Key") . " <span class='text-danger'>*</span></p>";
$content .= "<p class='text-secondary small mb-2'>" . _("Private key is required to sign certificates. It will be stored encrypted. Accepts .pem, .key or .p12/.pfx (key will be extracted).") . "</p>";
$content .= "<div class='mb-2'><input type='file' id='ca-key-file' class='form-control form-control-sm' accept='.pem,.key,.p12,.pfx'></div>";
$content .= "<div id='ca-key-pfx-wrap' style='display:none' class='mb-2'>";
$content .= "<label class='form-label small'>" . _("P12/PFX passphrase") . "</label>";
$content .= "<div class='input-group input-group-sm'>";
$content .= "<input type='password' id='ca-key-pfx-passphrase' class='form-control form-control-sm' placeholder='" . _("Leave empty if not set") . "'>";
$content .= "<button type='button' class='btn btn-sm btn-secondary' id='ca-key-pfx-extract'>" . _("Extract key") . "</button>";
$content .= "</div></div>";
$content .= "<textarea id='ca-key-pem' class='form-control form-control-sm mb-2' rows='5'"
          . " style='font-family:monospace;font-size:11px;'"
          . " placeholder='-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----'></textarea>";
$content .= "<div id='ca-passphrase-wrap' style='display:none'>";
$content .= "<label class='form-label small'>" . _("Key passphrase") . "</label>";
$content .= "<input type='password' id='ca-passphrase' class='form-control form-control-sm' placeholder='" . _("Leave empty if not set") . "'>";
$content .= "</div>";

$content .= "<div id='ca-import-result' class='mt-2'></div>";

$Modal->modal_print(_("Import Certificate Authority"), $content, _("Import"), "", false, "info");
?>
<script>
(function () {
    var _pfxFile = null;

    function togglePassphrase(pem) {
        var wrap = document.getElementById('ca-passphrase-wrap');
        wrap.style.display = pem.indexOf('ENCRYPTED') !== -1 ? '' : 'none';
    }

    function extractPfx(passphrase) {
        if (!_pfxFile) return;
        var $result = $('#ca-import-result');
        var $btn = $('#ca-pfx-extract').prop('disabled', true).text(<?php print json_encode(_("Extracting...")); ?>);
        var form = new FormData();
        form.append('pfx_file', _pfxFile);
        form.append('passphrase', passphrase || '');
        form.append('csrf_token', '<?php print $csrf_token; ?>');
        fetch('/route/ajax/cert-convert.php', { method: 'POST', body: form })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) {
                $result.html("<div class='alert alert-danger p-2'>" + data.error + "</div>");
            } else {
                if (data.pem)      { document.getElementById('ca-cert-pem').value = data.pem; }
                if (data.pkey_pem) { document.getElementById('ca-key-pem').value  = data.pkey_pem; togglePassphrase(data.pkey_pem); }
                $result.html('');
            }
            $btn.prop('disabled', false).text(<?php print json_encode(_("Extract")); ?>);
        })
        .catch(function () {
            $result.html("<div class='alert alert-danger p-2'><?php print addslashes(_("Extraction failed.")); ?></div>");
            $btn.prop('disabled', false).text(<?php print json_encode(_("Extract")); ?>);
        });
    }

    document.getElementById('ca-cert-file').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        var name = file.name.toLowerCase();
        if (name.endsWith('.p12') || name.endsWith('.pfx')) {
            _pfxFile = file;
            document.getElementById('ca-pfx-passphrase-wrap').style.display = '';
            // Try extraction immediately with empty passphrase
            extractPfx('');
        } else {
            _pfxFile = null;
            document.getElementById('ca-pfx-passphrase-wrap').style.display = 'none';
            var reader = new FileReader();
            reader.onload = function (e) { document.getElementById('ca-cert-pem').value = e.target.result; };
            reader.readAsText(file);
        }
    });

    document.getElementById('ca-pfx-extract').addEventListener('click', function () {
        extractPfx(document.getElementById('ca-pfx-passphrase').value || '');
    });

    var _keyPfxFile = null;

    function extractKeyPfx(passphrase) {
        if (!_keyPfxFile) return;
        var $result = $('#ca-import-result');
        var $btn = $('#ca-key-pfx-extract').prop('disabled', true).text(<?php print json_encode(_("Extracting...")); ?>);
        var form = new FormData();
        form.append('pfx_file', _keyPfxFile);
        form.append('passphrase', passphrase || '');
        form.append('csrf_token', '<?php print $csrf_token; ?>');
        fetch('/route/ajax/cert-convert.php', { method: 'POST', body: form })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) {
                $result.html("<div class='alert alert-danger p-2'>" + data.error + "</div>");
            } else {
                if (data.pkey_pem) {
                    document.getElementById('ca-key-pem').value = data.pkey_pem;
                    togglePassphrase(data.pkey_pem);
                }
                $result.html('');
            }
            $btn.prop('disabled', false).text(<?php print json_encode(_("Extract key")); ?>);
        })
        .catch(function () {
            $result.html("<div class='alert alert-danger p-2'><?php print addslashes(_("Extraction failed.")); ?></div>");
            $btn.prop('disabled', false).text(<?php print json_encode(_("Extract key")); ?>);
        });
    }

    document.getElementById('ca-key-file').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        var name = file.name.toLowerCase();
        if (name.endsWith('.p12') || name.endsWith('.pfx')) {
            _keyPfxFile = file;
            document.getElementById('ca-key-pfx-wrap').style.display = '';
            document.getElementById('ca-passphrase-wrap').style.display = 'none';
            extractKeyPfx('');
        } else {
            _keyPfxFile = null;
            document.getElementById('ca-key-pfx-wrap').style.display = 'none';
            var reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('ca-key-pem').value = e.target.result;
                togglePassphrase(e.target.result);
            };
            reader.readAsText(file);
        }
    });

    document.getElementById('ca-key-pfx-extract').addEventListener('click', function () {
        extractKeyPfx(document.getElementById('ca-key-pfx-passphrase').value || '');
    });

    document.getElementById('ca-key-pem').addEventListener('input', function () {
        togglePassphrase(this.value);
    });

    <?php if ($user->admin === "1"): ?>
    var tenantSel = document.getElementById('ca-tenant');
    function checkEncryption() {
        var opt = tenantSel.options[tenantSel.selectedIndex];
        var hasEnc = opt.getAttribute('data-has-enc') === '1';
        var $result = $('#ca-import-result');
        if (!hasEnc) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("Private key encryption is not configured for this tenant. CA import is not available.")); ?></div>");
        } else {
            $result.html('');
        }
    }
    tenantSel.addEventListener('change', checkEncryption);
    checkEncryption();
    <?php endif; ?>

    $(document).off('click.caImport').on('click.caImport', '.modal-execute', function () {
        var certPem = (document.getElementById('ca-cert-pem').value || '').trim();
        var keyPem  = (document.getElementById('ca-key-pem').value  || '').trim();
        var name    = (document.getElementById('ca-name').value      || '').trim();
        var $result = $('#ca-import-result');

        if (!name) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("Name is required.")); ?></div>");
            return false;
        }
        if (!certPem) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("CA certificate is required.")); ?></div>");
            return false;
        }
        if (!keyPem) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("CA private key is required.")); ?></div>");
            return false;
        }

        var payload = { name: name, cert_pem: certPem, key_pem: keyPem };
        if (keyPem.indexOf('ENCRYPTED') !== -1) {
            payload.passphrase = (document.getElementById('ca-passphrase').value || '');
        }
        <?php if ($user->admin === "1"): ?>
        payload.t_id = parseInt(document.getElementById('ca-tenant').value);
        <?php endif; ?>

        var $btn = $(this).prop('disabled', true);
        $result.html('');

        fetch('/route/ajax/ca/import.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status === 'ok') {
                $result.html("<div class='alert alert-success p-2'><?php print addslashes(_("CA imported successfully.")); ?></div>");
                setTimeout(function () { $('#modal1').modal('hide'); }, 800);
            } else {
                $result.html("<div class='alert alert-danger p-2'>" + (data.message || '<?php print addslashes(_("Import failed.")); ?>') + "</div>");
                $btn.prop('disabled', false);
            }
        })
        .catch(function () {
            $result.html("<div class='alert alert-danger p-2'><?php print addslashes(_("Request failed.")); ?></div>");
            $btn.prop('disabled', false);
        });

        return false;
    });
})();
</script>

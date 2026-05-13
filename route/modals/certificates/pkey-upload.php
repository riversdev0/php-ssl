<?php

/**
 * Modal: upload a private key for a certificate.
 * Loaded via data-bs-toggle="modal" — renders the form HTML only.
 */

require('../../../functions/autoload.php');
$User->validate_session(false, false, false);
$csrf_token = $User->create_csrf_token();

$cert_id = (int) ($_GET['cert_id'] ?? 0);

if ($cert_id <= 0) {
    $Modal->modal_print(_("Error"), "<div class='alert alert-danger'>"._("Invalid request.")."</div>", "", "", false, "danger");
    exit;
}

// Fetch cert — enforce tenant access
if ($user->admin === "1") {
    $cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ?", [$cert_id]);
} else {
    $cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ? AND t_id = ?", [$cert_id, $user->t_id]);
}

if (!$cert) {
    $Modal->modal_print(_("Error"), "<div class='alert alert-danger'>"._("Certificate not found.")."</div>", "", "", false, "danger");
    exit;
}

global $private_key_encryption_key;
if (empty($private_key_encryption_key[$cert->t_id])) {
    $Modal->modal_print(
        _("Private key encryption not configured"),
        "<div class='alert alert-warning'>"
            ._("No encryption key is configured for this tenant.")." "
            ._("Add an entry to \$private_key_encryption_key in config.php before uploading private keys.")
            ."</div>",
        "", "", false, "warning"
    );
    exit;
}

$cert_info = openssl_x509_parse($cert->certificate);
$cn        = htmlspecialchars($cert_info['subject']['CN'] ?? '');

$content  = "<div class='mb-3'>";
$content .= "<p class='text-secondary mb-2'>"
          . sprintf(_("Upload the private key for certificate <b>%s</b>."), $cn)
          . "</p>";
$content .= "<p class='text-secondary' style='font-size:12px;'>"
          . _("Paste the PEM private key below, or select a .key / .pem / .p12 / .pfx file.")
          . "</p>";
$content .= "</div>";

$content .= "<div class='mb-2'>";
$content .= "<label class='form-label'>"._("Select file (optional)")."</label>";
$content .= "<input type='file' id='pkey-file-input' class='form-control form-control-sm' accept='.key,.pem,.p12,.pfx'>";
$content .= "</div>";

$content .= "<div id='pkey-pfx-wrap' class='mb-2' style='display:none'>";
$content .= "<label class='form-label small'>" . _("P12/PFX passphrase") . "</label>";
$content .= "<div class='input-group input-group-sm'>";
$content .= "<input type='password' id='pkey-pfx-passphrase' class='form-control form-control-sm' placeholder='" . _("Leave empty if not set") . "'>";
$content .= "<button type='button' class='btn btn-sm btn-secondary' id='pkey-pfx-extract'>" . _("Extract key") . "</button>";
$content .= "</div></div>";

$content .= "<div class='mb-2'>";
$content .= "<label class='form-label'>"._("PEM private key")."</label>";
$content .= "<textarea id='pkey-pem-input' class='form-control' rows='8' "
          . "style='font-family:monospace;font-size:11px;' "
          . "placeholder='-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----'></textarea>";
$content .= "</div>";

$content .= "<div id='pkey-passphrase-wrap' class='mb-2' style='display:none'>";
$content .= "<label class='form-label'>"._("Key passphrase")." <span class='text-danger'>*</span></label>";
$content .= "<input type='password' id='pkey-passphrase-input' class='form-control form-control-sm'"
          . " placeholder='" . _("Passphrase for encrypted private key") . "'>";
$content .= "</div>";

$content .= "<div id='pkey-upload-result' class='mt-2'></div>";

$Modal->modal_print(_("Upload private key"), $content, _("Upload"), "", false, "info");
?>

<script>
(function () {
    var _pfxFile = null;

    function togglePassphrase(val) {
        var encrypted = val.indexOf('ENCRYPTED') !== -1;
        document.getElementById('pkey-passphrase-wrap').style.display = encrypted ? '' : 'none';
        if (!encrypted) document.getElementById('pkey-passphrase-input').value = '';
    }

    function extractPfx(passphrase) {
        if (!_pfxFile) return;
        var $result = $('#pkey-upload-result');
        var $btn = $('#pkey-pfx-extract').prop('disabled', true).text(<?php print json_encode(_("Extracting...")); ?>);
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
                if (data.pkey_pem) {
                    document.getElementById('pkey-pem-input').value = data.pkey_pem;
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

    // File → textarea
    document.getElementById('pkey-file-input').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        var name = file.name.toLowerCase();
        if (name.endsWith('.p12') || name.endsWith('.pfx')) {
            _pfxFile = file;
            document.getElementById('pkey-pfx-wrap').style.display = '';
            document.getElementById('pkey-passphrase-wrap').style.display = 'none';
            extractPfx('');
        } else {
            _pfxFile = null;
            document.getElementById('pkey-pfx-wrap').style.display = 'none';
            var reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('pkey-pem-input').value = e.target.result;
                togglePassphrase(e.target.result);
            };
            reader.readAsText(file);
        }
    });

    document.getElementById('pkey-pfx-extract').addEventListener('click', function () {
        extractPfx(document.getElementById('pkey-pfx-passphrase').value || '');
    });

    document.getElementById('pkey-pem-input').addEventListener('input', function () {
        togglePassphrase(this.value);
    });

    // Upload on modal confirm
    $(document).off('click.pkeyUpload').on('click.pkeyUpload', '.modal-execute', function () {
        var pem        = document.getElementById('pkey-pem-input').value.trim();
        var passphrase = document.getElementById('pkey-passphrase-input').value;
        var $result    = $('#pkey-upload-result');

        if (!pem) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("Please paste or select a private key.")); ?></div>");
            return false;
        }
        var $btn = $(this).prop('disabled', true);
        $result.html('');

        var payload = { certificate_id: <?php print (int)$cert_id; ?>, pem: pem };
        if (pem.indexOf('ENCRYPTED') !== -1) payload.passphrase = passphrase;

        fetch('/route/ajax/pkey-upload.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status === 'ok') {
                $('#modal1').modal('hide');
                location.reload();
            } else {
                $result.html("<div class='alert alert-danger p-2'>" + (data.message || '<?php print addslashes(_("Upload failed.")); ?>') + "</div>");
                $btn.prop('disabled', false);
            }
        })
        .catch(function () {
            $result.html("<div class='alert alert-danger p-2'><?php print addslashes(_("Upload failed.")); ?></div>");
            $btn.prop('disabled', false);
        });

        return false;
    });
})();
</script>

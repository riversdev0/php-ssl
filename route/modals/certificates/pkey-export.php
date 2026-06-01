<?php

/**
 * Modal: export private key as PEM or PKCS12.
 * Loaded via data-bs-toggle="modal" — renders the form HTML only.
 */

require('../../../functions/autoload.php');
$User->validate_session(false, false, false);

$cert_id        = (int) ($_GET['cert_id'] ?? 0);
$default_format = in_array($_GET['format'] ?? '', ['pem', 'p12']) ? $_GET['format'] : 'pem';

if ($cert_id <= 0) {
    $Modal->modal_print(_("Error"), "<div class='alert alert-danger'>" . _("Invalid request.") . "</div>", "", "", false, "danger");
    exit;
}

if ($user->admin == "1") {
    $cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ?", [$cert_id]);
} else {
    $cert = $Database->getObjectQuery("SELECT * FROM certificates WHERE id = ? AND t_id = ?", [$cert_id, $user->t_id]);
}

if (!$cert || !$cert->pkey_id) {
    $Modal->modal_print(_("Error"), "<div class='alert alert-danger'>" . _("No private key stored for this certificate.") . "</div>", "", "", false, "danger");
    exit;
}

$cert_info = openssl_x509_parse($cert->certificate);
$cn        = htmlspecialchars($cert_info['subject']['CN'] ?? '');

$pem_checked = $default_format === 'pem' ? 'checked' : '';
$p12_checked = $default_format === 'p12' ? 'checked' : '';

$content  = "<div class='mb-3'>";
$content .= "<p class='text-secondary mb-2'>" . sprintf(_("Export key for <b>%s</b>."), $cn) . "</p>";
$content .= "</div>";

$content .= "<div class='mb-3'>";
$content .= "<label class='form-label'>" . _("Format") . "</label><br>";
$content .= "<div class='form-check form-check-inline'>";
$content .= "<input class='form-check-input' type='radio' name='pkey-export-format' id='pkey-fmt-pem' value='pem' $pem_checked>";
$content .= "<label class='form-check-label' for='pkey-fmt-pem'>" . _("Private key (.pem)") . "</label>";
$content .= "</div>";
$content .= "<div class='form-check form-check-inline'>";
$content .= "<input class='form-check-input' type='radio' name='pkey-export-format' id='pkey-fmt-p12' value='p12' $p12_checked>";
$content .= "<label class='form-check-label' for='pkey-fmt-p12'>" . _("Certificate + key (.p12)") . "</label>";
$content .= "</div>";
$content .= "</div>";

$content .= "<div>";
$content .= "<label class='form-label'>" . _("Password") . " <span class='text-secondary' style='font-size:11px;'>" . _("(optional — leave empty for no encryption)") . "</span></label>";
$content .= "<input type='password' id='pkey-export-password' class='form-control form-control-sm' autocomplete='new-password'>";
$content .= "</div>";

$content .= "<div id='pkey-export-result' class='mt-2'></div>";

$Modal->modal_print(_("Export key"), $content, _("Download"), "", false, "info");
?>

<script>
(function () {
    $(document).off('click.pkeyExport').on('click.pkeyExport', '.modal-execute', function () {
        var format   = $('input[name="pkey-export-format"]:checked').val();
        var password = document.getElementById('pkey-export-password').value;
        var $result  = $('#pkey-export-result');
        var $btn     = $(this).prop('disabled', true);
        $result.html('');

        fetch('/route/ajax/pkey-export.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                certificate_id: <?php print (int)$cert_id; ?>,
                format:   format,
                password: password
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status === 'ok') {
                var binary = atob(data.data);
                var bytes  = new Uint8Array(binary.length);
                for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
                var blob = new Blob([bytes], { type: data.mime });
                var url  = URL.createObjectURL(blob);
                var a    = document.createElement('a');
                a.href     = url;
                a.download = data.filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(function () { URL.revokeObjectURL(url); }, 100);
                $('#modal1').modal('hide');
            } else {
                $result.html("<div class='alert alert-danger p-2'>" + (data.message || '<?php print addslashes(_("Export failed.")); ?>') + "</div>");
                $btn.prop('disabled', false);
            }
        })
        .catch(function () {
            $result.html("<div class='alert alert-danger p-2'><?php print addslashes(_("Export failed.")); ?></div>");
            $btn.prop('disabled', false);
        });

        return false;
    });
})();
</script>

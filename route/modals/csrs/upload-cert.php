<?php

/**
 * Modal: upload a signed certificate for a CSR.
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

if ($csr->status === 'signed') {
    $Modal->modal_print(_("Already signed"), "<div class='alert alert-info'>"._("This CSR already has a linked certificate.")."</div>", "", "", false, "info");
    exit;
}

$all_zones   = $Zones->get_all();
$all_tenants = $Tenants->get_all();

if (empty($all_zones)) {
    $Modal->modal_print(_("No zones"), "<div class='alert alert-warning'>"._("No zones available. Please create a zone first.")."</div>", "", "", false, "warning");
    exit;
}

$cn_esc = htmlspecialchars($csr->cn);

$content  = "<p class='text-secondary mb-3'>" . sprintf(_("Paste the signed certificate for <b>%s</b> below."), $cn_esc) . "</p>";

$content .= "<div class='mb-3'>";
$content .= "<label class='form-label'>" . _("Zone") . "</label>";
$content .= "<select id='upload-zone-id' class='form-select form-select-sm'>";
if ($user->admin == "1") {
    $by_tenant = [];
    foreach ($all_zones as $z) { $by_tenant[$z->t_id][] = $z; }
    foreach ($all_tenants as $t) {
        if (empty($by_tenant[$t->id])) continue;
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
$content .= "<div class='form-text'>" . _("The certificate will be imported into this zone.") . "</div>";
$content .= "</div>";

$content .= "<div class='mb-2'>";
$content .= "<label class='form-label'>" . _("Select file (optional)") . "</label>";
$content .= "<input type='file' id='cert-upload-file' class='form-control form-control-sm' accept='.pem,.crt,.cer'>";
$content .= "</div>";

$content .= "<div class='mb-2'>";
$content .= "<label class='form-label'>" . _("Certificate PEM") . "</label>";
$content .= "<textarea id='cert-upload-pem' class='form-control' rows='9' style='font-family:monospace;font-size:11px;'"
          . " placeholder='-----BEGIN CERTIFICATE-----\n...\n-----END CERTIFICATE-----'></textarea>";
$content .= "</div>";

$content .= "<div id='cert-upload-result' class='mt-2'></div>";

$Modal->modal_print(_("Upload signed certificate"), $content, _("Import"), "", false, "success");
?>

<script>
(function () {
    document.getElementById('cert-upload-file').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('cert-upload-pem').value = e.target.result;
        };
        reader.readAsText(file);
    });

    $(document).off('click.csrCertUpload').on('click.csrCertUpload', '.modal-execute', function () {
        var pem    = document.getElementById('cert-upload-pem').value.trim();
        var zoneId = document.getElementById('upload-zone-id').value;
        var $result = $('#cert-upload-result');

        if (!pem) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("Please paste or select a certificate.")); ?></div>");
            return false;
        }

        var $btn = $(this).prop('disabled', true);
        $result.html('');

        fetch('/route/ajax/csr/cert-upload.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csr_id: <?php print (int)$csr_id; ?>, zone_id: parseInt(zoneId), pem: pem })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status === 'ok') {
                $('#modal1').modal('hide');
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

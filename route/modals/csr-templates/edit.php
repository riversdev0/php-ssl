<?php

/**
 * Modal: add or edit a CSR template.
 * GET ?id=<int> for edit, no id for add.
 */

require('../../../functions/autoload.php');
$User->validate_session(false, false, false);

$_GET = $User->strip_input_tags($_GET);
$tpl_id = (int)($_GET['id'] ?? 0);
$tpl    = null;

if ($tpl_id > 0) {
    if ($user->admin == "1") {
        $tpl = $Database->getObjectQuery("SELECT * FROM csr_templates WHERE id = ?", [$tpl_id]);
    } else {
        $tpl = $Database->getObjectQuery("SELECT * FROM csr_templates WHERE id = ? AND t_id = ?", [$tpl_id, $user->t_id]);
    }
    if (!$tpl) {
        $Modal->modal_print(_("Error"), "<div class='alert alert-danger'>" . _("Template not found.") . "</div>", "", "", false, "danger");
        exit;
    }
}

$title = $tpl ? _("Edit template") : _("Add template");

$v = function($field) use ($tpl) {
    return $tpl ? htmlspecialchars($tpl->$field ?? '') : '';
};

$content  = "<form id='modal-form'>";
$content .= "<input type='hidden' name='id' value='{$tpl_id}'>";
$content .= "<table class='table table-borderless table-sm align-middle'>";

// Tenant selector — admin only
if ($user->admin == "1") {
    $all_tenants = $Tenants->get_all();
    $tpl_t_id    = $tpl ? (int)$tpl->t_id : 0;
    $content .= "<tr><th style='width:140px'>" . _("Tenant") . " <span class='text-danger'>*</span></th><td>";
    $content .= "<select name='t_id' class='form-select form-select-sm'>";
    foreach ($all_tenants as $t) {
        $sel = ($tpl_t_id === (int)$t->id) ? " selected" : "";
        $content .= "<option value='" . (int)$t->id . "'{$sel}>" . htmlspecialchars($t->name) . "</option>";
    }
    $content .= "</select></td></tr>";
}

$content .= "<tr><th style='width:140px'>" . _("Template name") . " <span class='text-danger'>*</span></th>";
$content .= "<td><input type='text' name='name' class='form-control form-control-sm' value='" . $v('name') . "' placeholder='" . _("e.g. Acme Corp RSA-2048") . "'></td></tr>";

$algo_rsa = (!$tpl || $tpl->key_algo === 'RSA') ? 'selected' : '';
$algo_ec  = ($tpl && $tpl->key_algo === 'EC')   ? 'selected' : '';
$content .= "<tr><th>" . _("Key algorithm") . "</th>";
$content .= "<td><select name='key_algo' id='tpl-algo' class='form-select form-select-sm' style='width:auto'>";
$content .= "<option value='RSA' {$algo_rsa}>RSA</option>";
$content .= "<option value='EC' {$algo_ec}>EC (ECDSA)</option>";
$content .= "</select></td></tr>";

$sz_rsa = $tpl && $tpl->key_algo === 'RSA' ? (int)$tpl->key_size : 2048;
$sz_ec  = $tpl && $tpl->key_algo === 'EC'  ? (int)$tpl->key_size : 256;
$disp_rsa = ($tpl && $tpl->key_algo === 'EC') ? 'display:none' : '';
$disp_ec  = ($tpl && $tpl->key_algo === 'EC') ? '' : 'display:none';
$content .= "<tr><th>" . _("Key size") . "</th><td>";
$content .= "<select name='key_size' id='tpl-size-rsa' class='form-select form-select-sm' style='width:auto;{$disp_rsa}'>";
$content .= "<option value='2048'" . ($sz_rsa==2048?' selected':'') . ">2048 bit</option>";
$content .= "<option value='4096'" . ($sz_rsa==4096?' selected':'') . ">4096 bit</option>";
$content .= "</select>";
$content .= "<select name='key_size_ec' id='tpl-size-ec' class='form-select form-select-sm' style='width:auto;{$disp_ec}'>";
$content .= "<option value='256'" . ($sz_ec==256?' selected':'') . ">P-256 (256 bit)</option>";
$content .= "<option value='384'" . ($sz_ec==384?' selected':'') . ">P-384 (384 bit)</option>";
$content .= "</select>";
$content .= "</td></tr>";

// Extensions — restore saved values or use defaults
$ku_default  = ['digitalSignature', 'keyEncipherment'];
$eku_default = ['serverAuth', 'clientAuth'];
$saved_ku    = $tpl ? (!empty($tpl->key_usage)     ? json_decode($tpl->key_usage, true)     : []) : $ku_default;
$saved_eku   = $tpl ? (!empty($tpl->ext_key_usage) ? json_decode($tpl->ext_key_usage, true) : []) : $eku_default;

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

$content .= "<tr><td colspan='2' style='padding-top:25px;'><small class='text-muted'>" . _("Extensions (pre-fill when creating CSRs)") . "</small><hr></td></tr>";

$content .= "<tr><td colspan='2'><span class='text-muted small fw-bold text-uppercase'>" . _("Key Usage") . "</span></td></tr>";
$content .= "<tr><td colspan='2'><div class='d-flex flex-wrap gap-2 pb-1' >";
foreach ($ku_options as $val => $label) {
    $checked = in_array($val, $saved_ku ?? []) ? " checked" : "";
    $content .= "<label class='d-flex align-items-center gap-1' style='cursor:pointer;white-space:nowrap'>";
    $content .= "<input type='checkbox' class='form-check-input tpl-ku' name='key_usage[]' value='{$val}'{$checked}> " . htmlspecialchars($label);
    $content .= "</label>";
}
$content .= "</div></td></tr>";

$content .= "<tr><td colspan='2' style='padding-top:10px'><span class='text-muted small fw-bold text-uppercase'>" . _("Extended Key Usage") . "</span></td></tr>";
$content .= "<tr><td colspan='2'><div class='d-flex flex-wrap gap-2'>";
foreach ($eku_options as $val => $label) {
    $checked = in_array($val, $saved_eku ?? []) ? " checked" : "";
    $content .= "<label class='d-flex align-items-center gap-1' style='cursor:pointer;white-space:nowrap'>";
    $content .= "<input type='checkbox' class='form-check-input tpl-eku' name='ext_key_usage[]' value='{$val}'{$checked}> " . htmlspecialchars($label);
    $content .= "</label>";
}
$content .= "</div></td></tr>";

$content .= "<tr><td colspan='2' style='padding-top:25px;'><small class='text-muted'>" . _("Subject fields (optional — pre-fill when creating CSRs)") . "</small><hr></td></tr>";
$content .= "<tr><th>" . _("Country (2 letters)") . "</th><td><input type='text' name='country' class='form-control form-control-sm' maxlength='2' style='width:60px' value='" . $v('country') . "'></td></tr>";
$content .= "<tr><th>" . _("State / Province") . "</th><td><input type='text' name='state' class='form-control form-control-sm' value='" . $v('state') . "'></td></tr>";
$content .= "<tr><th>" . _("Locality / City") . "</th><td><input type='text' name='locality' class='form-control form-control-sm' value='" . $v('locality') . "'></td></tr>";
$content .= "<tr><th>" . _("Organization") . "</th><td><input type='text' name='org' class='form-control form-control-sm' value='" . $v('org') . "'></td></tr>";
$content .= "<tr><th>" . _("Org. Unit") . "</th><td><input type='text' name='ou' class='form-control form-control-sm' value='" . $v('ou') . "'></td></tr>";
$content .= "<tr><th>" . _("Email") . "</th><td><input type='text' name='email' class='form-control form-control-sm' value='" . $v('email') . "'></td></tr>";

$content .= "</table></form>";

$Modal->modal_print($title, $content, _("Save template"), "/route/modals/csr-templates/edit-submit.php", false, "info");
?>
<script>
(function () {
    var algo    = document.getElementById('tpl-algo');
    var rsa_sel = document.getElementById('tpl-size-rsa');
    var ec_sel  = document.getElementById('tpl-size-ec');
    function syncAlgo() {
        var isEc = algo.value === 'EC';
        rsa_sel.style.display = isEc ? 'none' : ''; rsa_sel.disabled = isEc;
        ec_sel.style.display  = isEc ? '' : 'none'; ec_sel.disabled  = !isEc;
    }
    algo.addEventListener('change', syncAlgo);
})();
</script>

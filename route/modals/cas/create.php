<?php

/**
 * Modal: generate a new CA certificate (self-signed root or intermediate signed by a parent CA).
 */

require('../../../functions/autoload.php');
$User->validate_session(false, false, false);
# validate permissions
$User->validate_user_permissions (3, true);

global $private_key_encryption_key;

// Load CAs with private keys for parent selector (scoped to user's tenant; admin sees all)
if ($user->admin == "1") {
    $parent_cas = $Database->getObjectsQuery(
        "SELECT ca.id, ca.name, ca.subject, ca.t_id FROM cas ca
         INNER JOIN pkey pk ON ca.pkey_id = pk.id
         WHERE pk.private_key_enc IS NOT NULL AND pk.private_key_enc != ''
         ORDER BY ca.name ASC", []
    );
} else {
    $parent_cas = $Database->getObjectsQuery(
        "SELECT ca.id, ca.name, ca.subject FROM cas ca
         INNER JOIN pkey pk ON ca.pkey_id = pk.id
         WHERE ca.t_id = ? AND pk.private_key_enc IS NOT NULL AND pk.private_key_enc != ''
         ORDER BY ca.name ASC", [$user->t_id]
    );
}
$all_tenants_map = $user->admin == "1" ? $Tenants->get_all() : [];

$content  = "<form id='modal-form'>";
$content .= "<table class='table table-borderless table-sm align-middle'>";

// Tenant selector — admin only
if ($user->admin == "1") {
    $all_tenants = $Tenants->get_all();
    $content .= "<tr><th style='width:130px'>" . _("Tenant") . " <span class='text-danger'>*</span></th><td>";
    $content .= "<select id='ca-tenant' class='form-select form-select-sm'>";
    foreach ($all_tenants as $t) {
        $has_enc = !empty($private_key_encryption_key[$t->id]);
        $label   = htmlspecialchars($t->name) . ($has_enc ? '' : ' (' . _("no encryption") . ')');
        $content .= "<option value='" . (int)$t->id . "' data-has-enc='" . ($has_enc ? '1' : '0') . "'>" . $label . "</option>";
    }
    $content .= "</select></td></tr>";
}

$content .= "<tr><th>" . _("Display name") . " <span class='text-danger'>*</span></th>";
$content .= "<td><input type='text' id='ca-name' class='form-control form-control-sm' placeholder='" . _("e.g. Internal Root CA") . "'></td></tr>";

// Key algorithm
$content .= "<tr><th>" . _("Key algorithm") . "</th><td>";
$content .= "<select id='ca-algo' class='form-select form-select-sm' style='width:auto'>";
$content .= "<option value='RSA'>RSA</option>";
$content .= "<option value='EC'>EC (ECDSA)</option>";
$content .= "</select></td></tr>";

// Key size
$content .= "<tr><th>" . _("Key size") . "</th><td>";
$content .= "<select id='ca-size-rsa' class='form-select form-select-sm' style='width:auto'>";
$content .= "<option value='2048'>2048 bit</option>";
$content .= "<option value='4096' selected>4096 bit</option>";
$content .= "</select>";
$content .= "<select id='ca-size-ec' class='form-select form-select-sm' style='width:auto;display:none'>";
$content .= "<option value='256'>P-256 (256 bit)</option>";
$content .= "<option value='384'>P-384 (384 bit)</option>";
$content .= "</select>";
$content .= "</td></tr>";

// Validity
$content .= "<tr><th>" . _("Validity (days)") . "</th>";
$content .= "<td><input type='number' id='ca-days' class='form-control form-control-sm' style='width:100px' value='3650' min='1' max='36500'></td></tr>";

// Parent CA
$content .= "<tr><th>" . _("Parent CA") . "</th><td>";
$content .= "<select id='ca-parent' class='form-select form-select-sm'>";
$content .= "<option value=''>\xe2\x80\x94 " . _("None (self-signed root)") . " \xe2\x80\x94</option>";
if ($user->admin == "1") {
    $seen_tenants = [];
    foreach ($parent_cas as $pc) {
        $tid = (int)$pc->t_id;
        if (!isset($seen_tenants[$tid])) {
            if ($seen_tenants) $content .= "</optgroup>";
            $tname = isset($all_tenants_map[$tid]) ? htmlspecialchars($all_tenants_map[$tid]->name) : $tid;
            $content .= "<optgroup label='" . $tname . "'>";
            $seen_tenants[$tid] = true;
        }
        $label = htmlspecialchars($pc->name);
        if (!empty($pc->subject)) $label .= ' — <small class="text-muted">' . htmlspecialchars($pc->subject) . '</small>';
        $content .= "<option value='" . (int)$pc->id . "'>" . htmlspecialchars($pc->name) . (empty($pc->subject) ? '' : ' (' . htmlspecialchars($pc->subject) . ')') . "</option>";
    }
    if ($seen_tenants) $content .= "</optgroup>";
} else {
    foreach ($parent_cas as $pc) {
        $content .= "<option value='" . (int)$pc->id . "'>" . htmlspecialchars($pc->name) . (empty($pc->subject) ? '' : ' (' . htmlspecialchars($pc->subject) . ')') . "</option>";
    }
}
$content .= "</select></td></tr>";

// Pathlen (hidden unless parent selected)
$content .= "<tr id='ca-pathlen-row' style='display:none'><th>" . _("Path length") . "</th>";
$content .= "<td><input type='number' id='ca-pathlen' class='form-control form-control-sm' style='width:80px' value='0' min='0' max='10'>";
$content .= " <small class='text-muted'>" . _("Max chain depth below this CA (0 = end-entity only)") . "</small></td></tr>";

// Subject fields
$content .= "<tr><td colspan='2' style='padding-top:16px'><small class='text-muted'>" . _("Subject fields") . "</small><hr></td></tr>";
$content .= "<tr><th>" . _("Common Name") . " <span class='text-danger'>*</span></th>";
$content .= "<td><input type='text' id='ca-cn' class='form-control form-control-sm' placeholder='" . _("e.g. My Internal Root CA") . "'></td></tr>";
$content .= "<tr><th>" . _("Organization") . "</th>";
$content .= "<td><input type='text' id='ca-org' class='form-control form-control-sm'></td></tr>";
$content .= "<tr><th>" . _("Org. Unit") . "</th>";
$content .= "<td><input type='text' id='ca-ou' class='form-control form-control-sm'></td></tr>";
$content .= "<tr><th>" . _("Country (2 letters)") . "</th>";
$content .= "<td><input type='text' id='ca-country' class='form-control form-control-sm' maxlength='2' style='width:60px'></td></tr>";
$content .= "<tr><th>" . _("State / Province") . "</th>";
$content .= "<td><input type='text' id='ca-state' class='form-control form-control-sm'></td></tr>";
$content .= "<tr><th>" . _("Locality / City") . "</th>";
$content .= "<td><input type='text' id='ca-locality' class='form-control form-control-sm'></td></tr>";

$content .= "</table></form>";
$content .= "<div id='ca-create-result' class='mt-2'></div>";

$Modal->modal_print(_("Create Certificate Authority"), $content, _("Create CA"), "", false, "green");
?>
<script>
(function () {
    var algoSel = document.getElementById('ca-algo');
    var rsaSel  = document.getElementById('ca-size-rsa');
    var ecSel   = document.getElementById('ca-size-ec');

    function syncAlgo() {
        var isEc = algoSel.value === 'EC';
        rsaSel.style.display = isEc ? 'none' : 'inline-block'; rsaSel.disabled = isEc;
        ecSel.style.display  = isEc ? 'inline-block' : 'none'; ecSel.disabled  = !isEc;
    }
    algoSel.addEventListener('change', syncAlgo);

    var parentSel   = document.getElementById('ca-parent');
    var pathlenRow  = document.getElementById('ca-pathlen-row');
    function syncParent() {
        var hasParent = parentSel.value !== '';
        pathlenRow.style.display = hasParent ? '' : 'none';
    }
    parentSel.addEventListener('change', syncParent);

    <?php if ($user->admin == "1"): ?>
    var tenantSel = document.getElementById('ca-tenant');
    function checkEncryption() {
        var opt = tenantSel.options[tenantSel.selectedIndex];
        var hasEnc = opt.getAttribute('data-has-enc') === '1';
        var $result = $('#ca-create-result');
        if (!hasEnc) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("Private key encryption is not configured for this tenant. CA creation is not available.")); ?></div>");
        } else {
            $result.html('');
        }
    }
    tenantSel.addEventListener('change', checkEncryption);
    checkEncryption();
    <?php endif; ?>

    $(document).off('click.caCreate').on('click.caCreate', '.modal-execute', function () {
        var name    = (document.getElementById('ca-name').value    || '').trim();
        var cn      = (document.getElementById('ca-cn').value      || '').trim();
        var days    = parseInt(document.getElementById('ca-days').value);
        var $result = $('#ca-create-result');

        if (!name) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("Display name is required.")); ?></div>");
            return false;
        }
        if (!cn) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("Common Name is required.")); ?></div>");
            return false;
        }
        if (!days || days < 1) {
            $result.html("<div class='alert alert-warning p-2'><?php print addslashes(_("Validity must be at least 1 day.")); ?></div>");
            return false;
        }

        var isEc = algoSel.value === 'EC';
        var parentVal = parentSel.value;
        var payload = {
            name:         name,
            cn:           cn,
            key_algo:     algoSel.value,
            key_size:     parseInt(isEc ? ecSel.value : rsaSel.value),
            days:         days,
            org:          (document.getElementById('ca-org').value      || '').trim(),
            ou:           (document.getElementById('ca-ou').value       || '').trim(),
            country:      (document.getElementById('ca-country').value  || '').trim(),
            state:        (document.getElementById('ca-state').value    || '').trim(),
            locality:     (document.getElementById('ca-locality').value || '').trim(),
            parent_ca_id: parentVal ? parseInt(parentVal) : null,
            pathlen:      parentVal ? parseInt(document.getElementById('ca-pathlen').value) : null,
        };
        <?php if ($user->admin == "1"): ?>
        payload.t_id = parseInt(document.getElementById('ca-tenant').value);
        <?php endif; ?>

        var $btn = $(this).prop('disabled', true).text(<?php print json_encode(_("Creating...")); ?>);
        $result.html('');

        fetch('/route/ajax/ca/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status === 'ok') {
                $result.html("<div class='alert alert-success p-2'><?php print addslashes(_("CA created successfully.")); ?></div>");
                setTimeout(function () { $('#modal1').modal('hide'); }, 800);
            } else {
                $result.html("<div class='alert alert-danger p-2'>" + (data.message || '<?php print addslashes(_("Creation failed.")); ?>') + "</div>");
                $btn.prop('disabled', false).text(<?php print json_encode(_("Create CA")); ?>);
            }
        })
        .catch(function () {
            $result.html("<div class='alert alert-danger p-2'><?php print addslashes(_("Request failed.")); ?></div>");
            $btn.prop('disabled', false).text(<?php print json_encode(_("Create CA")); ?>);
        });

        return false;
    });
})();
</script>

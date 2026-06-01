<?php

#
# Import certificate manually into a zone
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, false);
# validate permissions
$User->validate_user_permissions (2, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

#
# Determine if a specific zone was pre-selected (link from zone detail page)
#
$has_zone = !empty($_GET['zone_name']);

if ($has_zone) {
	# validate tenant
	$_params['tenant'] = $_GET['tenant'];
	$User->validate_tenant (true);

	$zone   = $Zones->get_zone ($_GET['tenant'], $_GET['zone_name']);
	$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);
	$all_zones = false;
}
else {
	$zone      = null;
	$tenant    = null;
	$all_zones = $Zones->get_all();
}

#
# title
#
$title = _("Import certificate");

# error — specific zone requested but not found
if ($has_zone && (is_null($zone) || is_null($tenant))) {
	$content   = [];
	$content[] = $Result->show("danger", _("Invalid zone"), false, false, true);
	$btn_text  = "";
}
# error — no zone dropdown available (no zones at all)
elseif (!$has_zone && empty($all_zones)) {
	$content   = [];
	$content[] = $Result->show("danger", _("No zones available."), false, false, true);
	$btn_text  = "";
}
else {
	$csrfToken = $User->create_csrf_token();

	$content = [];
	$content[] = "<form id='modal-form'>";
	$content[] = "<input type='hidden' name='csrf_token' value='" . $csrfToken . "'>";

	$content[] = "<table class='table table-condensed table-borderless align-middle table-sm'>";

	# zone row — static (pre-selected) or dropdown
	if ($has_zone) {
		$content[] = "<input type='hidden' name='tenant' value='" . htmlspecialchars($_GET['tenant']) . "'>";
		$content[] = "<input type='hidden' name='zone_id' value='" . $zone->id . "'>";
		$content[] = "<tr>";
		$content[] = "	<th style='width:120px;'>" . _("Zone") . "</th>";
		$content[] = "	<td><b>" . htmlspecialchars($zone->name) . "</b></td>";
		$content[] = "</tr>";
	}
	else {
		# pre-load tenants indexed by id for href lookup
		$all_tenants  = $Tenants->get_all();
		$first_zone   = reset($all_zones);
		$first_href   = isset($all_tenants[$first_zone->t_id]) ? htmlspecialchars($all_tenants[$first_zone->t_id]->href) : '';
		$content[] = "<input type='hidden' name='tenant' id='zone-tenant-hidden' value='" . $first_href . "'>";

		$content[] = "<tr>";
		$content[] = "	<th style='width:120px;'>" . _("Zone") . "</th>";
		$content[] = "	<td>";
		$content[] = "		<select name='zone_id' id='zone-select' class='form-select form-select-sm'>";
		if ($user->admin == "1") {
			$by_tenant = [];
			foreach ($all_zones as $z) {
				$by_tenant[$z->tenant_name][] = $z;
			}
			foreach ($by_tenant as $tenant_name => $zones) {
				$content[] = "			<optgroup label='" . htmlspecialchars($tenant_name) . "'>";
				foreach ($zones as $z) {
					$t_href = isset($all_tenants[$z->t_id]) ? htmlspecialchars($all_tenants[$z->t_id]->href) : '';
					$content[] = "				<option value='" . $z->id . "' data-tenant='" . $t_href . "'>" . htmlspecialchars($z->name) . "</option>";
				}
				$content[] = "			</optgroup>";
			}
		} else {
			foreach ($all_zones as $z) {
				$t_href = isset($all_tenants[$z->t_id]) ? htmlspecialchars($all_tenants[$z->t_id]->href) : '';
				$content[] = "			<option value='" . $z->id . "' data-tenant='" . $t_href . "'>" . htmlspecialchars($z->name) . "</option>";
			}
		}
		$content[] = "		</select>";
		$content[] = "	</td>";
		$content[] = "</tr>";
	}

	$content[] = "<tr>";
	$content[] = "	<th style='vertical-align:top;padding-top:8px;'>" . _("Upload file") . "</th>";
	$content[] = "	<td>";
	$content[] = "		<input type='file' id='cert-file-input' accept='.pem,.crt,.cer,.pfx,.p12' style='display:none'>";
	$content[] = "		<div class='input-group input-group-sm'>";
	$content[] = "			<button type='button' class='btn btn-sm bg-info-lt' onclick='document.getElementById(\"cert-file-input\").click()'>" . _("Choose file") . "</button>";
	$content[] = "			<span class='form-control text-muted' id='cert-file-label' style='cursor:pointer' onclick='document.getElementById(\"cert-file-input\").click()'>" . _("No file chosen") . "</span>";
	$content[] = "		</div>";
	$content[] = "		<small class='text-muted'>" . _("Select a .pem / .crt / .cer / .pfx / .p12 file, or paste below") . "</small>";
	$content[] = "	</td>";
	$content[] = "</tr>";

	$content[] = "<tr id='pfx-passphrase-row' style='display:none'>";
	$content[] = "	<th style='vertical-align:middle;'>" . _("PFX passphrase") . "</th>";
	$content[] = "	<td>";
	$content[] = "		<div class='input-group input-group-sm'>";
	$content[] = "			<input type='password' id='cert-pfx-passphrase' class='form-control' placeholder='" . _("Leave empty if not protected") . "'>";
	$content[] = "			<button type='button' id='pfx-convert-btn' class='btn btn-sm bg-info-lt'>" . _("Convert to PEM") . "</button>";
	$content[] = "		</div>";
	$content[] = "		<div id='pfx-convert-error' class='text-danger small mt-1' style='display:none'></div>";
	$content[] = "	</td>";
	$content[] = "</tr>";

	$content[] = "<tr>";
	$content[] = "	<th style='vertical-align:top;padding-top:8px;'>" . _("Certificate") . "</th>";
	$content[] = "	<td>";
	$content[] = "		<textarea name='certificate' id='cert-pem-textarea' class='form-control' rows='8' style='font-family:monospace;font-size:11px;' placeholder='-----BEGIN CERTIFICATE-----\n...\n-----END CERTIFICATE-----'></textarea>";
	$content[] = "		<small class='text-muted'>" . _("PEM-encoded certificate (-----BEGIN CERTIFICATE----- block)") . "</small>";
	$content[] = "	</td>";
	$content[] = "</tr>";

	// Check if encryption is configured for at least one tenant the user can access
	global $private_key_encryption_key;
	$pkey_enc_available = $user->admin == "1"
		? !empty($private_key_encryption_key)
		: !empty($private_key_encryption_key[(int)$user->t_id]);

	if ($pkey_enc_available) {
		$content[] = "<tr>";
		$content[] = "	<th style='vertical-align:top;padding-top:8px;'>" . _("Private key") . " <span class='text-muted fw-normal small'>(" . _("optional") . ")</span></th>";
		$content[] = "	<td>";
		$content[] = "		<input type='file' id='pkey-file-input' accept='.pem,.key' style='display:none'>";
		$content[] = "		<div class='input-group input-group-sm mb-1'>";
		$content[] = "			<button type='button' class='btn btn-sm bg-info-lt' onclick='document.getElementById(\"pkey-file-input\").click()'>" . _("Choose file") . "</button>";
		$content[] = "			<span class='form-control text-muted' id='pkey-file-label' style='cursor:pointer' onclick='document.getElementById(\"pkey-file-input\").click()'>" . _("No file chosen") . "</span>";
		$content[] = "		</div>";
		$content[] = "		<textarea name='pkey_pem' id='pkey-pem-textarea' class='form-control' rows='4' style='font-family:monospace;font-size:11px;' placeholder='-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----'></textarea>";
		$content[] = "		<div id='pkey-passphrase-wrap' style='display:none' class='mt-1'>";
		$content[] = "			<input type='password' name='pkey_passphrase' id='pkey-passphrase' class='form-control form-control-sm' placeholder='" . _("Passphrase (if encrypted)") . "'>";
		$content[] = "		</div>";
		$content[] = "		<small class='text-muted'>" . _("Will be encrypted and stored. Leave empty to skip.") . "</small>";
		$content[] = "	</td>";
		$content[] = "</tr>";
	}

	$content[] = "</table>";
	$content[] = "</form>";

	$content[] = "<script>";
	$content[] = "var certCsrfToken = '" . $csrfToken . "';";

	# update hidden tenant field when zone dropdown changes
	if (!$has_zone) {
		$content[] = "var zoneSelect = document.getElementById('zone-select');";
		$content[] = "if (zoneSelect) {";
		$content[] = "    zoneSelect.addEventListener('change', function() {";
		$content[] = "        var opt = this.options[this.selectedIndex];";
		$content[] = "        document.getElementById('zone-tenant-hidden').value = opt.getAttribute('data-tenant');";
		$content[] = "    });";
		$content[] = "}";
	}

	$content[] = "function convertPfx() {";
	$content[] = "    var file = document.getElementById('cert-file-input').files[0];";
	$content[] = "    if (!file) return;";
	$content[] = "    var btn = document.getElementById('pfx-convert-btn');";
	$content[] = "    btn.disabled = true;";
	$content[] = "    btn.textContent = '" . _("Converting...") . "';";
	$content[] = "    document.getElementById('pfx-convert-error').style.display = 'none';";
	$content[] = "    var fd = new FormData();";
	$content[] = "    fd.append('pfx_file', file);";
	$content[] = "    fd.append('passphrase', document.getElementById('cert-pfx-passphrase').value);";
	$content[] = "    fd.append('csrf_token', certCsrfToken);";
	$content[] = "    fetch('/route/ajax/cert-convert.php', { method: 'POST', body: fd })";
	$content[] = "        .then(function(r) { return r.json(); })";
	$content[] = "        .then(function(data) {";
	$content[] = "            if (data.pem) {";
	$content[] = "                document.getElementById('cert-pem-textarea').value = data.pem.trim();";
	$content[] = "                var pkeyTa = document.getElementById('pkey-pem-textarea');";
	$content[] = "                if (pkeyTa && data.pkey_pem) {";
	$content[] = "                    pkeyTa.value = data.pkey_pem.trim();";
	$content[] = "                    document.getElementById('pkey-file-label').textContent = '" . addslashes(_("Extracted from PFX")) . "';";
	$content[] = "                    document.getElementById('pkey-file-label').classList.remove('text-muted');";
	$content[] = "                }";
	$content[] = "            } else {";
	$content[] = "                var errEl = document.getElementById('pfx-convert-error');";
	$content[] = "                errEl.textContent = data.error || '" . _("Conversion failed.") . "';";
	$content[] = "                errEl.style.display = '';";
	$content[] = "            }";
	$content[] = "        })";
	$content[] = "        .catch(function() {";
	$content[] = "            var errEl = document.getElementById('pfx-convert-error');";
	$content[] = "            errEl.textContent = '" . _("Request failed.") . "';";
	$content[] = "            errEl.style.display = '';";
	$content[] = "        })";
	$content[] = "        .finally(function() {";
	$content[] = "            btn.disabled = false;";
	$content[] = "            btn.textContent = '" . _("Convert to PEM") . "';";
	$content[] = "        });";
	$content[] = "}";
	$content[] = "document.getElementById('cert-file-input').addEventListener('change', function() {";
	$content[] = "    var file = this.files[0];";
	$content[] = "    if (!file) return;";
	$content[] = "    document.getElementById('cert-file-label').textContent = file.name;";
	$content[] = "    document.getElementById('cert-file-label').classList.remove('text-muted');";
	$content[] = "    var isPfx = file.name.toLowerCase().endsWith('.pfx');";
	$content[] = "    document.getElementById('pfx-passphrase-row').style.display = isPfx ? '' : 'none';";
	$content[] = "    document.getElementById('pfx-convert-error').style.display = 'none';";
	$content[] = "    document.getElementById('cert-pem-textarea').value = '';";
	$content[] = "    if (isPfx) {";
	$content[] = "        convertPfx();";
	$content[] = "    } else {";
	$content[] = "        var reader = new FileReader();";
	$content[] = "        reader.onload = function(e) {";
	$content[] = "            document.getElementById('cert-pem-textarea').value = e.target.result.trim();";
	$content[] = "        };";
	$content[] = "        reader.readAsText(file);";
	$content[] = "    }";
	$content[] = "});";
	$content[] = "document.getElementById('pfx-convert-btn').addEventListener('click', convertPfx);";

	if ($pkey_enc_available) {
		$content[] = "function togglePkeyPassphrase(val) {";
		$content[] = "    var wrap = document.getElementById('pkey-passphrase-wrap');";
		$content[] = "    if (wrap) wrap.style.display = val.indexOf('ENCRYPTED') !== -1 ? '' : 'none';";
		$content[] = "}";
		$content[] = "document.getElementById('pkey-file-input').addEventListener('change', function() {";
		$content[] = "    var file = this.files[0];";
		$content[] = "    if (!file) return;";
		$content[] = "    document.getElementById('pkey-file-label').textContent = file.name;";
		$content[] = "    document.getElementById('pkey-file-label').classList.remove('text-muted');";
		$content[] = "    var reader = new FileReader();";
		$content[] = "    reader.onload = function(e) {";
		$content[] = "        document.getElementById('pkey-pem-textarea').value = e.target.result.trim();";
		$content[] = "        togglePkeyPassphrase(e.target.result);";
		$content[] = "    };";
		$content[] = "    reader.readAsText(file);";
		$content[] = "});";
		$content[] = "document.getElementById('pkey-pem-textarea').addEventListener('input', function() {";
		$content[] = "    togglePkeyPassphrase(this.value);";
		$content[] = "});";
	}

	$content[] = "</script>";

	$btn_text = _("Import certificate");
}

# print modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "/route/modals/certificates/import-submit.php", false, "success");

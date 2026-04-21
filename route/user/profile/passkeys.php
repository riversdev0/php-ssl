<?php
/**
 * Passkeys section — included from route/user/profile/index.php
 * Variables available: $view_user, $user, $Database, $User
 */

// Check WebAuthn configuration
global $webauthn_origin, $webauthn_rpid;
$webauthn_configured = !empty($webauthn_origin) && !empty($webauthn_rpid);

// Fetch passkeys for this user
try {
    $passkeys = $Database->getObjectsQuery(
        "SELECT id, name, created_at, last_used_at FROM passkeys WHERE user_id = ? ORDER BY created_at DESC",
        [(int) $view_user->id]
    ) ?: [];
} catch (Exception $e) {
    $passkeys = [];
}

$force_passkey = !empty($view_user->force_passkey);
$pk_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" /><circle cx="15" cy="9" r="1" fill="currentColor" stroke="none" /></svg>';
?>

<div id="passkeys-card">

    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h3 class="card-title mb-0"><?php print $pk_icon; ?> <?php print _("Passkeys"); ?></h3>
        </div>
    </div>

    <?php if (!$webauthn_configured): ?>
    <div class="card-body pb-0">
        <div class="alert alert-warning p-2" style="font-size:12px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" /></svg>
            <?php print _("Passkeys are not configured."); ?>
            <?php print _("Set"); ?> <code>$webauthn_origin</code> <?php print _("and"); ?> <code>$webauthn_rpid</code> <?php print _("in"); ?> <code>config.php</code>.
        </div>
    </div>
    <?php endif; ?>

    <?php if ($force_passkey): ?>
    <div class="card-body">
        <div class="alert alert-info p-2" style="font-size:12px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9h.01" /><path d="M11 12h1v4h1" /><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /></svg>
            <?php print _("Password login is disabled — this account requires passkey authentication."); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add passkey form (hidden by default) -->
    <div id="passkey-add-form" style="display:none;" class="card-body border-bottom">
        <div class="d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <label class="form-label mb-1" style="font-size:12px;"><?php print _("Passkey name"); ?></label>
                <input type="text" id="passkey-name" class="form-control form-control-sm"
                       placeholder="<?php print htmlspecialchars(_('e.g. MacBook Touch ID'), ENT_QUOTES); ?>" maxlength="255">
            </div>
            <button type="button" class="btn btn-sm btn-primary" id="btn-register-passkey">
                <?php print _("Register"); ?>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-cancel-passkey">
                <?php print _("Cancel"); ?>
            </button>
        </div>
        <div id="passkey-register-result" class="mt-2"></div>
    </div>

    <div class="card-body">
        <?php if (empty($passkeys)): ?>
        <div class="text-secondary" style="font-size:13px;"><?php print _("No passkeys registered yet."); ?></div>
        <?php else: ?>
        <table class="table table-borderless table-sm table-md" id="passkeys-table">
            <thead>
                <tr>
                    <th><?php print _("Name"); ?></th>
                    <th><?php print _("Registered"); ?></th>
                    <th><?php print _("Last used"); ?></th>
                    <th style='width:10px'></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($passkeys as $pk): ?>
            <tr data-pk-id="<?php print (int) $pk->id; ?>">
                <td>
                    <?php print $pk_icon; ?>
                    <b><?php print htmlspecialchars($pk->name); ?></b>
                    <?php if (!empty($_SESSION['passkey_login']) && $_SESSION['passkey_login'] == $pk->id): ?>
                    <span class="badge bg-green-lt ms-1" style="font-size:10px;"><?php print _("active"); ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-secondary" style="font-size:12px;"><?php print htmlspecialchars($pk->created_at); ?></td>
                <td class="text-secondary" style="font-size:12px;">
                    <?php print $pk->last_used_at ? htmlspecialchars($pk->last_used_at) : '—'; ?>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-passkey"
                            data-pk-id="<?php print (int) $pk->id; ?>"
                            data-pk-name="<?php print htmlspecialchars($pk->name, ENT_QUOTES); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>


        <hr>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-passkey" <?php print !$webauthn_configured ? 'disabled' : ''; ?>>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
            <?php print _("Add passkey"); ?>
        </button>

    </div>

</div>

<script>
(function () {

    function showResult(el, type, msg) {
        el.textContent = '';
        var div = document.createElement('div');
        div.className = 'alert alert-' + type + ' p-2 mb-0';
        div.style.fontSize = '12px';
        div.textContent = msg;
        el.appendChild(div);
    }

    // ── Add passkey button ────────────────────────────────────────────────────
    document.getElementById('btn-add-passkey').addEventListener('click', function () {
        var form = document.getElementById('passkey-add-form');
        form.style.display = form.style.display === 'none' ? '' : 'none';
        if (form.style.display === '') document.getElementById('passkey-name').focus();
    });

    document.getElementById('btn-cancel-passkey').addEventListener('click', function () {
        document.getElementById('passkey-add-form').style.display = 'none';
        document.getElementById('passkey-register-result').textContent = '';
        document.getElementById('passkey-name').value = '';
    });

    // ── Register passkey ──────────────────────────────────────────────────────
    document.getElementById('btn-register-passkey').addEventListener('click', async function () {
        var name   = document.getElementById('passkey-name').value.trim();
        var result = document.getElementById('passkey-register-result');

        if (!name) {
            showResult(result, 'warning', <?php print json_encode(_("Please enter a name for the passkey.")); ?>);
            return;
        }
        if (!window.PublicKeyCredential) {
            showResult(result, 'danger', <?php print json_encode(_("Your browser does not support passkeys.")); ?>);
            return;
        }

        try {
            var resp = await fetch('/route/ajax/passkey-challenge.php?action=register');
            var data = await resp.json();
            if (data.status !== 'ok') throw new Error(data.message);

            var credential;
            if (typeof PublicKeyCredential.parseCreationOptionsFromJSON === 'function') {
                credential = await navigator.credentials.create({
                    publicKey: PublicKeyCredential.parseCreationOptionsFromJSON(data.options)
                });
            } else {
                var opts = data.options;
                opts.challenge = b64url_decode(opts.challenge);
                opts.user.id   = b64url_decode(opts.user.id);
                if (opts.excludeCredentials) {
                    opts.excludeCredentials = opts.excludeCredentials.map(function (c) {
                        c.id = b64url_decode(c.id); return c;
                    });
                }
                credential = await navigator.credentials.create({ publicKey: opts });
            }

            var encoded = (typeof credential.toJSON === 'function')
                ? credential.toJSON()
                : {
                    id:   credential.id,
                    type: credential.type,
                    response: {
                        clientDataJSON:    b64url_encode(credential.response.clientDataJSON),
                        attestationObject: b64url_encode(credential.response.attestationObject),
                    }
                };

            var reg     = await fetch('/route/ajax/passkey-register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: name, credential: encoded }),
            });
            var regData = await reg.json();
            if (regData.status !== 'ok') throw new Error(regData.message);

            showResult(result, 'success', <?php print json_encode(_("Passkey registered. Reloading...")); ?>);
            setTimeout(function () { location.reload(); }, 1200);
        }
        catch (err) {
            if (err.name === 'NotAllowedError') {
                showResult(result, 'warning', <?php print json_encode(_("Passkey registration was cancelled.")); ?>);
            } else {
                showResult(result, 'danger', err.message || <?php print json_encode(_("Registration failed.")); ?>);
            }
        }
    });

    // ── Delete passkey ────────────────────────────────────────────────────────
    document.querySelectorAll('.btn-delete-passkey').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var id   = this.dataset.pkId;
            var name = this.dataset.pkName;
            if (!confirm(<?php print json_encode(_("Delete passkey")); ?> + ' "' + name + '"?')) return;

            try {
                var resp = await fetch('/route/ajax/passkey-delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: parseInt(id) }),
                });
                var data = await resp.json();
                if (data.status !== 'ok') throw new Error(data.message);
                var row = document.querySelector('tr[data-pk-id="' + id + '"]');
                if (row) row.remove();
                if (!document.querySelector('#passkeys-table tbody tr')) { location.reload(); }
            }
            catch (err) {
                alert(<?php print json_encode(_("Error")); ?> + ': ' + (err.message || <?php print json_encode(_("Could not delete passkey.")); ?>));
            }
        });
    });

    // ── Base64URL helpers ─────────────────────────────────────────────────────
    function b64url_decode(str) {
        var b64 = str.replace(/-/g, '+').replace(/_/g, '/');
        while (b64.length % 4) b64 += '=';
        var bin = atob(b64);
        var buf = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) buf[i] = bin.charCodeAt(i);
        return buf.buffer;
    }
    function b64url_encode(buf) {
        var bytes = buf instanceof ArrayBuffer ? new Uint8Array(buf) : new Uint8Array(buf.buffer || buf);
        var bin   = '';
        bytes.forEach(function (b) { bin += String.fromCharCode(b); });
        return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }

})();
</script>

<?php
$_pk_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" /><circle cx="15" cy="9" r="1" fill="currentColor" stroke="none" /></svg>';

$_ud_passkeys = [];
try {
	$_ud_passkeys = $Database->getObjectsQuery(
		"SELECT id, name, created_at, last_used_at FROM passkeys WHERE user_id = ? ORDER BY created_at DESC",
		[(int) $view_user->id]
	) ?: [];
} catch (Exception $e) {}
?>

<div class="card-body">
	<h3 class="card-title"><?php print $_pk_icon; ?> <?php print _("Passkeys"); ?></h3>
</div>


<div class="card-body">
<div class="alert alert-info">
	<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9h.01" /><path d="M11 12h1v4h1" /><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /></svg>
	<?php print _("Passkeys can only be added by the user from their own profile."); ?>
</div>
</div>

<div class="card-body">
<?php if (empty($_ud_passkeys)): ?>
	<div class="text-secondary" style="font-size:13px;"><?php print _("No passkeys registered yet."); ?></div>
<?php else: ?>
	<table class="table table-borderless table-sm table-md" id="passkeys-table-admin">
		<thead>
			<tr>
				<th><?php print _("Name"); ?></th>
				<th><?php print _("Registered"); ?></th>
				<th><?php print _("Last used"); ?></th>
				<th style="width:10px"></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($_ud_passkeys as $_pk): ?>
		<tr data-pk-id="<?php print (int) $_pk->id; ?>">
			<td>
				<?php print $_pk_icon; ?>
				<b><?php print htmlspecialchars($_pk->name); ?></b>
			</td>
			<td class="text-secondary" style="font-size:12px;"><?php print htmlspecialchars($_pk->created_at); ?></td>
			<td class="text-secondary" style="font-size:12px;">
				<?php print $_pk->last_used_at ? htmlspecialchars($_pk->last_used_at) : '—'; ?>
			</td>
			<td>
				<button type="button" class="btn btn-sm btn-outline-danger btn-admin-delete-passkey"
				        data-pk-id="<?php print (int) $_pk->id; ?>"
				        data-pk-name="<?php print htmlspecialchars($_pk->name, ENT_QUOTES); ?>"
				        data-user-id="<?php print (int) $view_user->id; ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
				</button>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

</div>

<script>
(function () {
    document.querySelectorAll('.btn-admin-delete-passkey').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var id     = this.dataset.pkId;
            var name   = this.dataset.pkName;
            var userId = this.dataset.userId;
            if (!confirm(<?php print json_encode(_("Delete passkey")); ?> + ' "' + name + '"?')) return;

            try {
                var resp = await fetch('/route/ajax/passkey-delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: parseInt(id), user_id: parseInt(userId) }),
                });
                var data = await resp.json();
                if (data.status !== 'ok') throw new Error(data.message);
                var row = document.querySelector('#passkeys-table-admin tr[data-pk-id="' + id + '"]');
                if (row) row.remove();
                if (!document.querySelector('#passkeys-table-admin tbody tr')) { location.reload(); }
            }
            catch (err) {
                alert(<?php print json_encode(_("Error")); ?> + ': ' + (err.message || <?php print json_encode(_("Could not delete passkey.")); ?>));
            }
        });
    });
})();
</script>

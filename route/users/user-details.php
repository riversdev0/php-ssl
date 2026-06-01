<div class='card'>
	<div class='card-header'><?php print $icon_user; ?> <?php print htmlspecialchars($view_user->name); ?></div>
	<div>
	<table class='table table-borderless table-md table-hover table-td-top table-zones-details'>

		<tr>
			<td class='text-secondary' style='min-width:160px;width:180px;'><?php print _("Name"); ?></td>
			<td><b><?php print htmlspecialchars($view_user->name); ?></b></td>
		</tr>

		<tr>
			<td class='text-secondary'><?php print _("Email"); ?></td>
			<td><?php print htmlspecialchars($view_user->email); ?></td>
		</tr>

		<?php if ($user->admin == "1"): ?>
		<tr>
			<td class='text-secondary'><?php print _("Tenant"); ?></td>
			<td><?php print htmlspecialchars($view_tenant ? $view_tenant->name : "/"); ?></td>
		</tr>
		<?php endif; ?>

		<tr>
			<td class='text-secondary'><?php print _("Permission"); ?></td>
			<td><span class='badge badge-outline text-red'><?php print _($User->get_permissions_nice($view_user->permission)); ?></span></td>
		</tr>

		<tr>
			<td class='text-secondary'><?php print _("Days warning"); ?></td>
			<td>
				<span class='badge bg-info-lt' data-bs-toggle="tooltip" title="<?php print _("Days before expiry to warn"); ?>"><?php print (int)$view_user->days; ?> <?php print _("days"); ?></span>
			</td>
		</tr>

		<tr>
			<td class='text-secondary'><?php print _("Days expired"); ?></td>
			<td>
				<span class='badge bg-info-lt' data-bs-toggle="tooltip" title="<?php print _("Days after expiry to still warn"); ?>"><?php print (int)$view_user->days_expired; ?> <?php print _("days"); ?></span>
			</td>
		</tr>

		<?php
		$_view_passkeys = [];
		try {
			$_view_passkeys = $Database->getObjectsQuery(
				"SELECT id, name, created_at, last_used_at FROM passkeys WHERE user_id = ? ORDER BY created_at DESC",
				[(int) $view_user->id]
			) ?: [];
		} catch (Exception $e) {}
		$_pk_icon_sm = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon" style="vertical-align:-2px"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" /><circle cx="15" cy="9" r="1" fill="currentColor" stroke="none" /></svg>';
		?>
		<tr>
			<td class='text-secondary'><?php print _("Passkeys"); ?></td>
			<td>
				<?php if (empty($_view_passkeys)): ?>
					<span class="text-secondary" style="font-size:13px;"><?php print _("None"); ?></span>
				<?php else: ?>
					<?php foreach ($_view_passkeys as $_pk): ?>
						<span class="badge bg-blue-lt" style="margin-right:3px;"><?php print $_pk_icon_sm; ?> <?php print htmlspecialchars($_pk->name); ?></span>
					<?php endforeach; ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td class='text-secondary'><?php print _("Password login"); ?></td>
			<td>
				<?php if (!empty($view_user->force_passkey)): ?>
					<span class="badge bg-orange-lt"><?php print $_pk_icon_sm; ?> <?php print _("Disabled — passkey only"); ?></span>
				<?php else: ?>
					<span class="text-secondary" style="font-size:13px;"><?php print _("Enabled"); ?></span>
				<?php endif; ?>
			</td>
		</tr>

		<tr class='line'>
			<td class='text-secondary'><?php print _("Actions"); ?></td>
			<td>
				<div style='display:flex;flex-direction:column;gap:4px;align-items:flex-start;' class='actions'>
					<div>
						<a href='/route/modals/users/edit.php?action=edit&tenant=<?php print urlencode($_params['tenant']); ?>&id=<?php print (int)$view_user->id; ?>' class='btn btn-sm' data-bs-toggle='modal' data-bs-target='#modal1'><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-info"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415" /><path d="M16 5l3 3" /></svg> <?php print _("Edit user"); ?></a>
					</div>
					<div>
						<a href='/route/modals/users/edit.php?action=delete&tenant=<?php print urlencode($_params['tenant']); ?>&id=<?php print (int)$view_user->id; ?>' class='btn btn-sm' data-bs-toggle='modal' data-bs-target='#modal1'><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-danger"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg> <?php print _("Delete user"); ?></a>
					</div>
					<?php if($user->admin == "1" && !isset($_SESSION['impersonate_original']) && $view_user->email !== $user->email): ?>
					<div>
						<a href='/<?php print htmlspecialchars($user->href); ?>/user/impersonate/<?php print (int)$view_user->id; ?>/' class='btn btn-sm'><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-switch-3 text-warning"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17h5l1.67 -2.386m3.66 -5.227l1.67 -2.387h6" /><path d="M18 4l3 3l-3 3" /><path d="M3 7h5l7 10h6" /><path d="M18 20l3 -3l-3 -3" /></svg> <?php print _("Impersonate"); ?></a>
					</div>
					<?php endif; ?>
				</div>
			</td>
		</tr>

	</table>
	</div>
</div>

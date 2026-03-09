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

		<tr>
			<td class='text-secondary'><?php print _("Tenant"); ?></td>
			<td><?php print htmlspecialchars($view_tenant ? $view_tenant->name : "/"); ?></td>
		</tr>

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

		<tr class='line'>
			<td class='text-secondary'><?php print _("Actions"); ?></td>
			<td>
				<div style='display:flex;flex-direction:column;gap:4px;align-items:flex-start;' class='actions'>
					<div>
						<a href='/route/modals/users/edit.php?action=edit&tenant=<?php print urlencode($user->href); ?>&id=<?php print (int)$view_user->id; ?>' class='btn btn-sm' data-bs-toggle='modal' data-bs-target='#modal1'>
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-info"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415" /><path d="M16 5l3 3" /></svg>
							<?php print _("Edit profile"); ?>
						</a>
					</div>
					<?php if($view_user->permission == 3): ?>
					<div>
						<a href='/route/modals/users/edit.php?action=delete&tenant=<?php print urlencode($user->href); ?>&id=<?php print (int)$view_user->id; ?>&target=/logout/' class='btn btn-sm' data-bs-toggle='modal' data-bs-target='#modal1'>
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-danger"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
							<?php print _("Delete account"); ?>
						</a>
					</div>
					<?php endif; ?>
				</div>
			</td>
		</tr>

	</table>
	</div>
</div>

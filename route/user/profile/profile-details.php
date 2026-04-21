<?php
$force_passkey = !empty($view_user->force_passkey);
?>

<div class="card-body">
	<h3 class="card-title">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
		<?php print _("Account details"); ?>
	</h3>
</div>


<?php if ($force_passkey): ?>
<div class="card-body">
    <div class="alert alert-info p-2" style="font-size:12px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9h.01" /><path d="M11 12h1v4h1" /><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /></svg>
        <?php print _("Password login is disabled — this account requires passkey authentication."); ?>
    </div>
</div>
<?php endif; ?>

<div class="card-body">
<table class='table table-borderless table-md table-td-top table-zones-details'>

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
</table>

<hr>

	<a href='/route/modals/users/edit.php?action=edit&tenant=<?php print urlencode($user->href); ?>&id=<?php print (int)$view_user->id; ?>' class='btn btn-sm bg-info-lt' data-bs-toggle='modal' data-bs-target='#modal3'>
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-info"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415" /><path d="M16 5l3 3" /></svg>
		<?php print _("Edit profile"); ?>
	</a>
	<?php if($view_user->permission == 3): ?>
	<a href='/route/modals/users/edit.php?action=delete&tenant=<?php print urlencode($user->href); ?>&id=<?php print (int)$view_user->id; ?>&target=/logout/' class='btn btn-sm bg-info-lt text-danger' data-bs-toggle='modal' data-bs-target='#modal3'>
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-danger"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
		<?php print _("Delete account"); ?>
	</a>
<?php endif; ?>

</table>
</div>

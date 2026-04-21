<?php

$icon_mail = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-mail"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>';

# check tenant-level recipients across all visible tenants
# (a user's email may appear in any tenant's recipients, not only their own)
$tenant_recipient_of = [];
$tenants_to_check = ($user->admin == "1") ? $all_tenants_map : [$view_tenant->id => $view_tenant];
foreach ($tenants_to_check as $t) {
	if (!$t || strlen($t->recipients ?? "") == 0) { continue; }
	$recip_list = array_filter(array_map('trim', explode(";", str_replace(",", ";", $t->recipients))));
	if (in_array($view_user->email, $recip_list)) {
		$tenant_recipient_of[] = $t;
	}
}

# find per-host recipients (hosts where this user's email appears in h_recipients)
try {
	if ($user->admin == "1") {
		$recipient_hosts = $Database->getObjectsQuery(
			"select h.id, h.hostname, z.name as zone_name, z.t_id, t.href as tenant_href from hosts h join zones z on h.z_id = z.id join tenants t on z.t_id = t.id where h.h_recipients like ?",
			['%'.$view_user->email.'%']
		);
	} else {
		$recipient_hosts = $Database->getObjectsQuery(
			"select h.id, h.hostname, z.name as zone_name, z.t_id, t.href as tenant_href from hosts h join zones z on h.z_id = z.id join tenants t on z.t_id = t.id where h.h_recipients like ? and z.t_id = ?",
			['%'.$view_user->email.'%', $user->t_id]
		);
	}
} catch (Exception $e) {
	$recipient_hosts = [];
}

?>

<table class='table table-borderless table-md table-hover table-zones-details table-td-top'>

	<tr>
		<td class='text-secondary' style='min-width:160px;width:180px;vertical-align:top;'><?php print _("Tenant notifications"); ?></td>
		<td>
			<?php if (empty($tenant_recipient_of)): ?>
			<span class='badge bg-light-lt text-muted'><?php print _("Not in any tenant recipients"); ?></span>
			<?php else: ?>
			<?php foreach ($tenant_recipient_of as $t_recip): ?>
			<div><span class='badge bg-green-lt' style='margin-top:2px'><?php print htmlspecialchars($t_recip->name); ?></span></div>
			<?php endforeach; ?>
			<?php endif; ?>
		</td>
	</tr>

	<tr>
		<td class='text-secondary' style='vertical-align:top;'><?php print _("Per-host notifications"); ?></td>
		<td>
			<?php if (empty($recipient_hosts)): ?>
			<span class='text-secondary'><?php print _("No host-specific recipients"); ?></span>
			<?php else: ?>
			<?php foreach ($recipient_hosts as $rh): ?>
			<div>
				<a class='text-info' href='/<?php print htmlspecialchars($rh->tenant_href); ?>/zones/<?php print htmlspecialchars($rh->zone_name); ?>/<?php print htmlspecialchars($rh->hostname); ?>/'>
					<?php print htmlspecialchars($rh->hostname); ?>
				</a>
				<span class='text-muted' style='font-size:0.85em;'> / <?php print htmlspecialchars($rh->zone_name); ?></span>
			</div>
			<?php endforeach; ?>
			<?php endif; ?>
		</td>
	</tr>

</table>

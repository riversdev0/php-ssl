<div class="card-header">
	<h3 class="h3">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-clock"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 7v5l3 3" /></svg>
		<?php print _("Update certificates executions"); ?>
	</h3>
</div>

<div class="card-body" style="padding:0">

<?php

// fetch all cron jobs (indexed by [t_id][script])
$_check_jobs    = $Cron->fetch_tenant_cronjobs(true);
$_check_tenants = $Tenants->get_all();

// build rows: only update_certificates per tenant
$_check_rows = [];
if ($user->admin == "1") {
	foreach ($_check_tenants as $tid => $t) {
		$_check_rows[$tid] = $_check_jobs[$tid]['update_certificates'] ?? null;
	}
} else {
	$_check_rows[$user->t_id] = $_check_jobs[$user->t_id]['update_certificates'] ?? null;
}

?>

<table class="table table-borderless table-sm table-hover">
	<thead>
		<tr>
			<?php if ($user->admin == "1"): ?>
			<th class="text-secondary" style="padding-left:15px"><?php print _("Tenant"); ?></th>
			<?php else: ?>
			<th class="text-secondary" style="padding-left:15px;"><?php print _("Script"); ?></th>
			<?php endif; ?>
			<th class="text-secondary text-end" style="padding-right:15px;width:80px;"><?php print _("Last execution"); ?></th>
			<th class="text-secondary text-center d-none d-lg-table-cell" style="width:80px"><?php print _("Next execution"); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($_check_rows as $tid => $job): ?>
		<tr>
			<td style="padding-left:15px">
				<?php if ($user->admin == "1"): ?>
					<?php print htmlspecialchars($_check_tenants[$tid]->name); ?>
				<?php else: ?>
					<?php print _("Update certificates"); ?>
				<?php endif; ?>
			</td>
			<td class="text-end" style="padding-right:15px;white-space:nowrap">
				<?php if ($job === null || $job->last_executed === null): ?>
					<span class="badge bg-info-lt text-danger"><?php print _("Never"); ?></span>
				<?php else:
					print $job->last_executed==NULL ? "<span class='text-danger'>Never</span>" : "<span class='text-muted'>".htmlspecialchars($job->last_executed)."</span>";;
				?>
				<?php endif; ?>
			</td>
			<td class="d-none nextCheck"><?php if ($job !== null && $job->minute !== null) { print htmlspecialchars("{$job->minute} {$job->hour} {$job->day} {$job->month} {$job->weekday}"); } ?></td>
			<td class="text-muted text-center d-none d-lg-table-cell lastCheckSec" style="width:80px"></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<script src="/js/later-1.2.0.min.js"></script>
<script>updateNextCheckSeconds(""); setInterval(updateNextCheckSeconds, 1000, "");</script>

</div>
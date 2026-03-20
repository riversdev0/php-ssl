<?php
# validate user session - requires admin
$User->validate_session ();
?>

<div class="page-header">
	<h2 class="page-title">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-clock"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 7v5l3 3" /></svg>
		<?php print _("Cron jobs"); ?></h2>
	<hr>
</div>

<div>

<?php

# add
print '<div class="btn-group" role="group">';
print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> '._("Back").'</a>';
print '</div><br><br>';

# check write permission for the backup script's output directory
$Common->validate_backup_directory();

# text
print "<p class='text-secondary'>"._('List of scheduled cronjobs').".</p>";

# fetch jobs
$jobs = $Cron->fetch_tenant_cronjobs ();
# scripts
$scripts = $Cron->get_valid_scripts ();
# tenants
$tenants = $Tenants->get_all ();

# groups
$cron_groups = [];

// create groups for admins
if($user->admin=="1") {
	foreach($tenants as $t) {
		$cron_groups[$t->id] = $scripts;
	}
}
// regroup
if(sizeof($jobs)>0) {
	foreach ($jobs as $j) {
		$cron_groups[$j->t_id][$j->script] = $j;
	}
}

if (sizeof($cron_groups)==0) {
	$Result->show("info", _("No jobs available"));
}
else {

	# none
	print "<div class='card' style='margin-bottom:20px;padding:0px'>";
	print "<table class='table table-hover align-top table-sm' data-toggle='table' data-classes='table table-hover table-sm' data-cookie='false' data-pagination='true' data-page-size='250' data-page-list='[250,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";


	// header
	print "<thead>";
	print "<tr>";
	print "	<th data-field='name'>"._("Name")."</th>";
	print "	<th data-width='80' data-width-unit='px' data-field='lastCheckSec' class='text-center d-none d-lg-table-cell'></th>";
	print "	<th data-width='50' data-width-unit='px' data-field='minute' class='text-center d-none d-lg-table-cell'>"._("Min")."</th>";
	print "	<th data-width='50' data-width-unit='px' data-field='hour' class='text-center d-none d-lg-table-cell'>"._("Hour")."</th>";
	print "	<th data-width='50' data-width-unit='px' data-field='day' class='text-center d-none d-lg-table-cell'>"._("Day")."</th>";
	print "	<th data-width='50' data-width-unit='px' data-field='month' class='text-center d-none d-lg-table-cell'>"._("MON")."</th>";
	print "	<th data-width='50' data-width-unit='px' data-field='weekday' class='text-center d-none d-lg-table-cell'>"._("WKD")."</th>";
	print "	<th data-width='150' data-width-unit='px' data-field='check' class='text-center d-none d-lg-table-cell' style='width:150px;'>"._("Last executed")."</th>";
	print "	<th data-width='50' data-width-unit='px' data-field='next' class='text-center d-none'></th>";
	print "	<th data-width='60' data-width-unit='px' data-field='actions' class='text-center'></th>";
	print "</tr>";
	print "</thead>";

	print "<tbody>";

	// body
	foreach ($cron_groups as $tenant_id=>$group) {

		if($user->admin=="1") {
			print "<tr class='header'>";
			print "	<td colspan=9 style='padding-top:25px'>".$url_items["tenants"]['icon']." "._("Tenant")." <span style='color:var(--tblr-info);'>".$tenants[$tenant_id]->name."</span></td>";
			print "</tr>";
		}

		foreach ($group as $script => $t) {

			if(!is_object($t)) {
				$t = new StdClass ();
				$t->script = $script;
				$t->minute = "-";
				$t->hour = "-";
				$t->day = "-";
				$t->weekday = "-";
				$t->month = "-";
				$t->last_executed = "Never";

				$trclass = "text-danger";
			}
			else {
				$trclass = "";

				if($t->last_executed==NULL) {
					$t->last_executed = "<span class='text-danger'>Never</span>";
				}
			}

			if($t->script=="update_certificates")		{ $script_name = "Update SSL certificates"; }
			elseif($t->script=="axfr_transfer")			{ $script_name = "Zone transfers"; }
			elseif($t->script=="remove_orphaned")		{ $script_name = "Remove orhaned certificates"; }
			elseif($t->script=="expired_certificates")	{ $script_name = "Notify about expired certificates"; }
			elseif($t->script=="backup")				{ $script_name = "Database backup"; }
			else										{ $script_name = "Unknown"; }

			print "<tr class='$trclass'>";
			print "	<td style='padding-left:15px'><span class='text-secondary'>".$url_items['scanning']['submenu']['cron']['icon']." </span>".$script_name."</td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell lastCheckSec' style='width:50px'></td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell'>".$t->minute."</td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell'>".$t->hour."</td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell'>".$t->day."</td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell'>".$t->month."</td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell lastCheck'>".$t->weekday."</td>";
			print "	<td class='text-muted'>".$t->last_executed."</span></td>";
			// minutes
			$force_val = (is_object($t) && isset($t->force)) ? intval($t->force) : 0;
			if($t->minute!="-")
			print "	<td class='d-none nextCheck' data-force='{$force_val}'>{$t->minute} {$t->hour} {$t->day} {$t->month} {$t->weekday}</td>";
			else
			print "	<td class='d-none nextCheck' data-force='0'></td>";
			// actions
			print "	<td class='text-muted text-center d-none d-lg-table-cell' style='width:20px;'>";
			print '<div class="btn-group">';
			print '<span class="badge text-info"><a href="/route/modals/cron/edit.php?tenant='.$tenant_id.'&script='.$t->script.'" data-bs-toggle="modal" data-bs-target="#modal1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-pencil"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg></a></span>';
			// force button (only for existing cron entries)
			if(is_object($t) && isset($t->id)) {
				$force_class = ($t->force == 1) ? "text-warning" : "text-secondary";
				print '<span class="badge '.$force_class.' cron-force-btn" data-cron-id="'.$t->id.'" style="cursor:pointer" title="'._("Force next execution").'"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-circle-asterisk"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 8.5v7" /><path d="M9 10l6 4" /><path d="M9 14l6 -4" /><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /></svg></span>';
			}
			print "</div>";
			print "</td>";

			print "</tr>";
		}
	}
	print "</tbody>";
	print "</table>";
	print "</div>";
}
?>

</div>


<script src="/js/later-1.2.0.min.js"></script>
<script>updateNextCheckSeconds(); setInterval(updateNextCheckSeconds, 1000);</script>

<script>
$(document).on("click", ".cron-force-btn", function () {
	var $btn = $(this);
	var cronId = $btn.data("cron-id");
	$.ajax({
		url: "/route/ajax/cron-force.php",
		method: "POST",
		data: { id: cronId },
		headers: { "X-Requested-With": "XMLHttpRequest" },
		dataType: "json",
		success: function (resp) {
			if (resp.success) {
				var $nextTd = $btn.closest("tr").find("td.nextCheck");
				if (resp.force == 1) {
					$btn.removeClass("text-secondary").addClass("text-warning");
					$nextTd.data("force", 1);
				} else {
					$btn.removeClass("text-warning").addClass("text-secondary");
					$nextTd.data("force", 0);
				}
				updateNextCheckSeconds();
			}
		}
	});
});
</script>

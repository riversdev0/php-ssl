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
	print "	<th data-width='100' data-width-unit='px' data-field='minute' class='text-center d-none d-lg-table-cell'>"._("Minute")."</th>";
	print "	<th data-width='100' data-width-unit='px'  data-field='hour' class='text-center d-none d-lg-table-cell'>"._("Hour")."</th>";
	print "	<th data-width='100' data-width-unit='px' data-field='day' class='text-center d-none d-lg-table-cell'>"._("Day")."</th>";
	print "	<th data-width='100' data-width-unit='px' data-field='weekday' class='text-center d-none d-lg-table-cell'>"._("Weekday")."</th>";
	print "	<th data-width='150' data-width-unit='px' data-field='check' class='text-center d-none d-lg-table-cell' style='width:150px;'>"._("Last executed")."</th>";
	print "</tr>";
	print "</thead>";

	print "<tbody>";

	// body
	foreach ($cron_groups as $tenant_id=>$group) {

		if($user->admin=="1") {
			print "<tr class='header'>";
			print "	<td colspan=10 style='padding-top:25px'>".$url_items["tenants"]['icon']." "._("Tenant")." <a href='/".$user->href."/tenants/".$tenants[$tenant_id]->href."/' style='color:var(--tblr-info);'>".$tenants[$tenant_id]->name."</a></td>";
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
				$t->last_executed = "-";

				$trclass = "text-danger";
			}
			else {
				$trclass = "";
			}

			if($t->script=="update_certificates")		{ $script_name = "Update SSL certificates"; }
			elseif($t->script=="axfr_transfer")			{ $script_name = "Zone transfers"; }
			elseif($t->script=="remove_orphaned")		{ $script_name = "Remove orhaned certificates"; }
			elseif($t->script=="expired_certificates")	{ $script_name = "Notify about expired certificates"; }
			else										{ $script_name = "Unknown"; }

			print "<tr class='$trclass'>";
			print "	<td style='padding-left:15px'><span class='text-secondary'>".$url_items['scanning']['submenu']['cron']['icon']." </span>".$script_name."</td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell'>".$t->minute."</td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell'>".$t->hour."</td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell'>".$t->day."</td>";
			print "	<td class='text-muted text-center d-none d-lg-table-cell'>".$t->weekday."</td>";
			print "	<td class='text-muted'>".$t->last_executed."</span></td>";
			print "</tr>";
		}
	}
	print "</tbody>";
	print "</table>";
	print "</div>";
}
?>

</div>
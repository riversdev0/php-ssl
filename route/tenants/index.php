<?php
# validate user session - requires admin
$User->validate_session (true);
?>

<div class="page-header">
	<h2 class="page-title"><?php print $url_items["tenants"]['icon']; ?> <?php print _("Tenants"); ?></h2>
	<hr>
</div>


<p class='text-secondary'><?php print _('List of all available tenants in the system'); ?>.</p>


<?php

# fetch tenants
$tenants = $Tenants->get_all();

# back
print '<div>';
print '<a href="/zones/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> '._("Back").'</a>';
print '</div><br><br>';


print '<div style="text-align:right !important">';
print '<a href="/route/modals/tenants/edit.php?action=add" data-bs-toggle="modal" data-bs-target="#modal1" class="btn btn-sm text-green bg-info-lt"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg> '._("Create new tenant").'</a>';
print "</div>";

# nont
if (sizeof($tenants)==0) {
	$Result->show("info", _("No tenants available").".");
}
else {

	print '<div class="page-body">';
	print '<div class="card">';

	print "<div class='table-responsive'>";
	print "<table class='table table-hover align-top table-md' data-toggle='table' data-classes='table table-hover table-sm' data-cookie='false' data-pagination='true' data-page-size='250' data-page-list='[250,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";

	// header
	print "<thead>";
	print "<tr>";
	print "	<th>"._("Name")."</th>";
	print "	<th data-padding='10' data-padding-units='px' data-width='20' data-width-unit='px' >"._("ID")."</th>";
	print "	<th>"._("Status")."</th>";
	print "	<th>"._("Description")."</th>";
	print "	<th class='text-center' style='width:20px;padding:0.5rem 0rem' data-width='20' data-width-unit='px' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Agents")."'>".$url_items["scanning"]["icon"]."</th>";
	print "	<th class='text-center' style='width:20px;padding:0.5rem 0rem' data-width='20' data-width-unit='px' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Zones")."'>".$url_items["zones"]["icon"]."</th>";
	print "	<th class='text-center' style='width:20px;padding:0.5rem 0rem' data-width='20' data-width-unit='px' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Users")."'>".$url_items["users"]["icon"]."</th>";
	print "	<th class='text-center' style='width:15px;padding:0.5rem 0rem' data-width='20' data-width-unit='px'></th>";
	print "	<th class='text-center' style='width:15px;padding:0.5rem 0rem' data-width='20' data-width-unit='px'></th>";
	print "</tr>";
	print "</thead>";

	// body
	print "<tbody>";
	foreach ($tenants as $t) {


	$status = $t->active == 1 ? "<span class='badge bg-green-lt'>Active</span>" : "<span class='badge bg-red-lt'>Disabled</span>";
	$zones  = $Database->count_database_objects("zones", "t_id", $t->id);
	$users  = $Database->count_database_objects("users", "t_id", $t->id);
	$agents = $Database->count_database_objects("agents", "t_id", $t->id);

	# check for missing cronjobs and portgroups
	$warnings = [];
	$check_cronjobs = $Database->getObjectsQuery("select count(*) as cnt from cron where t_id = ?", [$t->id]);
	if($check_cronjobs[0]->cnt == 0) {
		$warnings[] = _("No cronjobs configured");
	}
	$check_portgroups = $Database->getObjectsQuery("select count(*) as cnt from ssl_port_groups where t_id = ?", [$t->id]);
	if($check_portgroups[0]->cnt == 0) {
		$warnings[] = _("No port groups configured");
	}

	# warning icon and popup
	$warning_icon = "";
	$tenant_name_class = "text-body";
	if(sizeof($warnings)>0) {
		$warning_icon = " <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon icon-tabler icons-tabler-outline icon-tabler-alert-triangle text-red' data-bs-toggle='tooltip' data-bs-html='true' title=\"".implode("<br>", $warnings)."\" data-bs-placement='right'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 9v4' /><path d='M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0' /><path d='M12 16h.01' /></svg>";
		$tenant_name_class = "text-red";
	}

	print "<tr>";
	print "	<td>".$url_items["tenants"]['icon']." <a href='/route/modals/tenants/edit.php?id=".$t->id."&action=edit' data-bs-toggle='modal' data-bs-target='#modal1' class='".$tenant_name_class."'>".$t->name."</a>".$warning_icon."</td>";
	print "	<td><span class='badge' style='width:100%'>".$t->id."</span></td>";
	print "	<td>".$status."</td>";
	print "	<td class='text-muted'>".$t->description."</td>";
	print "	<td class='text-center' style='padding:0.5rem 0.1rem;'><span class='badge' style='width:100%'>".$agents."</span></td>";
	print "	<td class='text-center' style='padding:0.5rem 0.1rem;'><span class='badge' style='width:100%'>".$zones."</span></td>";
	print "	<td class='text-center' style='padding:0.5rem 0.1rem;'><span class='badge' style='width:100%'>".$users."</span></td>";
	print "	<td class='text-center' style='padding:0.5rem 0.2rem;padding-left:0.5rem;border-left:1px solid var(--tblr-border-color);'>
					<a href='/route/modals/tenants/edit.php?id=".$t->id."&action=edit' data-bs-toggle='modal' data-bs-target='#modal1'>
						<span class='badge text-info' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Edit tenant")."'>
						<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon icon-tabler icons-tabler-outline icon-tabler-edit'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1' /><path d='M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415' /><path d='M16 5l3 3' /></svg>
						</span>
					</a>
			</td>";
	print "	<td class='text-center' style='padding:0.5rem 0.2rem;'>
				<a href='/route/modals/tenants/edit.php?id=".$t->id."&action=delete' data-bs-toggle='modal' data-bs-target='#modal1' style='color:rgb(210,51,40) !important;'>
					<span class='badge text-red' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Delete tenant")."'>
						<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon icon-tabler icons-tabler-outline icon-tabler-trash'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M4 7l16 0' /><path d='M10 11l0 6' /><path d='M14 11l0 6' /><path d='M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12' /><path d='M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3' /></svg>
					</span>
				</a>
			</td>";
	print "</tr>";
	}

	print "</table>";
	print "</div>";

	print '</div>';
	print '</div>';
}
<?php
# validate user session
$User->validate_session ();
?>


<div class="page-header">
	<h2 class="page-title"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-device-desktop-search"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.5 16h-7.5a1 1 0 0 1 -1 -1v-10a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v6.5" /><path d="M7 20h4" /><path d="M9 16v4" /><path d="M15 18a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M20.2 20.2l1.8 1.8" /></svg>
		<?php print _("Scan agents"); ?></h2>
	<hr>
</div>


<div>
<?php

# fetch agents
if($user->admin=="1")
$agents = $Database->getObjectsQuery("select * from agents where atype = 'remote'");
else
$agents = $Database->getObjectsQuery("select * from agents where atype = 'remote' and t_id = ?", [$user->t_id]);
# tenants
$tenants = $Tenants->get_all ();

# groups
$agent_groups = [];

// create tenant groups for admins to show empty also
if($user->admin=="1") {
	foreach($tenants as $t) {
		$agent_groups[$t->id] = [];
	}
}
// regroup groups to tenants
if(sizeof($agents)>0) {
	foreach ($agents as $z) {
		$agent_groups[$z->t_id][] = $z;
	}
}

# add
print '<div class="btn-group" role="group">';
print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> '._("Back").'</a>';
if($user->admin=="0") {
print '<a href="/route/scanning/agents/edit.php?action=add&tenant='.$user->t_id.'" data-bs-toggle="modal" class="btn btn-sm btn-outline-success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2"><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg> '._("New agent").'</a>';
}
print '</div><br><br>';

# text
print "<p class='text-secondary'>"._('List of all agents').".</p>";

# errors
require(dirname(__FILE__)."/../../dashboard/card-agent-errors.php");

# none
if (sizeof($agent_groups)==0) {
	$Result->show("info", _("No agents available"));
}
else {

	print "<div class='card' style='margin-bottom:20px;padding:0px'>";
	print "<table class='table table-hover align-top table-md' data-toggle='table' data-classes='table table-hover table-sm' data-cookie='false' data-pagination='true' data-page-size='250' data-page-list='[250,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";

	// header
	print "<thead>";
	print "<tr>";
	print "	<th data-field='name'>"._("Name")."</th>";
	print "	<th data-field='status' style='width:50px;' data-width='50' data-width-unit='px'>"._("Status")."</th>";
	print "	<th data-field='desc' class='d-none d-lg-table-cell'>"._("URL")."</th>";
	print "	<th data-field='zones' class='text-center' style='width:20px;' data-width='20' data-toggle='tooltip' data-bs-placement='top' title='"._("Zones")."'>".$url_items['zones']['icon']."</th>";
	print "	<th data-field='hosts' class='text-center' style='width:20px;' data-width='20' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Hosts")."'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-server"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3" /><path d="M3 15a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3l0 -2" /><path d="M7 8l0 .01" /><path d="M7 16l0 .01" /></svg>'."</th>";
	print "	<th data-field='check' class='d-none d-lg-table-cell' style='width:150px;'>"._("Last check")."</th>";
	print "	<th data-field='checks' class='d-none d-lg-table-cell' style='width:150px;'>"._("Last success")."</th>";
	print "	<th data-field='edit' class='text-center' style='width:20px'><i class='fa fa-pencil' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Edit agent")."'></i></th>";
	print "	<th data-field='refresh' class='text-center' style='width:20px'><i class='fa fa-refresh' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Retest agent")."'></i></th>";
	print "	<th data-field='delete' class='text-center' style='width:20px;'><i class='fa fa-remove' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Delete agent")."'></i></th>";
	print "</tr>";
	print "</thead>";

	print "<tbody>";

	// init agent
	$Agent = new Agent ();
	// get conf
	$config = $Config->get_config($user->t_id);
	// get errors
	$agent_errors = $Agent->get_agent_connection_errors($Database, $config['agentTimeout'], $user->admin, $user->t_id);

	// body
	foreach ($agent_groups as $tenant_id=>$group) {

		if($user->admin=="1") {
			print "<tr class='header'>";
			print "	<td colspan=10 style='padding-top:25px'>".$url_items["tenants"]['icon']." "._("Tenant")." <a href='/".$user->href."/tenants/".$tenants[$tenant_id]->href."/' style='color:var(--tblr-info);'>".$tenants[$tenant_id]->name."</a>";
			print '<a href="/route/scanning/agents/edit.php?action=add&tenant='.$tenants[$tenant_id]->href.'" data-bs-toggle="modal" class="btn btn-sm btn-outline-success float-end"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2"><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg> '._("New agent").'</a>';
			print "</td>";
			print "</tr>";
		}

		if(sizeof($group)==0) {
			print "<tr>";
			print "	<td colspan='10'>".'<div class="alert alert-info"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-info-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>'." "._("No agents available").".</div></td>";
			print "</tr>";
		}
		else {
			foreach ($group as $a) {

				// cnt
				$count_zones = $Database->getObjectQuery("select count(*) as cnt from zones where agent_id = ?", [$a->id]);
				$count_hosts = $Database->getObjectQuery("select count(*) as cnt from zones as z, hosts as h where h.z_id = z.id and z.`agent_id` = ?", [$a->id]);

				// error if status not ok
				$status = array_key_exists($a->id, $agent_errors) ? "<span class='badge bg-red-lt'>"._("Error")."</span>" : "<span class='badge bg-green-lt'>"._("OK")."</span>";
				// never checked
				$status = is_null($a->last_check) ? "<span class='badge bg-red-lt'>"._("Unknown")."</span>" : $status;
				// never success
				$status = is_null($a->last_success) ? "<span class='badge bg-orange-lt'>"._("Unknown")."</span>" : $status;

				print "<tr>";
				print "	<td style='padding-left:15px'>";
				print '	<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-device-desktop-search"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.5 16h-7.5a1 1 0 0 1 -1 -1v-10a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v6.5" /><path d="M7 20h4" /><path d="M9 16v4" /><path d="M15 18a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M20.2 20.2l1.8 1.8" /></svg>';
				print " ".$a->name."</td>";
				print "	<td>$status</td>";
				print "	<td class='text-muted d-none d-lg-table-cell'>".$a->url."</td>";
				print "	<td class='text-center' style='width:20px;'> <span class='badge badge-outline text-default' style='width:100%'>".$count_zones->cnt."</span></td>";
				print "	<td class='text-center' style='width:20px;'><span class='badge badge-outline text-default' style='width:100%'>".$count_hosts->cnt."</span></td>";
				print "	<td class='text-muted d-none d-lg-table-cell' style='font-size:11px;width:140px'>".$a->last_check."</td>";
				print "	<td class='text-muted d-none d-lg-table-cell' style='font-size:11px;width:140px'>".$a->last_success."</td>";
				// actions
				print "	<td class='text-center' style='padding:0.5rem 0.2rem;width:20px;border-left:1px solid var(--tblr-table-border-color);'>";
				print "		<a href='/route/scanning/agents/edit.php?id=".$a->id."&action=edit&tenant=".$a->t_id."' data-bs-toggle='modal' data-bs-target='#modal1'>";
				print "		<span class='badge badge-outline text-info'>";
				print '			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415" /><path d="M16 5l3 3" /></svg>';
				print "		</span>";
				print "		</a>";
				print "</td>";
				// edit
				print "	<td class='text-center' style='padding:0.5rem 0.2rem;width:20px'>";
				print "		<a href='/route/scanning/agents/refresh.php?id=".$a->id."&tenant=".$a->t_id."' data-bs-toggle='modal' data-bs-target='#modal1'>";
				print "		<span class='badge badge-outline text-light'>";
				print '			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-refresh"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>';
				print "		</span>";
				print "		</a>";
				print "</td>";
				// delete
				print "	<td class='text-center' style='padding:0.5rem 0.2rem;width:20px;'>";
				print "		<a href='/route/scanning/agents/edit.php?id=".$a->id."&action=delete&tenant=".$a->t_id."' data-bs-toggle='modal' data-bs-target='#modal1'>";
				print "		<span class='badge badge-outline text-red'>";
				print '			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';
				print "</span>";
				print "	</a>";
				print "</td>";

				print "</tr>";
			}
		}
	}
	print "</tbody>";
	print "</table>";
	print "</div>";
}
?>
</div>
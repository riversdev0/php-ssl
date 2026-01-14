<?php
# validate user session
$User->validate_session ();
?>

<div class="page-header">
	<h2 class="page-title">
		<?php print $url_items['ignored']['icon'].""._("Ignored issuers"); ?></h2>
	<hr>
</div>

<div>
<?php

# fetch issuers
if($user->admin=="1")
$issuers = $Database->getObjectsQuery("select * from ignored_issuers");
else
$issuers = $Database->getObjectsQuery("select * from ignored_issuers where t_id = ?", [$user->t_id]);
# tenants
$tenants = $Tenants->get_all ();

# groups
$issuer_group = [];

// create tenant groups for admins to show empty also
if($user->admin=="1") {
	foreach($tenants as $t) {
		$issuer_group[$t->id] = [];
	}
}
// regroup groups to tenants
if(sizeof($issuers)>0) {
	foreach ($issuers as $z) {
		$issuer_group[$z->t_id][] = $z;
	}
}

# add
print '<div class="btn-group" role="group">';
print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> '._("Back").'</a>';
if($user->admin=="0")
print '<a href="/route/ignored/edit.php?action=add&tenant='.$user->t_id.'" data-bs-toggle="modal" class="btn btn-sm btn-outline-success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2"><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg> '._("New ignored issuer").'</a>';
print '</div><br><br>';

# text
print "<p class='text-secondary'>"._('List of all ignored issuers - new certificates detected by this issuers will not be reported').". "._("Certificates issued by this issuers will still be available in system").".</p>";

# none
print "<div class='card'>";

// no groups
if (sizeof($issuer_group)==0) {
	print "<div style='padding: 10px;padding-bottom:0px'>";
	$Result->show("info", _("No issuers available"));
	print "</div>";
}
else {
	print "<table class='table table-hover align-top table-sm' data-toggle='table' data-classes='table table-hover table-md' data-cookie='false' data-pagination='true' data-page-size='250' data-page-list='[250,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";

	// header
	print "<thead>";
	print "<tr>";
	print "	<th data-field='name'>"._("Name")."</th>";
	print "	<th data-field='id' class='d-none d-lg-table-cell'>"._("Subject Key ID")."</th>";
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
	foreach ($issuer_group as $tenant_id=>$group) {

		if($user->admin=="1") {
			print "<tr class='header'>";
			print "	<td colspan=3 style='padding-top:25px'>".$url_items["tenants"]['icon']." "._("Tenant")." <a href='/".$user->href."/tenants/".$tenants[$tenant_id]->href."/' style='color:var(--tblr-info);'>".$tenants[$tenant_id]->name."</a>";

			print '<a href="/route/ignored/edit.php?action=add&tenant='.$tenant_id.'" data-bs-toggle="modal" class="btn btn-sm btn-outline-success float-end"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2"><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg> '._("New ignored issuer").'</a>';
			print "</td>";
			print "</tr>";
		}

		if(sizeof($group)==0) {
			print "<tr class='header'>";
			print "	<td colspan=3><span class='alert alert-info'>".$url_items["ignored"]['icon']." "._("No ignored issuers configured")." </div></td>";
			print "</tr>";
		}
		else {
			foreach ($group as $a) {
				print "<tr>";
				print "	<td style='padding-left:20px'>".$url_items["ignored"]['icon']." ".$a->name."</td>";
				print "	<td class='text-muted d-none d-lg-table-cell'>".$a->ski."</td>";
				print "	<td class='text-center' style='width:20px;'><span class='badge badge-outline text-red'><a href='/route/ignored/edit.php?id=".$a->id."&action=delete&tenant=".$a->t_id."' data-bs-toggle='modal' data-bs-target='#modal1' style='color:rgb(210,51,40) !important;'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>'."</a></span></td>";
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
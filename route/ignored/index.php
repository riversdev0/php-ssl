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
print '<a href="/route/modals/ignored/edit.php?action=add&tenant='.$user->t_id.'" data-bs-toggle="modal" class="btn btn-sm btn-outline-success '.$user->actions_disabled.'"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2"><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg> '._("New ignored issuer").'</a>';
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
	print "	<th data-field='update' class='text-center d-none d-lg-table-cell'>"._("Ignore updates")."</th>";
	print "	<th data-field='expired' class='text-center d-none d-lg-table-cell'>"._("Ignore expiry")."</th>";
	print "	<th data-field='actions' class='text-center' style='width:60px;'></th>";
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
			print "	<td colspan=5 style='padding-top:25px'>".$url_items["tenants"]['icon']." "._("Tenant")." <a href='/".$user->href."/tenants/".$tenants[$tenant_id]->href."/' style='color:var(--tblr-info);'>".$tenants[$tenant_id]->name."</a>";

			print '<a href="/route/modals/ignored/edit.php?action=add&tenant='.$tenant_id.'" data-bs-toggle="modal" class="btn btn-sm text-green bg-info-lt § float-end"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2"><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg> '._("New ignored issuer").'</a>';
			print "</td>";
			print "</tr>";
		}

		if(sizeof($group)==0) {
			print "<tr class='header'>";
			print "	<td colspan=5><span class='alert alert-info'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-info-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>'." "._("No ignored issuers configured")." </div></td>";
			print "</tr>";
		}
		else {
			foreach ($group as $a) {
				$badge_update  = $a->update  == '1' ? "<span class='badge bg-green-lt text-green'>"._("Yes")."</span>" : "<span class='badge bg-secondary-lt text-muted'>"._("No")."</span>";
				$badge_expired = $a->expired == '1' ? "<span class='badge bg-green-lt text-green'>"._("Yes")."</span>" : "<span class='badge bg-secondary-lt text-muted'>"._("No")."</span>";
				print "<tr>";
				print "	<td style='padding-left:20px'><span class='text-muted'>".$url_items["ignored"]['icon']."</span> ".$a->name."</td>";
				print "	<td class='text-muted d-none d-lg-table-cell'>".$a->ski."</td>";
				print "	<td class='text-center d-none d-lg-table-cell'>".$badge_update."</td>";
				print "	<td class='text-center d-none d-lg-table-cell'>".$badge_expired."</td>";
				print "	<td class='text-center' style='width:80px;'>";
				print "		<span class='badge text-secondary'><a href='/route/modals/ignored/edit.php?id=".$a->id."&action=edit&tenant=".$a->t_id."' data-bs-toggle='modal' data-bs-target='#modal1' style='color:var(--tblr-secondary) !important;'><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon icon-tabler icons-tabler-outline icon-tabler-edit'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1'/><path d='M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z'/><path d='M16 5l3 3'/></svg></a></span>";
				print "		<span class='badge text-red'><a href='/route/modals/ignored/edit.php?id=".$a->id."&action=delete&tenant=".$a->t_id."' data-bs-toggle='modal' data-bs-target='#modal1' style='color:rgb(210,51,40) !important;'><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon icon-tabler icons-tabler-outline icon-tabler-trash'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M4 7l16 0'/><path d='M10 11l0 6'/><path d='M14 11l0 6'/><path d='M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12'/><path d='M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3'/></svg></a></span>";
				print "	</td>";
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
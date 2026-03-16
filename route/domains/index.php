<?php
# validate user session
$User->validate_session ();
# require permission 3 or admin
$User->validate_user_permissions (3, true);
?>


<div class="page-header">
	<h2 class="page-title"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-database"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6a8 3 0 1 0 16 0a8 3 0 1 0 -16 0" /><path d="M4 6v6a8 3 0 0 0 16 0v-6" /><path d="M4 12v6a8 3 0 0 0 16 0v-6" /></svg>
		<?php print _("Domains"); ?></h2>
	<hr>
</div>


<div>
<?php

# fetch domains
if($user->admin=="1")
$domains = $Database->getObjectsQuery("select * from domains where type != 'local'");
else
$domains = $Database->getObjectsQuery("select * from domains where type != 'local' AND t_id = ?", [$user->t_id]);

# tenants
$tenants = $Tenants->get_all ();

# groups
$domain_groups = [];

// create tenant groups for admins to show empty also
if($user->admin=="1") {
	foreach($tenants as $t) {
		$domain_groups[$t->id] = [];
	}
}
// regroup to tenants
if(sizeof($domains)>0) {
	foreach ($domains as $d) {
		$domain_groups[$d->t_id][] = $d;
	}
}

# buttons
print '<div class="btn-group" role="group">';
print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> '._("Back").'</a>';
if($user->admin=="0") {
	print '<a href="/route/modals/domains/edit.php?action=add&tenant='.$user->t_id.'" data-bs-toggle="modal" class="btn btn-sm btn-outline-success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2"><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg> '._("New domain").'</a>';
}
print '</div>';

# text
print "<p class='text-secondary'>"._('List of all login domains').".</p>";

# none
if (sizeof($domain_groups)==0) {
	$Result->show("info", _("No domains available"));
}
else {

	print "<div class='card' style='margin-bottom:20px;padding:0px'>";
	print "<table class='table table-hover align-top table-md' data-toggle='table' data-classes='table table-hover table-sm' data-cookie='false' data-pagination='true' data-page-size='250' data-page-list='[250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";

	// header
	print "<thead>";
	print "<tr>";
	print "	<th data-field='name'>"._("Name")."</th>";
	print "	<th data-field='type' style='width:80px;'>"._("Type")."</th>";
	print "	<th data-field='active' class='text-center' style='width:80px;'>"._("Active")."</th>";
	print "	<th data-field='suffix' class='d-none d-lg-table-cell'>"._("Account suffix")."</th>";
	print "	<th data-field='controllers' class='d-none d-lg-table-cell'>"._("Domain controllers")."</th>";
	print "	<th data-field='edit' class='text-center' style='width:20px'><i class='fa fa-pencil' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Edit domain")."'></i></th>";
	print "	<th data-field='delete' class='text-center' style='width:20px;'><i class='fa fa-remove' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Delete domain")."'></i></th>";
	print "</tr>";
	print "</thead>";

	print "<tbody>";

	// body
	foreach ($domain_groups as $tenant_id=>$group) {

		if($user->admin=="1") {
			print "<tr class='header'>";
			print "	<td colspan=7 style='padding-top:25px;'>".$url_items["tenants"]['icon']." "._("Tenant")." <span style='color:var(--tblr-info);'>".$tenants[$tenant_id]->name."</span>";
			print '<a href="/route/modals/domains/edit.php?action=add&tenant='.$tenants[$tenant_id]->href.'" data-bs-toggle="modal" class="btn btn-sm text-green bg-info-lt text-green float-end"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2"><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg> '._("New domain").'</a>';
			print "</td>";
			print "</tr>";
		}

		if(sizeof($group)==0) {
			print "<tr>";
			print "	<td colspan='7'>".'<div class="alert alert-info"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-info-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>'." "._("No domains available").".</div></td>";
			print "</tr>";
		}
		else {
			foreach ($group as $d) {

				// type badge
				$type_badge = $d->type === 'AD'
					? "<span class='badge bg-blue-lt text-blue'>AD</span>"
					: "<span class='badge bg-secondary-lt text-muted'>"._("Local")."</span>";

				// active badge
				$active_badge = $d->active === 'Yes'
					? "<span class='badge bg-green-lt text-green'>"._("Yes")."</span>"
					: "<span class='badge bg-red-lt text-red'>"._("No")."</span>";

				print "<tr>";
				print "	<td style='padding-left:15px'>";
				print '	<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-database"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6a8 3 0 1 0 16 0a8 3 0 1 0 -16 0" /><path d="M4 6v6a8 3 0 0 0 16 0v-6" /><path d="M4 12v6a8 3 0 0 0 16 0v-6" /></svg>';
				print " ".htmlspecialchars($d->name)."</td>";
				print "	<td>$type_badge</td>";
				print "	<td class='text-center'>$active_badge</td>";
				print "	<td class='text-muted d-none d-lg-table-cell'>".htmlspecialchars($d->account_suffix)."</td>";
				print "	<td class='text-muted d-none d-lg-table-cell'>".htmlspecialchars($d->domain_controllers)."</td>";
				// edit - local domains are non-editable
				print "	<td class='text-center' style='padding:0.5rem 0.2rem;width:20px;border-left:1px solid var(--tblr-table-border-color);'>";
				print "		<a href='/route/modals/domains/edit.php?id=".$d->id."&action=edit&tenant=".$tenants[$tenant_id]->href."' data-bs-toggle='modal' data-bs-target='#modal1'>";
				print "		<span class='badge text-info'>";
				print '			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415" /><path d="M16 5l3 3" /></svg>';
				print "		</span>";
				print "		</a>";
				print "</td>";
				// delete - local domains are non-deletable
				print "	<td class='text-center' style='padding:0.5rem 0.1rem;width:10px;'>";
				print "		<a href='/route/modals/domains/edit.php?id=".$d->id."&action=delete&tenant=".$tenants[$tenant_id]->href."' data-bs-toggle='modal' data-bs-target='#modal1'>";
				print "		<span class='badge text-red'>";
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

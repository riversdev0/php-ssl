<?php
# validate user session
$User->validate_session ();
?>


<div class="page-header">
	<h2 class="page-title"><?php print $url_items["zones"]['icon']; ?> <?php print _("Zones"); ?></h2>
	<hr>
</div>


<p class='text-secondary'><?php print _('List of all available DNS zones in the system'); ?>.</p>

<?php

# fetch zones
$zones = $Zones->get_all();
# tenants
$tenants = $Tenants->get_all ();

# groups
$zone_groups = [];

// create tenant groups for admins to show empty also
if($user->admin=="1") {
	foreach($tenants as $t) {
		$zone_groups[$t->id] = [];
	}
}
// regroup groups to tenants
if(sizeof($zones)>0) {
	foreach ($zones as $z) {
		$zone_groups[$z->t_id][] = $z;
	}
}

# back
print '<div>';
print '<div class="btn-group" role="group">';
print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> '._("Back").'</a>';
if($user->admin=="0")
print '<a href="/route/modals/zones/edit.php?action=add&tenant='.$user->href.'" data-bs-toggle="modal" class="btn btn-sm btn-outline-success float-end"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg> '._("Create new zone").'</a>';
print '</div>';
print '<br><br>';

print '<div style="padding:5px">';
print '<div class="row">';
print '<div class="card" style="padding:0px">';
print '<div class="card-body" style="padding:0px">';

# none
print "<div class='table-responsive'>";
print "<table class='table table-hover align-top table-md' data-toggle='table' data-classes='table table-hover table-sm' data-cookie='false' data-pagination='true' data-page-size='250' data-page-list='[250,250,500,All]' data-search='true' data-icons-prefix='fa' data-icon-size='xs' data-show-footer='false' data-smart-display='true' showpaginationswitch='true'>";


// header
print "<thead>";
print "<tr>";
print "	<th data-field='name'>"._("Name")."</th>";
print "	<th data-field='type' style='width:50px;' data-width='50' data-width-unit='px'>"._("Type")."</th>";
print "	<th data-field='desc' class='d-none d-lg-table-cell'>"._("Description")."</th>";
print "	<th data-field='agent' class='d-none d-lg-table-cell'>"._("Agent")."</th>";
print "	<th data-field='check' class='d-none d-lg-table-cell' style='width:150px;'>"._("Last check")."</th>";
print "	<th data-field='hosts' class='text-center' data-width='40' data-width-unit='px' style='width:40px;' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Hosts")."'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-server"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3" /><path d="M3 15a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3l0 -2" /><path d="M7 8l0 .01" /><path d="M7 16l0 .01" /></svg>'."</th>";
print "	<th data-field='certs' class='text-center' data-width='40' data-width-unit='px' style='width:40px;' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Certificates")."'>".$url_items["certificates"]['icon']."</th>";
print "	<th data-field='expire_soon' class='text-center text-warning' data-width='40' data-width-unit='px' style='width:40px;' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Expire soon")."'>".$url_items["certificates"]['icon']."</th>";
print "	<th data-field='expired' class='text-center text-danger' data-width='40' data-width-unit='px' style='width:40px;' data-bs-toggle='tooltip' data-bs-placement='top' title='"._("Expired")."'>".$url_items["certificates"]['icon']."</th>";
print "</tr>";
print "</thead>";

// body
print "<tbody>";

if (sizeof($zone_groups)==0) {
	print "<tr>";
	print "	<td colspan=9><i class='fa fa-database text-info' style='color:#ccc;padding:0px 5px;'></i> <span class='text-info'>". _("No zones available").".</span></td>";
	print "</tr>";
}
else {
	// body
	foreach ($zone_groups as $tenant_id=>$group) {

		// for admins show tenants
		if($user->admin=="1") {
			print "<tr class='header'>";
			print "	<td colspan=9 style='padding-top:25px'>".$url_items["tenants"]['icon']." "._("Tenant")." <a href='/".$user->href."/tenants/".$tenants[$tenant_id]->href."/' style='color:var(--tblr-info);'>".$tenants[$tenant_id]->name."</a>";

			print '<a href="/route/modals/zones/edit.php?action=add&tenant='.$tenants[$tenant_id]->href.'" data-bs-toggle="modal" class="btn btn-sm btn-outline-success float-end"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg> '._("Create new zone").'</a>';
			print "</td>";
			print "</tr>";
		}

		if(sizeof($group)==0) {
			print "<tr>";
			print "	<td colspan='9'>".'<div class="alert alert-info"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-info-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>'." "._("No zones available").".</div></td>";
			print "</tr>";
		}
		else {
		foreach ($group as $t) {

			$status            = $t->ignore == 0 ? "" : "<span class='badge bg-danger'>"._("Not checked")."</span>";
			$hosts             = $Database->count_database_objects("hosts", "z_id", $t->id);
			$certs             = $Zones->count_zone_certs ($t->id);
			$last_check        = $Zones->get_last_check ($t->id);
			// cert count
			$expired_certs_cnt = $Certificates->count_expired_by_zone ($t->id, 0);
			$expire_soon       = $Certificates->count_expired_by_zone ($t->id, $user->days);
			$expire_soon 	   = $expire_soon-$expired_certs_cnt;
			// ikona levo
			$icon_color        = $expired_certs_cnt == 0 ? "text-success" : "text-danger";
			$icon_color        = $hosts==0 ? "text-muted" : $icon_color;
			$icon_color 	   = $expired_certs_cnt == 0 && $expire_soon!=0 ? "text-warning" : $icon_color;
			// klase za badge
			$warning_class     = $expire_soon==0 ? "" : "text-warning";
			$danger_class      = $expired_certs_cnt==0 ? "" : "text-danger";

			// aicon
			$aicon 			   = $t->atype=="local" ? "L" : "R";

			print "<tr>";
			print "	<td><span class='$icon_color' style='color:#ccc;padding:0px 5px;'>".$url_items["zones"]['icon']." <strong><a href='/".$t->href."/zones/".$t->name."/' style='color:var(--tblr-info);'>".$t->name." $status</td>";
			print "	<td><span class='badge badge-outline text-azure $t->type'>".$t->type."</span></td>";
			print "	<td class='text-muted d-none d-lg-table-cell'>".$t->description."</td>";
			print "	<td class='text-muted d-none d-lg-table-cell'><span class='badge bg-azure-lt'>$aicon</span> ".$t->agname."</td>";
			print "	<td class='text-muted d-none d-lg-table-cell' style='font-size:11px;width:140px'>".$last_check."</td>";
			print "	<td class='text-center'><span class='badge badge-outline text-default' style='width:100%'>".$hosts."</span></td>";
			print "	<td class='text-center'><span class='badge badge-outline text-default' style='width:100%'>".$certs."</span></td>";
			print "	<td class='text-center'><span class='badge badge-outline text-default $warning_class' style='width:100%'>".$expire_soon."</span></td>";
			print "	<td class='text-center'><span class='badge badge-outline text-default $danger_class' style='width:100%'>".$expired_certs_cnt."</span></td>";
			print "</tr>";
		}
		}
	}
	print "</tbody>";
}
print "</table>";
print "</div>";

print '</div>';
print '</div>';
print '</div>';
print '</div>';
<?php

# validate user session
$User->validate_session ();
# validate tenant
$User->validate_tenant ();

# fetch zone
$zone = $Zones->get_zone ($_params['tenant'], $_params['app']);

# private zone — deny access if not the creator, or if inside an impersonation session
if ($zone !== NULL && !empty($zone->private_zone_uid) && ($zone->private_zone_uid != $user->id || isset($_SESSION['impersonate_original'])))
$zone = NULL;

# not existing ?
if ($zone==NULL) {

	// title
	print '<div class="page-header">';
	print '	<h2 class="page-title">'. _("Invalid zone").'</h2>';
	print '	<hr>';
	print '</div>';

	// back
	print "<div>";
	print '<div class="btn-group" role="group">';
	print '<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> '._("Back").'</a>';
	print '</div>';
	print "</div>";

	// content
	print '<div class="page-content">';
	$Result->show("danger", _("Zone does not exist."), false);
	print "</div>";
}
# ok
else {

	# hosts
	if(is_object($zone)) {
		// hosts
		$zone_hosts = $Zones->get_zone_hosts ($zone->id);
		// certificates
		$all_certs = $Certificates->get_all ();
	}
?>


<div class='page-header'>
	<h2 class='page-title'><?php print  $url_items["zones"]['icon']." "._("Zone details"); ?>  [<?php print @$zone->name; ?>]</h3>
	<hr>
</div>


<div>
	<a href="/zones/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>
</div><br><br>


<!-- details -->
<div>
	<?php include("zone-details.php"); ?>
</div>

<!-- hosts -->
<div style='margin-top:20px;'>
	<?php include("zone-hosts.php"); ?>
</div>
<?php } ?>
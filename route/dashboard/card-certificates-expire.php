<?php

# title
if($expired_certs) {
	$days           = 0;
	$days_expired   = $user->days_expired;
	$title          = "Expired certificates [in last $days_expired days]";
	$link           = "expired";
	$not_found_text = "No expired certificates found";
}
else {
	$days           = $user->days;
	$days_expired   = 0;
	$title          = "Certificates that expire soon"." [in next $days days]";
	$link           = "expire_soon";
	$not_found_text = "No certificates found that will expire soon";
}

?>


<div class="card-header">
	<h3 class="h3">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-certificate"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 15a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5" /><path d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -1 1.73" /><path d="M6 9l12 0" /><path d="M6 12l3 0" /><path d="M6 15l2 0" /></svg>
		<a href='/<?php print $user->href; ?>/certificates/<?php print $link; ?>/' style='color:var(--tblr-body-color)'><?php print _($title); ?></a>
	</h3>
</div>
<!-- <hr> -->

<div class="card-bod1y">

<!-- <div> -->
<?php
$certificates = $Certificates->get_expired ($days, $days_expired);

// none
if (sizeof($certificates)==0) {
	print "<div class='card-body'>";
	print "<div class='text-success'>";
	print '<span class="badge text-teal bg-teal-lt">';
	print '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>';
	print "</span>";
	print " "._($not_found_text);
	print "</div>";
	print "</div>";
}
else {
	# tenants
	$tenants = $Tenants->get_all ();

	print '<div class="table-responsive">';
	print "<table class='table align-top table-md table-hover'>";

	print "<thead>";
	print "<tr>";
	if($user->admin=="1")
	print "	<th data-field='tenant'>"._("Tenant")."</th>";
	print "	<th data-field='serial'>"._("Serial number")."</th>";
	print "	<th data-field='status' style='width:20px;'>"._("Status")."</th>";
	print "	<th data-field='issuer' class='d-none d-lg-table-cell'>"._("Issuer")."</th>";
	print "	<th data-field='domain'>"._("Common name")."</th>";
	print "	<th data-field='zone' class='d-none d-lg-table-cell'>"._("Zone")."</th>";
	print "	<th data-field='valid' class='align-top d-none d-lg-table-cell' data-width='150' data-width-unit='px'>"._("Valid to")."</th>";
	print "</tr>";
	print "</thead>";

	print "<tbody>";
	foreach ($certificates as $t) {
		// parse cert
		$cert_parsed = $Certificates->parse_cert ($t->certificate);

		// status
		$status = $Certificates->get_status ($cert_parsed);

		// text class
		$danger_class = "";
		if($status['code']==2)	{ $textclass='Expire soon'; $danger_class = "orange";  }
		else					{ $textclass='Expired';  	$danger_class = "red"; }

		print "<tr>";
		if($user->admin=="1")
		print "	<td><a href='/".$user->href."/tenants/".$tenants[$t->t_id]->href."/' style='color:var(--tblr-body-color)'>".$tenants[$t->t_id]->name."</a></td>";
		print "<td class='align-top'>";
		print "	<a href='/".$t->href."/certificates/".$t->zone_name."/".$cert_parsed['serialNumber']."/' style='color:var(--tblr-info)'>".$url_items["certificates"]['icon']." ".$cert_parsed['serialNumberHex']."</a>";
		print "</td>";
		print "	<td class='align-top'><span class='badge badge-outline text-$danger_class'>"._($textclass)."</span></td>";
		print "	<td class='align-top text-muted d-none d-lg-table-cell'>".$cert_parsed['issuer']['O']."</span></td>";
		print "	<td class='align-top'>".$cert_parsed['subject']['CN']."</td>";
		print "	<td class='align-top d-none d-lg-table-cell'><a href='/".$t->href."/zones/".$t->zone_name."/' style='color:var(--tblr-info)'>".$t->zone_name."</td>";
		print "	<td class='text-muted align-top d-none d-lg-table-cell'>".$cert_parsed['custom_validTo']." <span class='badge bg-$danger_class-lt'>".$cert_parsed['custom_validDays']." "._("days")."</span></td>";
		print "</tr>";
	}

	print "</tbody>";
	print "</table>";
	print "</div>";
}
?>
</div>
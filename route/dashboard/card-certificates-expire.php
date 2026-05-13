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
$Certificates->get_all_ignored_issuers();
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
	print "	<th data-field='status' style='width:20px;'>"._("Expires")." / Valid</th>";
	print "	<th data-field='serial'>"._("Serial number")."</th>";
	print "	<th data-field='issuer' class='d-none d-lg-table-cell'>"._("Issuer")." / "._("CNAME")."</th>";
	print "	<th data-field='zone' class='d-none d-lg-table-cell'>"._("Zone")."</th>";
	print "	<th data-field='hosts'>"._("Hosts")."</th>";
	print "</tr>";
	print "</thead>";

	print "<tbody>";
	$rows_printed = 0;
	foreach ($certificates as $t) {
		// parse cert
		$cert_parsed = $Certificates->parse_cert ($t->certificate);

		// skip if issuer is ignored for expiry
		$aki = str_replace("keyid:", "", $cert_parsed['extensions']['authorityKeyIdentifier'] ?? "");
		if ($Certificates->is_issuer_ignored($aki, $t->t_id, 'expired')) {
			continue;
		}
		$rows_printed++;

		// status
		$status = $Certificates->get_status ($cert_parsed);

		// text class
		$danger_class = "";
		if($status['code']==2)	{ $textclass='Expire soon'; $danger_class = "orange";  }
		else					{ $textclass='Expired';  	$danger_class = "red"; }

		// today ?
		if ($cert_parsed['custom_validDays']==0) {
			$cert_parsed['custom_validDays'] =  date("d", strtotime($cert_parsed['custom_validTo'])) == date("d") ? "Today" : "Tomorrow";
			$danger_class = "red";
		}
		else {
			$cert_parsed['custom_validDays'] .= " "._("days");
		}


		print "<tr>";
		print "	<td class='text-muted align-top d-none d-lg-table-cell'><span  class='w-100 badge text-$danger_class'>".$cert_parsed['custom_validDays']."</span><br>".date("d. M H:i", strtotime($cert_parsed['custom_validTo']))."</td>";
		print "<td class='align-top'>";
		print "	<a href='/".$t->href."/certificates/".$t->zone_name."/".$cert_parsed['serialNumber']."/' style='color:var(--tblr-info)'>".$url_items["certificates"]['icon']." ".$cert_parsed['serialNumberHex']."</a>";
		if($user->admin=="1")
		print "	<br><a href='/".$user->href."/tenants/".$tenants[$t->t_id]->href."/' style='color:var(--tblr-body-color)'>".$tenants[$t->t_id]->name."</a>";
		print "</td>";
		print "	<td class='align-top text-muted d-none d-lg-table-cell'>".$cert_parsed['issuer']['CN']."<br>".$cert_parsed['subject']['CN']."</span></td>";
		print "	<td class='align-top d-none d-lg-table-cell'><a href='/".$t->href."/zones/".$t->zone_name."/' style='color:var(--tblr-info)'>".$t->zone_name."</td>";
		print "	<td class='align-top'>";
		$hosts      = $t->hosts;
		$host_count = count($hosts);
		$first      = $hosts[0];
		print "<a href='/".$t->href."/zones/".$t->zone_name."/".$first->hostname."/' style='color:var(--tblr-secondary)' target='_blank'>".$first->hostname."</a>";
		if ($host_count > 1) {
			$extra = $host_count - 1;
			$uid   = 'hx-' . $t->id;
			print "<br><span class='badge bg-secondary-lt text-secondary mt-1' style='cursor:pointer' onclick=\"var el=document.getElementById('{$uid}');el.style.display=el.style.display==='none'?'block':'none'\">+{$extra} " . _("hosts") . "</span>";
			print "<div id='{$uid}' style='display:none;margin-top:4px'>";
			for ($hi = 1; $hi < $host_count; $hi++) {
				$h = $hosts[$hi];
				print "<a href='/".$t->href."/zones/".$t->zone_name."/".$h->hostname."/' style='color:var(--tblr-secondary)' target='_blank'>".$h->hostname."</a><br>";
			}
			print "</div>";
		}
		print "</td>";
		print "</tr>";
	}
	if ($rows_printed === 0) {
		print "<tr><td colspan=5><div class='card-body'><div class='text-success'><span class='badge text-teal bg-teal-lt'><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon icon-tabler icons-tabler-outline icon-tabler-check'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M5 12l5 5l10 -10' /></svg></span> "._($not_found_text)."</div></div></td></tr>";
	}

	print "</tbody>";
	print "</table>";
	print "</div>";
}
?>
</div>
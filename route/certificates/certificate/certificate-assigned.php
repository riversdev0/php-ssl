<?php

// found domains
if(!isset($is_from_fetch)) {

	// get all hosts
	$hosts = $Certificates->get_certificate_hosts ($certificate->id);


	if(sizeof($hosts)==0) {
		print "<div class='alert alert-info' style='margin:10px'>"._("Not assigned to any host")."</div></td>";
	}
	else {
		// group by zone
		$hosts_grouped = [];
		foreach ($hosts as $h) {
			$hosts_grouped[$h->name][] = $h;
		}
		foreach($hosts_grouped as $group=>$host) {
			print "<div style='padding:15px'>";
			print "<div style='margin-bottom:5px'>"._("Zone")." <a href='/".$_params['tenant']."/zones/".$h->name."/'> ".$host[0]->name."</a> :</div>";

			print "<div>";
			print "<table class='table table-borderless table-sm table-assigned-hosts' style='width:auto'>";
			foreach ($host as $h) {

				// check valoidity of certificate
				$h_cert_status = $Certificates->get_status ($certificate_details, true, true, $h->hostname);

				$h->ip = $User->validate_ip ($h->hostname) ? $h->hostname : $h->ip;
				$h->ip = strlen($h->ip)>0 ? $h->ip : "";

				print "<tr>";
				print "	<td style='padding-left:10px'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-server"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3" /><path d="M3 15a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3l0 -2" /><path d="M7 8l0 .01" /><path d="M7 16l0 .01" /></svg>'."</td>";
				print "	<td><a href='/".$_params['tenant']."/zones/".$h->name."/".$h->hostname."/'>".$h->hostname."</a></td>";
				print "	<td><span class='badge bg-light-lt'>".$h->ip."</span></td>";
				print "	<td>".$h_cert_status['text']."</td>";
				print "</tr>";
			}
			print "</table>";
			print "</div>";


			print "</div>";

		}
	}

}
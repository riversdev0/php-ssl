<hr>

<div class="main" style='margin-top: 30px;'>
	<?php
	// set from_search
	$from_search = true;

	// all certificates
	$all_certs = $Certificates->get_all ();


	// validate request !

	// none
	if(@$_params['hosts']!=="on" && @$_params['certificates']!=="on") {
		print "<div class='alert alert-warning'>";
		print '<strong>'._("Invalid search parameters").':</strong>'._("Nothing to search")."</div>";
		print "</div>";
	}
	// hosts
	elseif($_params['hosts']=="on") {
		// title
		print '<h2 class="h3">'._("Host search results").':</h4>';
		// search hosts
		$zone_hosts = $Zones->search_zone_hosts ($_params['search']);

		// include table
		include(dirname(__FILE__)."/../zones/zone/zone-hosts.php");
	}
	// certificates
	if ($_params['certificates']=="on") {
		// title
		print '<h2 class="h3" style="margin-top:30px">'._("Certificate search results").':</h4>';

		$certificates = [];

		foreach ($all_certs as $c) {
			// parse
			$cert_parsed = $Certificates->parse_cert ($c->certificate);

			// search cname
			if(is_array($cert_parsed['subject']['CN'])) {
				foreach($cert_parsed['subject']['CN'] as $i) {
					if(strpos($i, $_params['search'])!==false)   					  	{ $certificates[] = $c;	continue; }
				}
			}
			else {
				if(strpos($cert_parsed['subject']['CN'], $_params['search'])!==false)   { $certificates[] = $c;	continue; }
			}

			// search serial
			if(strpos($cert_parsed['serialNumber'], $_params['search'])!==false) 	  { $certificates[] = $c;	continue; }
			// search hex
			if(strpos($cert_parsed['serialNumberHex'], $_params['search'])!==false) { $certificates[] = $c;	continue; }
			// search altnames
			if(strpos($cert_parsed['extensions']['subjectAltName'], $_params['search'])!==false)  { $certificates[] = $c;	continue; }
		}

		// include table
		print "<div class='card'>";
		include(dirname(__FILE__)."/../certificates/all.php");
		print "</div>";
	}
	?>
</div>
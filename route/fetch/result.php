<?php

/**
 *
 * Fetch website certificate and display it.
 *
 */

// get agent
$agent = $Database->getObjectQuery("select * from agents where id = ?", [$_POST['agent_id']]);

// verify
if (is_null($agent)) {
		print "<div class='container-fluid main'>";
		print "<div class='alert alert-danger alert-block'>"._("Failed to obtain certificate");
		print "<hr>";
		print "Errors:";
		print "<ul><li class='text-muted'>"._("Invalid agent")."</ul>";
		print "</div>";
		print "</div>";
}
else {
	// fetch local
	if ($agent->atype=="local") {
		$fetched_cert = $SSL->fetch_website_certificate_single ($_POST['website']);
	}
	// fetch via agent
	else {
		// init agent
		$Agent = new Agent ($agent);
		// url to hostname / port
		$url_parsed = $Agent->url_to_hostname_port ($_POST['website']);
		// add host/port to agent
		$Agent->add_host_port ($url_parsed['host'], [$url_parsed['port']]);
		// execute
		$scan_res = $Agent->scan();

		// get cert
		$fetched_cert = $Agent->get_result ();
	}

	// check result - local
	if($fetched_cert===false && $agent->atype=="local") {
		print "<div class='container-fluid main'>";
		print "<div class='alert alert-danger alert-block'>"._("Failed to obtain certificate")." :: ".end($SSL->errors);
		print "<hr>";
		print "Errors:";
		print "<ul><li class='text-muted'>".implode("</li><li class='text-muted'>",$SSL->errors)."</ul>";
		print "</div>";
		print "</div>";
	}
	// check result - agent
	elseif ($fetched_cert===false || is_null($fetched_cert)) {
		print "<div class='container-fluid main'>";
		print "<div class='alert alert-danger alert-block'>"._("Failed to obtain certificate");
		print "<hr>";
		print "Errors:";
		print "<ul><li class='text-muted'>".implode("</li><li class='text-muted'>",$Agent->errors)."</ul>";
		print "</div>";
		print "</div>";
	}
	else {
		// set flag fetched already
		$is_from_fetch = true;
		// ok. save cert to fake object for later display
		$certificate = (object) $fetched_cert;

		// br
		print "<br><br>";

		// show parsed cert
		include(dirname(__FILE__)."/../certificates/certificate.php");

	}
}
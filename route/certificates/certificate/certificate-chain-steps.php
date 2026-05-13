<?php


// chain
$delimiter = "-----BEGIN CERTIFICATE-----\n";
$chains = array_reverse(array_values(array_filter(explode($delimiter, $certificate->chain))));


// chain
$cert_chain = $SSL->process_certificate_chain ($certificate->chain);

// print chain
$int = 1;
$valid_cert = false;
$valid_cert_text = [];



print "<ul class='steps steps-vertical' style='margin-left:0px;border-left:none; margin-top:20px;'>";


// chain print
foreach ($cert_chain as $index=>$cert) {

	// title
	if($index==sizeof($cert_chain)-1) 	{ $title = "Server"; }
	elseif($index==0) 					{ $title = "Root"; }
	else 								{ $title = _("Intermediate")." #".$int; $int++; }

	// errors ?
	if(sizeof($cert['errors'])>0) {
		$valid_cert = false;

		$validto_class 				  = isset($cert['errors']['validto']) ? "text-danger" : "";
		$authorityKeyIdentifier_class = isset($cert['errors']['authorityKeyIdentifier']) ? "text-danger" : "";
		$basicConstraints_class 	  = isset($cert['errors']['basicConstraints']) ? "text-danger" : "";
	}
	else {
		$validto_class 				  = "text-muted";
		$authorityKeyIdentifier_class = "text-muted";
		$basicConstraints_class 	  = "text-muted";
	}

	// ignored issuer ?
	$ignored_cert = $Certificates->is_issuer_ignored (str_replace("keyid:", "", $cert['certificate']['extensions']['authorityKeyIdentifier']), $certificate->t_id)===true ? " <span class='badge bg-warning'><i class='fa fa-volume-xmark'></i></span>" : "";
	$ignored_issuer = $Certificates->is_issuer_ignored ($cert['certificate']['extensions']['subjectKeyIdentifier'], $certificate->t_id)===true ? " <span class='badge bg-warning'>"._("Ignored issuer")."</span>" : "";

	// get hash
	$cert['raw'] = "-----BEGIN CERTIFICATE-----\n".$cert['raw'];
	$cert_x509 = openssl_x509_read($cert['raw']);



	print "<li class='step-item'>";
	print "<div class='h4 m-0'>"._($title)."</div>";

	print "<div>";

	print "<table class='table table-cert-details table-borderless table-auto table-details table-condensed' style='width:auto'>";
	print "<tr>";
	print "	<td style=''>";
	print "<strong><a href=''>".$cert['certificate']['subject']['CN']."</a></strong> $ignored_issuer $ignored_cert<br>";
	print _("Issued by").": ".$cert['certificate']['issuer']['CN']."<br>";
	print "<span class='text-muted $validto_class'>"._("Expires on").": ".date("Y-m-d H:i:s", $cert['certificate']['validTo_time_t'])."</span><br>";
	print "<span style='font-size:10px;padding-left:10px;font-style:italic' class='text-muted'>"._("SHA-256 Fingerprint").": ".chunk_split(openssl_x509_fingerprint($cert_x509, 'SHA256'), 2, ' ')."</span><br>";
	print "<span style='font-size:10px;padding-left:10px;font-style:italic' class='text-muted'>"._("Subject Key Identifier").": ".$cert['certificate']['extensions']['subjectKeyIdentifier']."</span><br>";
	print "<span style='font-size:10px;padding-left:10px;font-style:italic' class='text-muted $authorityKeyIdentifier_class'>"._("Authority Key Identifier").": ".str_replace("keyid:", "", $cert['certificate']['extensions']['authorityKeyIdentifier'])."</span><br>";
	print "<span style='font-size:10px;padding-left:10px;font-style:italic' class='text-muted $basicConstraints_class'>"._("basicConstraints").": ".$cert['certificate']['extensions']['basicConstraints']."</span><br>";
	print "<span style='font-size:10px;padding-left:10px;font-style:italic' class='text-muted'>"._("keyUsage").": ".$cert['certificate']['extensions']['keyUsage']."</span><br>";
	print "<span style='font-size:10px;padding-left:10px;' class='text-muted'><a href='/route/modals/certificates/download.php?certificate=".base64_encode($cert['raw'])."' data-bs-toggle='modal' data-bs-target='#modal1'><span class='badge badge-outline text-blue' style='width:auto;'>".$url_items['certificates']['icon']." "._("Download")."</a></span><br>";
	if(sizeof($cert['errors'])>0) {
		print "<span><ul style='margin-bottom:0px;list-style-type: none;padding-left:0px;margin-top:10px;'>";
		foreach ($cert['errors'] as $e) {
			print "<li style='font-size:11px;'><span class='badge bg-red-lt'>$e</span></li>";
		}
		print "</ul>";
	}
	print "</td>";
	print "</tr>";
	print "</table>";
	print "</div>";
}
print "</table>";
print "</ul>";


// fail ?
print "<hr>";
if($valid_cert===true) {
	print "<span class='alert alert-success' style='margin:10px;'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>'." "._("Certificate chain is Valid")."</span>";
}
else {
	print "<span class='alert alert-danger' style='margin:10px;'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-alert-triangle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0" /><path d="M12 16h.01" /></svg>'." ". _("Certificate chain is Invalid").".</span>";
}
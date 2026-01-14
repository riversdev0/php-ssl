<?php

# validate tenant
$User->validate_tenant ();

// fetch item
if(!isset($is_from_fetch)) {
	// validate zone
	$zone_check = $Zones->get_zone ($_params['tenant'], $_params['app']);
	// fetch cert
	if(!is_null($zone_check)) {
		$certificate = $Certificates->get_certificate_from_zone ($_params['id1'], $_params['tenant'], $zone_check->id);
	}
}

// invalid zone
if(is_null($zone_check) && !isset($is_from_fetch)) {
	print '<div class="page-header">';
	print '	<h2 class="page-title">'.$url_items['zones']['icon']." "._("Error").'</h2><hr>';
	print '</div>';
	print '<div class="page-content">';
	print '	<a href="/'.$_params['tenant'].'/certificates/" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg></i>'. _("All certificates").'</a>';
	print '	<div class="alert alert-danger" style="margin-top:10px;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-database-search"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6c0 1.657 3.582 3 8 3s8 -1.343 8 -3s-3.582 -3 -8 -3s-8 1.343 -8 3" /><path d="M4 6v6c0 1.657 3.582 3 8 3m8 -3.5v-5.5" /><path d="M4 12v6c0 1.657 3.582 3 8 3" /><path d="M15 18a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M20.2 20.2l1.8 1.8" /></svg>'._("Zone not found").'.</div>';
	print '</div>';
}
// null ?
elseif(is_null($certificate)) {
	print '<div class="page-header">';
	print '	<h2 class="page-title">'.$url_items['certificates']['icon']." "._("Error").'</h2><hr>';
	print '</div>';
	print '<div class="page-content">';
	print '	<a href="/'.$_params['tenant'].'/certificates/" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg>'. _("All certificates").'</a>';
	print '	<div class="alert alert-danger" style="margin-top:10px;">'.$url_items['certificates']['icon']." "._("Certificate not found").'.</div>';
	print '</div>';

}
else {
	// decode and print cert
	$certificate_details = $Certificates->parse_cert ($certificate->certificate);

	// CN
	if(is_array($certificate_details['subject']['CN'])) {
		$certificate_details['subject']['CN_all'] = implode("<br>", $certificate_details['subject']['CN']);
		$certificate_details['subject']['CN']     = $certificate_details['subject']['CN'][0];
	}
	else {
		$certificate_details['subject']['CN_all'] = $certificate_details['subject']['CN'];
	}

	// get all ignored issuers
	$Certificates->get_all_ignored_issuers ();

	// get public key details
	$key = openssl_pkey_get_public($certificate->certificate);
	$key_details = openssl_pkey_get_details($key);

	// get hash
	$cert = openssl_x509_read($certificate->certificate);

	// status
	$status = $Certificates->get_status ($certificate_details, true, false);

	// valid_period
	$valid_period = $certificate_details['custom_validAllDays']>398 ? "<br><span class='badge bg-orange-lt'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-alert-triangle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0" /><path d="M12 16h.01" /></svg>'." "._("Certificate validity is more than 398 days")."</span>" : "";

	// text class
	if($status['code']==0)		{ $textclass='muted'; }
	elseif($status['code']==1)	{ $textclass='danger'; }
	elseif($status['code']==2)	{ $textclass='warning'; }
	elseif($status['code']==3)	{ $textclass='success'; }
	else 						{ $textclass=''; }

	// no altnames
	if(!isset($certificate_details['extensions']['subjectAltName'])) {
		$certificate_details['extensions']['subjectAltName'] = "/";
	}

	// width:
	$td_min_width = "160px";
	?>

	<div class='page-header'>
		<h2 class="page-title"><?php print _("Certificate details"); ?> :: <?php print $certificate_details['subject']['CN']; ?></h2>
		<hr>
	</div>

	<!-- back -->
	<div>
		<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>
	</div>


	<!-- content -->
	<div style="margin-top:20px;">

	<div class="row">

	<!-- Issued to -->
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-6" style="margin-bottom: 15px;">
	<div class="card">
		<div class="card-header">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
			<?php print _("Issued to"); ?>
		</div>
		<div class="card-content">
			<?php include('certificate/certificate-issued-to.php'); ?>
		</div>
	</div>
	</div>

	<?php if($is_from_fetch) { ?>
	<!-- Fetch details-->
	<div class="col-12 col-xs-12 col-sm-12 col-md-12 col-lg-6" style="margin-bottom: 15px;">
	<div class="card">
		<div class="card-header"><?php print $url_items['scanning']['submenu']['agents']['icon']; ?> <?php print _("Connection details"); ?></div>
		<div class="card-content">
			<?php include('certificate/certificate-fetch.php'); ?>
		</div>
	</div>
	</div>
	<?php } ?>

	<!-- Assigned to -->
	<?php if(!isset($is_from_fetch)) { ?>
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-6" style="margin-bottom: 15px;">
	<div class="card">
		<div class="card-header">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-server"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3" /><path d="M3 15a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3l0 -2" /><path d="M7 8l0 .01" /><path d="M7 16l0 .01" /></svg>
			<?php print _("Assigned hosts"); ?>
		</div>
		<div class="card-content">
			<?php include('certificate/certificate-assigned.php'); ?>
		</div>
	</div>
	</div>
	<?php } ?>

	<!-- Issuer -->
	<div class="col-12" style="margin-bottom: 15px;">
	<div class="card">
		<div class="card-header">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-id"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v10a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3l0 -10" /><path d="M7 10a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M15 8l2 0" /><path d="M15 12l2 0" /><path d="M7 16l10 0" /></svg>
			<?php print _("Issuer"); ?>
		</div>
		<div class="card-content">
			<?php include('certificate/certificate-issuer.php'); ?>
		</div>
	</div>
	</div>

	<!-- Details -->
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" style="margin-bottom: 15px;">
	<div class="card">
		<div class="card-header">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-certificate"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 15a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5" /><path d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -1 1.73" /><path d="M6 9l12 0" /><path d="M6 12l3 0" /><path d="M6 15l2 0" /></svg>
			<?php print _("Certificate details"); ?>
		</div>
		<div class="card-content">
			<?php include('certificate/certificate-details.php'); ?>
		</div>
	</div>
	</div>

	<!-- FP -->
	<div class="col-12" style="margin-bottom: 15px;">
	<div class="card">
		<div class="card-header">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-fingerprint"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18.9 7a8 8 0 0 1 1.1 5v1a6 6 0 0 0 .8 3" /><path d="M8 11a4 4 0 0 1 8 0v1a10 10 0 0 0 2 6" /><path d="M12 11v2a14 14 0 0 0 2.5 8" /><path d="M8 15a18 18 0 0 0 1.8 6" /><path d="M4.9 19a22 22 0 0 1 -.9 -7v-1a8 8 0 0 1 12 -6.95" /></svg>
			<?php print _("Fingerprints"); ?>
		</div>
		<div class="card-content">
			<?php include('certificate/certificate-fingerprints.php'); ?>
		</div>
	</div>
	</div>

	<!-- Extensions -->
	<div class="col-12" style="margin-bottom: 15px;">
	<div class="card">
		<div class="card-header">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-hexagon-plus"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.875 6.27c.7 .398 1.13 1.143 1.125 1.948v7.284c0 .809 -.443 1.555 -1.158 1.948l-6.75 4.27a2.269 2.269 0 0 1 -2.184 0l-6.75 -4.27a2.225 2.225 0 0 1 -1.158 -1.948v-7.285c0 -.809 .443 -1.554 1.158 -1.947l6.75 -3.98a2.33 2.33 0 0 1 2.25 0l6.75 3.98h-.033" /><path d="M9 12h6" /><path d="M12 9v6" /></svg>
			<?php print _("Extensions"); ?>
		</div>
		<div class="card-content">
			<?php include('certificate/certificate-extensions.php'); ?>
		</div>
	</div>
	</div>

	<!-- chain -->
	<div class="col-12" style="margin-bottom: 15px;">
	<div class="card">
		<div class="card-header">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-link"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 15l6 -6" /><path d="M11 6l.463 -.536a5 5 0 0 1 7.071 7.072l-.534 .464" /><path d="M13 18l-.397 .534a5.068 5.068 0 0 1 -7.127 0a4.972 4.972 0 0 1 0 -7.071l.524 -.463" /></svg>
			<?php print _("Certificate Chain"); ?>
		</div>
		<div class="card-content">
			<?php include('certificate/certificate-chain-steps.php'); ?>
		</div>
	</div>
	</div>

	</div>

<?php } ?>
</div>
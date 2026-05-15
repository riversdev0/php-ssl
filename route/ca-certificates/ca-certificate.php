<?php

$User->validate_tenant();

$app = $_params['app'] ?? '';
if ($app === '') {
	print '<div class="alert alert-danger" style="margin-top:10px;">' . _("Invalid CA.") . '</div>';
	return;
}

$base_select = "SELECT ca.*, (pk.private_key_enc IS NOT NULL AND pk.private_key_enc != '') AS has_pkey,
                       pca.name AS parent_ca_name
                FROM cas ca
                LEFT JOIN pkey pk ON ca.pkey_id = pk.id
                LEFT JOIN cas pca ON ca.parent_ca_id = pca.id";

// Look up by serial first, fall back to numeric ID for legacy URLs
if ($user->admin === "1") {
	$ca_rows = $Database->getObjectsQuery("$base_select WHERE ca.serial = ?", [$app]);
	if (empty($ca_rows) && ctype_digit($app)) {
		$ca_rows = $Database->getObjectsQuery("$base_select WHERE ca.id = ?", [(int)$app]);
	}
} else {
	$ca_rows = $Database->getObjectsQuery("$base_select WHERE ca.serial = ? AND ca.t_id = ?", [$app, (int)$user->t_id]);
	if (empty($ca_rows) && ctype_digit($app)) {
		$ca_rows = $Database->getObjectsQuery("$base_select WHERE ca.id = ? AND ca.t_id = ?", [(int)$app, (int)$user->t_id]);
	}
}

if (empty($ca_rows)) {
	print '<div class="page-header"><h2 class="page-title">' . $url_items['ca-certificates']['icon'] . ' ' . _("Error") . '</h2><hr></div>';
	print '<div class="page-content">';
	print '<a href="/' . $_params['tenant'] . '/ca-certificates/" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> ' . _("All CA certificates") . '</a>';
	print '<div class="alert alert-danger" style="margin-top:10px;">' . $url_items['ca-certificates']['icon'] . ' ' . _("CA certificate not found") . '.</div>';
	print '</div>';
	return;
}
$ca = $ca_rows[0];

// Auto-backfill serial for legacy records that don't have it yet
if (empty($ca->serial) && !empty($ca->certificate)) {
	$_parsed_serial = openssl_x509_parse($ca->certificate);
	if ($_parsed_serial && !empty($_parsed_serial['serialNumberHex'])) {
		$ca->serial = strtolower($_parsed_serial['serialNumberHex']);
		$Database->runQuery("UPDATE cas SET serial = ? WHERE id = ?", [$ca->serial, (int)$ca->id]);
	}
}

if (empty($ca->certificate)) {
	print '<div class="page-header"><h2 class="page-title">' . $url_items['ca-certificates']['icon'] . ' ' . _("Error") . '</h2><hr></div>';
	print '<div class="page-content">';
	print '<a href="/' . $_params['tenant'] . '/ca-certificates/" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> ' . _("All CA certificates") . '</a>';
	print '<div class="alert alert-danger" style="margin-top:10px;">' . _("No certificate data available for this CA.") . '</div>';
	print '</div>';
	return;
}

// Build compatibility object for sub-pages that expect $certificate
$certificate = (object)[
	'id'          => $ca->id,
	'certificate' => $ca->certificate,
	'pkey_id'     => $ca->pkey_id ?? null,
	'chain'       => null,
	'is_manual'   => '0',
	'created'     => $ca->created,
	't_id'        => $ca->t_id,
	'serial'      => '',
];

// Suppress action buttons in certificate-issued-to.php
$is_from_fetch = true;

// Parse certificate
$certificate_details = $Certificates->parse_cert($ca->certificate);

// CN handling
if (is_array($certificate_details['subject']['CN'])) {
	$certificate_details['subject']['CN_all'] = implode("<br>", $certificate_details['subject']['CN']);
	$certificate_details['subject']['CN']     = $certificate_details['subject']['CN'][0];
} else {
	$certificate_details['subject']['CN_all'] = $certificate_details['subject']['CN'];
}

// No altnames fallback
if (!isset($certificate_details['extensions']['subjectAltName'])) {
	$certificate_details['extensions']['subjectAltName'] = "/";
}

$Certificates->get_all_ignored_issuers();

// Public key details
$key      = openssl_pkey_get_public($ca->certificate);
$key_details = openssl_pkey_get_details($key);

// X.509 resource for fingerprints
$cert = openssl_x509_read($ca->certificate);

// Status and display classes
$status       = $Certificates->get_status($certificate_details, true, false);
$valid_period = $certificate_details['custom_validAllDays'] > 398
	? "<br><span class='badge bg-orange-lt'><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon icon-tabler icons-tabler-outline icon-tabler-alert-triangle'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 9v4' /><path d='M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0' /><path d='M12 16h.01' /></svg> " . _("Certificate validity is more than 398 days") . "</span>"
	: "";

if     ($status['code'] == 0) { $textclass = 'muted'; }
elseif ($status['code'] == 1) { $textclass = 'danger'; }
elseif ($status['code'] == 2) { $textclass = 'warning'; }
elseif ($status['code'] == 3) { $textclass = 'success'; }
else                          { $textclass = ''; }

$td_min_width = "160px";

// Fetch issued certificates (certs where AKI matches this CA's SKI)
$issued_certs = [];
if (!empty($ca->ski)) {
	$issued_certs = $Database->getObjectsQuery(
		"SELECT c.id, c.serial, c.expires,
		        z.name AS zone_name,
		        (SELECT h2.hostname FROM hosts h2 WHERE h2.c_id = c.id ORDER BY h2.id LIMIT 1) AS primary_hostname,
		        (SELECT COUNT(*) FROM hosts h2 WHERE h2.c_id = c.id) AS host_count
		 FROM certificates c
		 JOIN zones z ON c.z_id = z.id
		 WHERE c.aki = ? AND c.t_id = ?
		 ORDER BY c.expires DESC
		 LIMIT 500",
		[$ca->ski, (int)$ca->t_id]
	);
}
?>

<div class='page-header'>
	<h2 class="page-title"><?php print _("CA Certificate details"); ?> :: <?php print htmlspecialchars($certificate_details['subject']['CN']); ?></h2>
	<hr>
</div>

<div>
	<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>
</div>

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
		<?php include(dirname(__FILE__) . '/ca-certificate-issued-to.php'); ?>
	</div>
</div>
</div>

<!-- Issued certificates -->
<div class="col-xs-12 col-sm-12 col-md-12 col-lg-6" style="margin-bottom: 15px;">
<div class="card">
	<div class="card-header">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-certificate"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 15a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5" /><path d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -1 1.73" /><path d="M6 9l12 0" /><path d="M6 12l3 0" /><path d="M6 15l2 0" /></svg>
		<?php print _("Issued certificates"); ?>
		<?php if (!empty($issued_certs)): ?>
		<span class="badge bg-blue-lt ms-1"><?php print count($issued_certs); ?></span>
		<?php endif; ?>
	</div>
	<div class="card-content">
	<?php if (empty($ca->ski)): ?>
		<div class="alert alert-info" style="margin:10px"><?php print _("No Subject Key Identifier — cannot match issued certificates."); ?></div>
	<?php elseif (empty($issued_certs)): ?>
		<div class="alert alert-info" style="margin:10px"><?php print _("No issued certificates found."); ?></div>
	<?php else: ?>
		<table class="table table-borderless table-sm" style="margin:10px; width:auto">
		<?php
		$now = time();
		foreach ($issued_certs as $ic) {
			$exp_ts   = $ic->expires ? strtotime($ic->expires) : 0;
			$exp_date = $exp_ts ? date('Y-m-d', $exp_ts) : '—';
			if (!$exp_ts) {
				$exp_badge = "<span class='text-muted'>—</span>";
			} elseif ($exp_ts < $now) {
				$exp_badge = "<span class='badge bg-danger-lt text-danger me-1'>" . _("Expired") . "</span><span class='text-muted small'>" . $exp_date . "</span>";
			} elseif (($exp_ts - $now) < 30 * 86400) {
				$exp_badge = "<span class='badge bg-warning-lt text-warning me-1'>" . _("Expiring") . "</span><span class='text-muted small'>" . $exp_date . "</span>";
			} else {
				$exp_badge = "<span class='text-muted small'>" . $exp_date . "</span>";
			}
			$cert_url      = '/' . $_params['tenant'] . '/certificates/' . htmlspecialchars($ic->zone_name) . '/' . htmlspecialchars($ic->serial) . '/';
			$host_url      = $ic->primary_hostname ? '/' . $_params['tenant'] . '/zones/' . htmlspecialchars($ic->zone_name) . '/' . htmlspecialchars($ic->primary_hostname) . '/' : null;
			$serial_cell   = "<a href='{$cert_url}' class='font-monospace small'>" . htmlspecialchars($ic->serial) . "</a>";
			$hostname_cell = $ic->primary_hostname
				? "<a href='{$host_url}'>" . htmlspecialchars($ic->primary_hostname) . "</a>" . ($ic->host_count > 1 ? " <span class='text-muted small'>+" . ((int)$ic->host_count - 1) . "</span>" : "")
				: "<span class='text-muted'>—</span>";
			print "<tr>";
			print "<td>{$serial_cell}</td>";
			print "<td class='ps-2'>{$hostname_cell}</td>";
			print "<td class='text-muted small ps-2'>" . htmlspecialchars($ic->zone_name) . "</td>";
			print "<td class='ps-2'>{$exp_badge}</td>";
			print "</tr>";
		}
		?>
		</table>
	<?php endif; ?>
	</div>
</div>
</div>

<!-- Issuer -->
<div class="col-12" style="margin-bottom: 15px;">
<div class="card">
	<div class="card-header">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-id"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v10a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3l0 -10" /><path d="M7 10a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M15 8l2 0" /><path d="M15 12l2 0" /><path d="M7 16l10 0" /></svg>
		<?php print _("Issuer"); ?>
	</div>
	<div class="card-content">
		<?php include(dirname(__FILE__) . '//../certificates/certificate/certificate-issuer.php'); ?>
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
		<?php include(dirname(__FILE__) . '//../certificates/certificate/certificate-details.php'); ?>
	</div>
</div>
</div>

<!-- Fingerprints -->
<div class="col-12" style="margin-bottom: 15px;">
<div class="card">
	<div class="card-header">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-fingerprint"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18.9 7a8 8 0 0 1 1.1 5v1a6 6 0 0 0 .8 3" /><path d="M8 11a4 4 0 0 1 8 0v1a10 10 0 0 0 2 6" /><path d="M12 11v2a14 14 0 0 0 2.5 8" /><path d="M8 15a18 18 0 0 0 1.8 6" /><path d="M4.9 19a22 22 0 0 1 -.9 -7v-1a8 8 0 0 1 12 -6.95" /></svg>
		<?php print _("Fingerprints"); ?>
	</div>
	<div class="card-content">
		<?php include(dirname(__FILE__) . '//../certificates/certificate/certificate-fingerprints.php'); ?>
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
		<?php include(dirname(__FILE__) . '//../certificates/certificate/certificate-extensions.php'); ?>
	</div>
</div>
</div>

</div>
</div>

<?php

// Fetch pkey record (same as shared certificate-issued-to.php)
$_pkey = $certificate->pkey_id
    ? $Database->getObjectQuery("SELECT * FROM pkey WHERE id = ?", [$certificate->pkey_id])
    : null;
$_has_private_key = $_pkey && !empty($_pkey->private_key_enc);

print "<table class='table table-cert-details table-borderless table-auto table-details table-md' style='width:auto;margin:10px'>";

print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>" . _("Common name") . "</td>";
print "	<td>" . $certificate_details['subject']['CN_all'] . "</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary'>" . _("Valid for domains") . "</td>";
print "	<td>" . str_replace(",", "<br>", $certificate_details['extensions']['subjectAltName']) . "</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary'>" . _("Status") . "</td>";
print "	<td>" . $status['text'] . $valid_period . "</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary'>" . _("Discovered") . "</td>";
print "	<td>" . $certificate->created . "</td>";
print "</tr>";

// CA-specific rows
print "<tr>";
print "	<td class='text-secondary'>" . _("Ignore updates") . "</td>";
print "	<td>";
if ($ca->ignore_updates) {
    print "<span class='badge bg-warning-lt text-warning'>" . _("Yes") . "</span>";
} else {
    print "<span class='badge bg-secondary-lt text-muted'>" . _("No") . "</span>";
}
print "	</td>";
print "</tr>";

print "<tr>";
print "	<td class='text-secondary'>" . _("Ignore expiry") . "</td>";
print "	<td>";
if ($ca->ignore_expiry) {
    print "<span class='badge bg-warning-lt text-warning'>" . _("Yes") . "</span>";
} else {
    print "<span class='badge bg-secondary-lt text-muted'>" . _("No") . "</span>";
}
print "	</td>";
print "</tr>";

print "<tr>";
print "	<td></td>";
print "	<td style='padding-top:10px'>";
print "		<a href='/route/modals/cas/view.php?ca_id=" . (int)$ca->id . "' class='btn btn-sm btn-outline-secondary me-1' data-bs-toggle='modal' data-bs-target='#modal1'>";
print "			<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1' /><path d='M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z' /><path d='M16 5l3 3' /></svg> ";
print "			" . _("Edit properties") . "</a>";
print "		<a href='/route/ajax/ca/download.php?ca_id=" . (int)$ca->id . "&type=crt' class='btn btn-sm bg-info-lt text-info'>";
print "			<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2' /><path d='M7 11l5 5l5 -5' /><path d='M12 4l0 12' /></svg> ";
print "			.crt</a>";
print "	</td>";
print "</tr>";

print "</table>";

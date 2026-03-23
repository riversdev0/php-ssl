<?php

//
// Certificate details
//
print "<table class='table table-cert-details table-borderless table-auto table-details table-condensed' style='width:auto;margin:10px'>";

print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("Serial number")."</td>";
print "	<td>".chunk_split($certificate_details['serialNumberHex'], 2, ' ');
if(isset($certificate->is_manual) && $certificate->is_manual == "1") {
	print " <span class='badge bg-azure-lt text-azure ms-1' title='"._("This certificate was manually imported")."'><svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 3l8 4.5v9l-8 4.5l-8 -4.5v-9z' /><path d='M12 12l8 -4.5' /><path d='M12 12v9' /><path d='M12 12l-8 -4.5' /></svg> "._("Manual")."</span>";
}
print "</td>";
print "</tr>";

if(isset($certificate->is_manual) && $certificate->is_manual == "1") {
print "<tr>";
print "	<td class='text-secondary'>"._("Import type")."</td>";
print "	<td><span class='badge bg-azure-lt text-azure'><svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 3l8 4.5v9l-8 4.5l-8 -4.5v-9z' /><path d='M12 12l8 -4.5' /><path d='M12 12v9' /><path d='M12 12l-8 -4.5' /></svg> "._("Manually imported")."</span> <span class='text-muted small'>"._("This certificate was manually uploaded and will not be removed by orphaned-certificate cleanup.")."</span></td>";
print "</tr>";
}
print "<tr>";
print "	<td class='text-secondary'>"._("Key size")."</td>";
print "	<td>".$key_details['bits']." kB</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary'>"._("Version")."</td>";
print "	<td>".$certificate_details['version']."</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary'>"._("Signature algoritdm")."</td>";
print "	<td>".$certificate_details['signatureTypeSN']."</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary'>"._("Valid from")."</td>";
print "	<td>".date("Y-m-d H:i:s", $certificate_details['validFrom_time_t'])."</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary'>"._("Valid Until")."</td>";
print "	<td class='text-$textclass'>".date("Y-m-d H:i:s", $certificate_details['validTo_time_t'])." (".$certificate_details['custom_validDays']." "._("days remaining").")</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary'>"._("Lifetime")."</td>";
print "	<td>".$certificate_details['custom_validAllDays']." "._("days")." $valid_period</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary' style='border-top:1px solid var(--tblr-card-border-color)'>"._("Purposes")."</td>";
print "	<td style='vertical-align:center;border-top:1px solid var(--tblr-card-border-color)'>";
foreach($certificate_details['custom_purposes'] as $p=>$val) {
	$icon = $val == "Yes" ? "fa-check text-success" : "fa-times text-danger";

	if($val == "Yes") {
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>';
		$badgeClass = "badge bg-green-lt";
	}
	else {
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-x"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>';
		$badgeClass = "badge bg-red-lt";
	}


	print "<span class='$badgeClass' style='margin-bottom:3px'>$icon</span> <span style='padding:3px'>".$p."</span><br>";
}
print "</td>";
print "</tr>";

print "</table>";
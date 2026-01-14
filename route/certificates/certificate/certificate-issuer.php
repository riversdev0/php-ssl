<?php


print "<table class='table table-cert-details table-borderless table-auto table-details table-condensed' style='width:auto;margin:10px'>";

// Issuer
print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("Common name")."</td class='text-secondary'>";
print "	<td>".$certificate_details['issuer']['CN']."</td>";
print "</tr>";
if(strlen($certificate_details['issuer']['O'])>0) {
print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("Organisation name")."</td class='text-secondary'>";
print "	<td>".$certificate_details['issuer']['O']."</td>";
print "</tr>";
}
if(strlen($certificate_details['issuer']['C'])>0) {
print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("Country")."</td class='text-secondary'>";
print "	<td>".$certificate_details['issuer']['C']."</td>";
print "</tr>";
}
if(strlen($certificate_details['issuer']['ST'])) {
print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("County")."</td class='text-secondary'>";
print "	<td>".$certificate_details['issuer']['ST']."</td>";
print "</tr>";
}
if(strlen($certificate_details['issuer']['L'])) {
print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("Locality")."</td class='text-secondary'>";
print "	<td>".$certificate_details['issuer']['L']."</td>";
print "</tr>";
}

print '</table>';
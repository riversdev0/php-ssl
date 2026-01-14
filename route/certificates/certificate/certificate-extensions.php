<?php


print "<table class='table table-cert-details table-borderless table-auto table-details table-condensed' style='width:auto;margin:10px'>";


// Extensions
unset($certificate_details['extensions']['ct_precert_scts']);
unset($certificate_details['extensions']['subjectAltName']);


foreach($certificate_details['extensions'] as $ext_key=>$e) {
	print "<tr>";
	print "	<td class='text-secondary' style='min-width:$td_min_width'>".ucwords(preg_replace('/(?<!\ )[A-Z]/', ' $0', $ext_key))."</td>";
	print "	<td>".str_replace(",","<br>",$e)."</td>";
	print "</tr>";
}


print "</table>";
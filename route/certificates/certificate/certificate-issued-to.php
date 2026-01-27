<?php

print "<table class='table table-cert-details table-borderless table-auto table-details table-md' style='width:auto;margin:10px'>";

print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("Common name")."</td>";
print "	<td>".$certificate_details['subject']['CN_all']."</td>";
print "</tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("Valid for domains")."</td>";
print "	<td>".str_replace(",","<br>",$certificate_details['extensions']['subjectAltName'])."</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("Status")."</td>";
print "	<td>".$status['text']."$valid_period</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("Discovered")."</td>";
print "	<td>".$certificate->created."</td>";
print "</tr>";
print "<tr>";
print "	<td></td>";
print "	<td><hr>";
print "<a class='btn btn-outline btn-info btn-sm' href='/route/modals/certificates/download.php?certificate=".base64_encode($certificate->certificate)."' data-bs-toggle='modal' data-bs-target='#modal1'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-download"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>'." "._("Download")."</a>";
if(!isset($is_from_fetch))
print "<a class='btn btn-outline btn-danger btn-sm' style='margin-left:5px' href='/route/modals/certificates/delete.php?tenant=".$_params['tenant']."&serial=".$certificate_details['serialNumber']."' data-bs-toggle='modal' data-bs-target='#modal1'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>'._("Remove certificate");
print "</td>";
print "</tr>";

print "</table>";
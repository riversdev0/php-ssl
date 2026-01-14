<?php

// make sure it is object
$fetched_cert = (object) $fetched_cert;

print "<div style='padding:15px'>";
print "<table class='table table-cert-details table-borderless table-auto table-details table-md' style='width:auto'>";
print "<tr>";
print "	<td class='text-secondary'>"._("Agent")."</th>";
print "	<td>".$agent->name."</td>";
print "</tr>";		print "<tr>";
print "	<td class='text-secondary'>"._("IP address")."</th>";
print "	<td>".$fetched_cert->ip."</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary'>"._("Port")."</th>";
print "	<td>tcp/".$fetched_cert->port."</td>";
print "</tr>";
print "	<td class='text-secondary'>"._("TLS version")."</th>";
print "	<td>".$fetched_cert->tls_proto."</td>";
print "</tr>";
print "</table>";
print "</div>";
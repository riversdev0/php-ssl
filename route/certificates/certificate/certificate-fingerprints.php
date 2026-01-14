<?php

print "<table class='table table-cert-details table-borderless table-auto table-details table-condensed' style='width:auto;margin:10px'>";


// Fingerptints
print "<tr>";
print "	<td class='text-secondary' style='min-width:$td_min_width'>"._("SHA-512")."</td>";
print "	<td>".chunk_split(openssl_x509_fingerprint($cert, 'SHA512'), 2, ' ')."</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary'>"._("SHA-256")."</td>";
print "	<td>".chunk_split(openssl_x509_fingerprint($cert, 'SHA256'), 2, ' ')."</td>";
print "</tr>";
print "<tr>";
print "	<td class='text-secondary'>"._("SHA-1")."</td>";
print "	<td>".chunk_split(openssl_x509_fingerprint($cert, 'SHA1'), 2, ' ')."</td>";
print "</tr>";

print "</table>";
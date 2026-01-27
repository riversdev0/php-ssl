<?php

# headers
header("Cache-Control: private");
header("Content-Description: File Transfer");
header('Content-type: application/octet-stream');
header('Content-Disposition: attachment; filename="error.txt');

#
# Download certificate
#

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, false);
# validate permissions
$User->validate_user_permissions (1, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# decode
$certificate = base64_decode($_GET['certificate']);

# validate cert
if(openssl_x509_parse($certificate)===false) {
	print _("Cannot parse certificate");
}
# validate format
elseif(!$Certificates->validate_cert_format ($_GET['format'])) {
	print _("Invalid format");
}
else {
	// parse cert to set name
	$certificate_details = $Certificates->parse_cert ($certificate);
	// name
	$filename = $certificate_details['subject']['CN'].".".$_GET['format'];
	// convert ?
	$cert_converted = $Certificates->cert_reformat ($certificate, $_GET['format']);

	// reset name
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	// print cert
	print($certificate);
}
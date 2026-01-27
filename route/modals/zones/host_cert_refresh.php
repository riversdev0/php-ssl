<?php

#
# Refresh cert
#



# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, false);
# validate permissions
$User->validate_user_permissions (2, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant (false, true);

# get tenant
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);

// title
$title = _("Update host SSL certificate");
// content
$content = [];

// hader
$header_class = "info";

# try to fetch certificate
try {
	// set execution time
	$execution_time = date('Y-m-d H:i:s');
    // fetch hosts
	$host = $Database->getObjectQuery("select *,h.id as host_id,z.name as zone_name,a.name as agname,z.t_id as t_id from agents as a, `hosts` as h, `zones` as z where h.`z_id` = z.id and z.agent_id = a.id and z.`t_id` = ? and h.ignore = 0 and h.id = ?", [$tenant->id, $_GET['host_id']]);

	// fetch cert
	$host_certificate = $SSL->fetch_website_certificate ($host, $execution_time, $tenant->id);

	// update cert if fopund
	if ($host_certificate!==false) {
		$cert_id = $SSL->update_db_certificate ($host_certificate, $host->t_id, $host->z_id, $execution_time);
		// get IP if not set from remote agent
		$ip = !isset($host_certificate['ip']) ? $SSL->resolve_ip($host->hostname) : $host_certificate['ip'];
		// if Id of certificate changed
		if($host->c_id!=$cert_id) {
			$SSL->assign_host_certificate ($host->hostname, $ip, $host->host_id, $cert_id, $host_certificate['port'], $execution_time, $host_certificate['tls_proto']);
		}

		// parse cert and set text
		$cert_parsed = $Certificates->parse_cert ($host_certificate['certificate']);

		// status
		$status = $Certificates->get_status ($cert_parsed, true, true, $host->hostname);

		$cert_text = [];
		$cert_text[] = "<div class='' style='line-height:1.5rem'>";
		$cert_text[] = _("Issuer").": ".$cert_parsed['issuer']['O']."<br>";
		$cert_text[] = _("Status").": ".$status['text']."<br>";
		$cert_text[] = _("Subject").": ".$cert_parsed['subject']['CN']."<br>";
		$cert_text[] = _("Serial").": ".$cert_parsed['serialNumberHex']."<br>";
		$cert_text[] = _("Valid to").": ".$cert_parsed['custom_validTo']." (".$cert_parsed['custom_validDays']." days)"."<br>";
		$cert_text[] = _("TLS version").": ".$host_certificate['tls_proto']."<br>";
		$cert_text[] = _("Scan agent").": ".$host->agname."<br>";
		$cert_text[] = "<hr><a href='".$tenant->href."/certificates/".$host->zone_name."/".$cert_parsed['serialNumber']."/' target='_blank' class='btn btn-sm btn-outline-info'>".$url_items["certificates"]["icon"]." "._("Show certificate details")."</a>";
		$cert_text[] = "</div>";
		// ok
		$content[] = $Result->show("success", _("Certificate fetched"), false, false, true, false);
		$content[] = "<hr>";
		$content[] = implode("", $cert_text);
	}
	// error
	else {
		$content[] = $Result->show("danger alert-block", _("Failed to obtain certificate")." :: ".end($SSL->errors).".", false, false, true, false);
		// print if more errors are present
		if(sizeof($SSL->errors)>1) {
			$content[] = "<hr>";
			$content[] = "Errors:";
			$content[] = "<ul><li class='text-muted'>".implode("</li><li class='text-muted'>",$SSL->errors)."</ul>";
		}
	}
} catch (Exception $e) {
    // print error
	$content[] = $Result->show("danger", $e->getMessage(), false, false, true, false);
}


# print modal
$Modal->modal_print ($title, implode("\n", $content), "", "", true, $header_class);
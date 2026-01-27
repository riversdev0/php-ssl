<?php

#
# Edit zone - truncate
#



# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, false);
# validate permissions
$User->validate_user_permissions (3, true);

# strip tags
$_GET = $User->strip_input_tags ($_GET);

# validate tenant
$_params['tenant'] = $_GET['tenant'];
$User->validate_tenant (false, true);

# fetch tenant
$tenant = $Tenants->get_tenant_by_href ($_GET['tenant']);
# fetch zone
$zone = $Zones->get_zone ($_GET['tenant'], $_GET['zone_id']);
# fetch hosts
$zone_hosts = $Database->getObjectsQuery("select *,h.id as host_id from `hosts` as h, `zones` as z, agents as a where h.`z_id` = z.id and z.agent_id = a.id and z.`t_id` = ? and h.ignore = 0 and h.`z_id` = ?", [$tenant->id, $zone->id]);

// set execution time
$execution_time = date('Y-m-d H:i:s');

# title
$title = _("Refresh all host certificates");

# ok, validations passed, insert
try {
	// loop
	foreach ($zone_hosts as $host) {
		// fetch cert
		$host_certificate = $SSL->fetch_website_certificate ($host, $execution_time, $tenant->id);
		// update cert if fopund
		if ($host_certificate!==false) {
			$cert_id = $SSL->update_db_certificate ($host_certificate, $host->t_id, $host->z_id, $execution_time);
			// get IP if not set from remote agent
			$ip = !isset($host_certificate['ip']) ? $SSL->resolve_ip($host->hostname) : $host_certificate['ip'];
			// if Id of certificate changed
			if($host->c_id!=$cert_id) {
				$SSL->assign_host_certificate ($host->hostname, $ip, $host->host_id, $cert_id, $host_certificate['port'], $execution_time);
			}
		}
	}

	// content
	$content[] = $Result->show("success", _("All certificates fetched"), false, false, true, true);
} catch (Exception $e) {
	// error
	$content[] = $Result->show("danger", $e->getMessage().".", false, false, true, true);
}

// modal
$Modal->modal_print ($title, implode("\n", $content), $btn_text, "", true);
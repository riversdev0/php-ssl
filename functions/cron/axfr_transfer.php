<?php

/**
 *
 * Get hostnames from AQXFR zone transfer
 *
 * $j object is passed to scrip :: $j->t_id is tenant id
 *
 *
 * ----- TODO : Forking -------
 *
 */

# load classes
$Result   = new Result ();
$Common   = new Common ();
$URL      = new URL ();
$Database = new Database_PDO ();
$SSL      = new SSL ($Database);

# include Net_DNS2
ini_set("include_path", dirname(__FILE__)."/../assets/Net_DNS2");
require_once(dirname(__FILE__)."/../assets/Net_DNS2/Net/DNS2.php");

# script can only be run from cli
if(php_sapi_name()!="cli") {
	$Common->errors[] = "This script can only be run from cli!";
	$Common->result_die ();
}

# save tenant id
$tenant_id = $j->t_id;

# get all zones to update
try {
	// fetch all zones
	$axfr_zones = $Database->getObjectsQuery("select *,id as zone_id from `zones` where type = 'axfr' and t_id =  ? ", [$tenant_id]);

	// if some are found execute
	if (sizeof($axfr_zones)>0) {

		// loop through hosts and fetch certificate
		foreach ($axfr_zones as $zone) {

			// init class
			$AXFR = new AXFR ($Database);
			// set dns, tcp and tsig parameters
			$AXFR->set_nameservers (explode(",",$zone->dns));					// set anmeservers to query
			$AXFR->set_tsig ($zone->tsig_name, $zone->tsig);					// set tsig parameters
			$AXFR->set_zone_name ($zone->aname);								// set zone name to query
			$AXFR->set_valid_types (explode(",", $zone->record_types));			// set valid dns record types
			$AXFR->set_regexes ($zone->regex_include, $zone->regex_exclude);	// set regexes

			// execute
			$AXFR->execute();

			// get result
			$results = $AXFR->get_records ();

			// calculate differences [create, remove, new etc]
			$AXFR->calculate_diffs ($zone->zone_id, $zone->check_ip);

			// add records
			$AXFR->create_new_records ();

			// remove records not in DNS AXFR
			if ($zone->delete_records=="1") {
				$AXFR->delete_records ();
			}
			else {
				$AXFR->records['removed_records'] = [];
			}

			//
			// send mail with changes !
			//
			if( (sizeof($AXFR->records['removed_records'])>0 && $zone->delete_records=="1") || sizeof($AXFR->records['new_records'])>0 ) {
				// init mailer
				$Mail = new mailer ();
				// mail
				global $mail_sender_settings;

				// tenant
				$tenant = $Database->getObject("tenants", $tenant_id);

				// mail content
				$content = [];

				// items to main
				$items = [];

				// created records
				if(sizeof($AXFR->records['new_records'])>0) {
					$items['New DNS records'] = $AXFR->records['new_records'];
				}
				// removed records
				if(sizeof($AXFR->records['removed_records'])>0 && $zone->delete_records=="1") {
					$items['Removed DNS records'] = $AXFR->records['removed_records'];
				}

				// loop and save
				foreach ($items as $type=>$items1) {
					$content[] = $Mail->font_title._($type)." for zone ".$zone->name."</font>:<br><br>";
					foreach ($items1 as $item) {
						$content[] = $Mail->font_norm.$item."</font><br>";
					}
					$content[] = "<br><hr style='border-bottom:none;border-top:1px solid #ccc;'>";
				}

				// footer
				$content[] = "<br><br>".$Mail->font_norm."Visit <a href='".$mail_sender_settings->www."' style='color:#003551;'>".$mail_sender_settings->www."</a></font>";

				// private zone: only notify the creator, not tenant recipients
				if (!empty($zone->private_zone_uid)) {
					$creator = $Database->getObject("users", $zone->private_zone_uid);
					if ($creator && filter_var($creator->email, FILTER_VALIDATE_EMAIL)) {
						$Mail->send ("Telemach php-ssl :: DNS changed hosts [".$zone->name."]", [$creator->email], [], [], implode("\n", $content), false);
					}
				}
				else {
					// recipients
					$to = array_values(array_filter(
						array_map('trim', explode(";", str_replace(",", ";", $tenant->recipients))),
						fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));

					// send
					$Mail->send ("Telemach php-ssl :: DNS changed hosts [".$tenant->name."]", $to, [], [], implode("\n", $content), false);
				}
			}

		}
	}
} catch (Net_DNS2_Exception $e) {
    // print error
	$Common->errors[] = $e->getMessage();
	$Common->result_die ();
} catch (Exception $e) {
    // print error
	$Common->errors[] = $e->getMessage();
	$Common->result_die ();
}

<?php

/**
 *
 * Update certificates - cronjob
 *
 * $j object is passed to scrip :: $j->t_id is tenant id
 *
 */

# load classes
$Result   = new Result ();
$Common   = new Common ();
// $URL      = new URL ();
$Database = new Database_PDO ();
$Config   = new Config ($Database);

# script can only be run from cli
if(php_sapi_name()!="cli") {
	$Common->errors[] = "This script can only be run from cli!";
	$Common->result_die ();
}
# threading check
if(!ScanThread::available($errmsg)) {
	$Common->errors[] = "Threading is required for scanning certificatres - Error: $errmsg\n";
	$Common->result_die ();
}

# save tenant id
$tenant_id = $j->t_id;

// get conf
$config = $Config->get_config($j->t_id);

#
# execute
#
try {
    // fetch hosts (including private_zone_uid so we can route notifications correctly)
	$hosts = $Database->getObjectsQuery("select *,h.id as host_id,z.t_id as t_id,z.private_zone_uid as private_zone_uid from `hosts` as h, `zones` as z, agents as a where h.`z_id` = z.id and z.agent_id = a.id and z.`t_id` = ? and h.ignore = 0", [$tenant_id]);

	// if some are found execute
	if (sizeof($hosts)>0) {

		$z = 0;

		for ($m=0; $m<=sizeof($hosts); $m += $config['scanMaxThreads']) {
		    // create threads
		    $threads = [];
		    //fork processes
		    for ($i = 0; $i <= 64 && $i <= sizeof($hosts); $i++) {
		    	//only if index exists!
		    	if(isset($hosts[$z])) {
					//start new thread
		            $threads[$i] = new ScanThread( 'scan_host' );
		            $threads[$i]->start($hosts[$z], $execution_time, $tenant_id);
		            $z++;				//next index
				}
		    }
		    // wait for all the threads to finish
		    while( !empty( $threads ) ) {
		        foreach( $threads as $index => $thread ) {
		            if( ! $thread->isAlive() ) {
		                //remove thread
		                unset( $threads[$index] );
		            }
		        }
		        usleep(200000);
		    }
		}

		// reinit database
		unset($Database);
		$Database = new Database_PDO ();

		// get changed based on execution time !
        $changed_hosts = $Database->getObjectsQuery("select *,h.id as id,a.name as agname,z.t_id as t_id,z.name as zone_name from zones as z,hosts as h, certificates as c, agents as a where h.z_id = z.id and h.c_id = c.id and z.agent_id = a.id and h.last_change = ? and h.mute = 0 and z.t_id = ?", [$execution_time, $tenant_id]);

		// mail diff
		if(sizeof($changed_hosts)>0) {

			// all users
			$User = new User ($Database);
			$all_users = $User->get_all ("email");

			// processed flag - we need it in case issuer is ignored !
			$processed = 0;

			// fake user
			$cron_user = new stdClass ();
			$cron_user->t_id = $tenant_id;

    		// init certs
    		$Certificates = new Certificates ($Database, $cron_user);
    		$Certificates->get_all_ignored_issuers ($tenant_id);			// get all ignored issuers for this tenant

			// init mailer
			$Mail = new mailer ();

            // mail
            global $mail_sender_settings;

			// tenant
			$tenant = $Database->getObject("tenants", $tenant_id);

			// tenant recipients
			$email_to_tenant_recipents = array_values(array_filter(
				array_map('trim', explode(";", str_replace(",", ";", $tenant->recipients))),
				fn($e) => $Common->validate_mail($e)));

			$use_list = ($tenant->mail_style == "list");

			// headers
			$header_list = [
				$Mail->font_title._("Changed host certificates")."</font>:<br><br>",
				"<table border='0' cellpadding='3' cellspacing='0'>",
			];
			$header_table = [
				$Mail->font_title._("Changed host certificates")."</font>:<br><br>",
				"<table border='0' cellpadding='3' cellspacing='0'>",
				"<thead>",
				"  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Hostname")." / "._("IP")."</font></th>",
				"  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Zone")."</font></th>",
				"  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Certificate")."</font></th>",
				"  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Serial")." / "._("Issuer")."</font></th>",
				"  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Valid to")."</font></th>",
				"  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Expires")."</font></th>",
				"</thead>",
				"<tbody>",
			];

			// initialize per-recipient content: $content[$email] = [rows]
			$content = [];
			foreach ($email_to_tenant_recipents as $email) {
				$content[$email] = $use_list ? $header_list : $header_table;
			}

			// track private zone creator emails so we don't BCC tenant recipients to their notifications
			$private_zone_emails = [];

			foreach ($changed_hosts as $c) {

            	// parse cert
            	$cert_parsed = $Certificates->parse_cert ($c->certificate);

            	// status
            	$status_int = $Certificates->get_status_int ($cert_parsed, true, $c->hostname);

            	if     ($status_int == "1")  { $status = "Expired";         $color = "#E74C3C"; }
            	elseif ($status_int == "2")  { $status = "Expires soon";    $color = "#FF5733"; }
            	elseif ($status_int == "10") { $status = "Domain mismatch"; $color = "#FF5733"; }
            	else                         { $status = "Valid";            $color = "#1ABC9C"; }

            	// check if cert is ignored, if so skip to next item !
            	if ($Certificates->is_issuer_ignored (str_replace("keyid:", "", $cert_parsed['extensions']['authorityKeyIdentifier']), $tenant_id)===false) {

	            	// try to prevent linkable text
					$c->hostname                                 = $Mail->prevent_linkable_text($c->hostname);
					$cert_parsed['subject']['CN']                = $Mail->prevent_linkable_text($cert_parsed['subject']['CN']);
					$cert_parsed['extensions']['subjectAltName'] = $Mail->prevent_linkable_text($cert_parsed['extensions']['subjectAltName']);
					$cert_parsed['issuer']['O']                  = $Mail->prevent_linkable_text($cert_parsed['issuer']['O']);
					$zone_name                                   = $Mail->prevent_linkable_text($c->zone_name ?? "/");

	                $td_style_title = "vertical-align:top;padding:1px 5px;white-space:nowrap;padding-left:0px;padding-bottom: 7px;padding-top:20px;";
	                $td_style       = "border-left:1px solid #ddd;vertical-align:top;padding:1px 5px;white-space:nowrap;padding-left:10px;";

	                // table-style rows
	                $cn_display = is_array($cert_parsed['subject']['CN']) ? implode("<br>", $cert_parsed['subject']['CN']) : $cert_parsed['subject']['CN'];
	                $table_rows = [
	                	"<tr>",
	                	"  <td style='border-bottom:1px solid #ddd;vertical-align:top;'>".$Mail->font_bold.$c->hostname."<br>".$Mail->font_ligh.$c->ip."</font></td>",
	                	"  <td style='border-bottom:1px solid #ddd;vertical-align:top;'>".$Mail->font_norm.$zone_name."</font></td>",
	                	"  <td style='border-bottom:1px solid #ddd;vertical-align:top;'>".$Mail->font_norm.$cn_display."</font></td>",
	                	"  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;'><strong><a href='https://".$mail_sender_settings->url."/".$tenant->href."/certificates/".$cert_parsed['serialNumber']."/'>".$Mail->font_bold.$cert_parsed['serialNumberHex']."</strong></font></a><br>".$Mail->font_norm.$cert_parsed['issuer']['O']."</font></td>",
	                	"  <td style='border-bottom:1px solid #ddd;vertical-align:top;'>".$Mail->font_norm.str_replace(" ","<br>",$cert_parsed['custom_validTo'])."</font></td>",
	                	"  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;'>".$Mail->font_norm."<div style='border-radius:4px;margin-top:5px;padding:2px 6px;border:1px solid #1ABC9C;background:rgba(26,188,156,0.1);text-align: center;white-space:nowrap;'>".$cert_parsed['custom_validDays']." "._("days")."</div></font></td>",
	                	"</tr>",
	                ];

	                // list-style rows
	                $list_rows = [
		                "<tr><td style='$td_style_title'>".$Mail->font_bold.$c->hostname."</font></td></tr>",
		                "<tr><td style='$td_style'>".$Mail->font_norm._("IP").": ".$c->ip."</font></td></tr>",
		                "<tr><td style='$td_style'>".$Mail->font_norm._("Zone").": ".$zone_name."</font></td></tr>",
		                "<tr><td style='$td_style'>".$Mail->font_norm._("Subject").":</font> ".$Mail->font_bold.$cert_parsed['subject']['CN']."</font></td></tr>",
		                "<tr><td style='$td_style'>".$Mail->font_norm._("Status").": <span style='color:$color;padding:0px;margin:0px;'>".$status." (".$cert_parsed['custom_validDays']." "._("days").")</span></font></td></tr>",
		                "<tr><td style='$td_style'>".$Mail->font_norm._("Issuer").": ".$cert_parsed['issuer']['O']."</font></td></tr>",
		                "<tr><td style='$td_style'>".$Mail->font_norm._("Serial").": <a href='https://".$mail_sender_settings->url."/".$tenant->href."/certificates/".$cert_parsed['serialNumber']."/' style='text-decoration:none;color:#333'>".$cert_parsed['serialNumberHex']."</a></font></td></tr>",
		                "<tr><td style='$td_style'>".$Mail->font_norm._("Expires").": ".$cert_parsed['custom_validTo']."</font></td></tr>",
		                "<tr><td style='$td_style'>".$Mail->font_norm._("Scan agent").": ".$c->agname."</font></td></tr>",
	                ];
	                if (strlen($cert_parsed['extensions']['subjectAltName'])>0)
	                $list_rows[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Altnames").":<br><span style='padding:2px;padding-left:15px;'>".str_replace(",","</span><br><span style='padding:0px;padding:2px;padding-left:15px;'>",$cert_parsed['extensions']['subjectAltName'])."</span></font></td></tr>";

	                // private zone: only notify the zone creator, not tenant recipients
	                if (!empty($c->private_zone_uid)) {
	                	$creator = $Database->getObject("users", $c->private_zone_uid);
	                	if ($creator && $Common->validate_mail($creator->email)) {
	                		if (!isset($content[$creator->email])) { $content[$creator->email] = $header_list; }
	                		array_push($content[$creator->email], ...$list_rows);
	                		$private_zone_emails[$creator->email] = true;
	                	}
	                }
	                else {
		                // add to tenant recipients
		                $host_rows = $use_list ? $list_rows : $table_rows;
		                foreach ($email_to_tenant_recipents as $email) {
		                	array_push($content[$email], ...$host_rows);
		                }

		                // add to per-host recipients (always list style)
		                foreach (explode(";", $c->h_recipients) as $r) {
		                    $r = trim($r);
		                    if ($Common->validate_mail($r)) {
		                        if (!isset($content[$r])) { $content[$r] = $header_list; }
		                        array_push($content[$r], ...$list_rows);
		                    }
		                }
	                }

                	$processed++;
            	}
	        }

	        // added for ignored certs check
	        if ($processed>0) {

	        	// close tables + footer
				foreach ($content as $email => &$rows) {
					if (!$use_list && in_array($email, $email_to_tenant_recipents)) { $rows[] = "</tbody>"; }
					$rows[] = "</table>";
					$rows[] = "<br><br>".$Mail->font_norm."Visit <a href='".$mail_sender_settings->www."' style='color:#003551;'>".$mail_sender_settings->www."</a></font>";
				}
				unset($rows);

				// send to tenant recipients together
				$Mail->send ("Telemach php-ssl :: changed certificates [".$tenant->name."]", $email_to_tenant_recipents, [], [], implode("\n", $content[$email_to_tenant_recipents[0]]), false);

		        // Log
		        $Log = new Log ($Database);
		        // Log
		        $Log->write ("users", NULL, $tenant->id, null, "notification", true, "Certificate change notification email sent to all tenant admins for certificate change", json_encode($email_to_tenant_recipents), json_encode(["title"=>"Telemach php-ssl :: changed certificates [".$tenant->name."]", "data"=>$content[$email_to_tenant_recipents[0]]]), false);

				// send to per-host recipients individually; private zone creators get no BCC to tenant recipients
		        foreach ($content as $email => $rows) {
		        	if (!in_array($email, $email_to_tenant_recipents)) {
		        		$bcc = isset($private_zone_emails[$email]) ? [] : $email_to_tenant_recipents;
		                $Mail->send ("Telemach php-ssl :: changed certificates", [$email], [], $bcc, implode("\n", $rows), false);
		                // Log
		                $Log->write ("users", $all_users[$email]->id ?? null, $tenant->id, null, "notification", true, "Certificate change notification email sent to user ".(isset($all_users[$email]) ? $all_users[$email]->name : $email)." (".$email.")", json_encode([$email]), json_encode(["title"=>"Telemach php-ssl :: changed certificates", "data"=>$rows]), false);
		        	}
		        }
	    	}
		}
	}
} catch (Exception $e) {
    // print error
	$Common->errors[] = $e->getMessage();
	$Common->show_cli ($Common->get_last_error());
}
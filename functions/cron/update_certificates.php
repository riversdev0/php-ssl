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
$Log 	  = new Log ($Database);

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
    // fetch hosts
	$hosts = $Database->getObjectsQuery("select *,h.id as host_id,z.t_id as t_id from `hosts` as h, `zones` as z, agents as a where h.`z_id` = z.id and z.agent_id = a.id and z.`t_id` = ? and h.ignore = 0", [$tenant_id]);

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
        $changed_hosts = $Database->getObjectsQuery("select *,h.id as id,a.name as agname,z.t_id as t_id from zones as z,hosts as h, certificates as c, agents as a where h.z_id = z.id and h.c_id = c.id and z.agent_id = a.id and h.last_change = ? and h.mute = 0 and z.t_id = ?", [$execution_time, $tenant_id]);

		// mail diff
		if(sizeof($changed_hosts)>0) {
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

			//
			// content - table
			//
			$content = [];

            $content[] = $Mail->font_title._("Changed host certificates")."</font>:<br><br>";
            $content[] = "<table border='0' cellpadding='3' cellspacing='0'>";
            $content[] = "<thead>";
            $content[] = "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Hostname")." / "._("IP")."</font></th>";
            $content[] = "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Certificate")."</font></th>";
            $content[] = "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Serial")." / "._("Issuer")."</font></th>";
            $content[] = "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Valid to")."</font></th>";
            $content[] = "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Expires")."</font></th>";
            $content[] = "</thead>";

            $content[] = "<tbody>";

			foreach ($changed_hosts as $c) {

            	// parse cert
            	$cert_parsed = $Certificates->parse_cert ($c->certificate);

				// CN - array ?
				if(is_array($cert_parsed['subject']['CN'])) {
					$cert_parsed['subject']['CN'] = implode("<br>", $cert_parsed['subject']['CN']);
				}

            	// content
	            $content[] = "<tr>";
	            $content[] = "  <td style='border-bottom:1px solid #ddd;vertical-align:top;'>".$Mail->font_bold.$c->hostname."<br>".$Mail->font_ligh.$c->ip."</font></td>";
	            $content[] = "  <td style='border-bottom:1px solid #ddd;vertical-align:top;'>".$Mail->font_norm.$cert_parsed['subject']['CN']."</font></td>";
                $content[] = "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;'><strong><a href='https://".$mail_sender_settings->url."/".$tenant->href."/certificates/".$cert_parsed['serialNumber']."/'>".$Mail->font_bold.$cert_parsed['serialNumberHex']."</strong></font></a><br>".$Mail->font_norm.$cert_parsed['issuer']['O']."</font></td>";
                $content[] = "  <td style='border-bottom:1px solid #ddd;vertical-align:top;'>".$Mail->font_norm.str_replace(" ","<br>",$cert_parsed['custom_validTo'])."</font></td>";
                $content[] = "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;'>".$Mail->font_norm."<div style='border-radius:4px;margin-top:5px;padding:2px 6px;border:1px solid #1ABC9C;background:rgba(26,188,156,0.1);text-align: center;white-space:nowrap;'>".$cert_parsed['custom_validDays']." "._("days")."</div></font></td>";
	            $content[] = "</tr>";
	        }

			$content[] = "</tbody>";
			$content[] = "</table>";


            //
            // style 2 - item per line
            //
            $content2 = [];


        	// separate recipients notification
	        $content_h_recipients_mails = [];
	        $h_recipients_mails         = [];


           	$content2[] = $Mail->font_title._("Changed host certificates")."</font>:<br><br>";
            $content2[] = "<table border='0' cellpadding='3' cellspacing='0'>";

            // save initial
            $content_h_recipients_mails = $content2;

			foreach ($changed_hosts as $c) {

            	// parse cert
            	$cert_parsed = $Certificates->parse_cert ($c->certificate);

            	// status
            	$status_int = $Certificates->get_status_int ($cert_parsed, true, $c->hostname);

            	// status
            	if ($status_int == "1") {
            		$status = "Expired";
            		$color  = "#E74C3C";
            	}
            	elseif ($status_int == "2") {
            		$status = "Expires soon";
            		$color  = "#FF5733";
            	}
            	elseif ($status_int == "10") {
            		$status = "Domain mismatch";
            		$color  = "#FF5733";
            	}
            	else {
            		$status = "Valid";
            		$color  = "#1ABC9C";
            	}

            	// check if cert is ignored, if so skip to next item !
            	if ($Certificates->is_issuer_ignored (str_replace("keyid:", "", $cert_parsed['extensions']['authorityKeyIdentifier']), $tenant_id)===false) {

	            	// try to prevent linkable text
					$c->hostname                                 = $Mail->prevent_linkable_text($c->hostname);
					$cert_parsed['subject']['CN']                = $Mail->prevent_linkable_text($cert_parsed['subject']['CN']);
					$cert_parsed['extensions']['subjectAltName'] = $Mail->prevent_linkable_text($cert_parsed['extensions']['subjectAltName']);
					$cert_parsed['issuer']['O']                  = $Mail->prevent_linkable_text($cert_parsed['issuer']['O']);

	                $td_style_title = "vertical-align:top;padding:1px 5px;white-space:nowrap;padding-left:0px;padding-bottom: 7px;padding-top:20px;";
	                $td_style = "border-left:1px solid #ddd;vertical-align:top;padding:1px 5px;white-space:nowrap;padding-left:10px;";

	            	// content
	                $content2[] = "<tr><td style='$td_style_title'>".$Mail->font_bold.$c->hostname."</font></td></tr>";
	                $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("IP").": ".$c->ip."</font></td></tr>";
	                $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Subject").":</font> ".$Mail->font_bold.$cert_parsed['subject']['CN']."</font></td></tr>";
	                $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Status").": <span style='color:$color;padding:0px;margin:0px;'>".$status." (".$cert_parsed['custom_validDays']." "._("days").")</span></font></td></tr>";
	                $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Issuer").": ".$cert_parsed['issuer']['O']."</font></td></tr>";
	                $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Serial").": <a href='https://".$mail_sender_settings->url."/".$tenant->href."/certificates/".$cert_parsed['serialNumber']."/' style='text-decoration:none;color:#333'>".$cert_parsed['serialNumberHex']."</a></font></td></tr>";
	                $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Expires").": ".$cert_parsed['custom_validTo']."</font></td></tr>";
	                $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Scan agent").": ".$c->agname."</font></td></tr>";
	                if(strlen($cert_parsed['extensions']['subjectAltName'])>0)
	                $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Altnames").":<br><span style='padding:2px;padding-left:15px;'>".str_replace(",","</span><br><span style='padding:0px;padding:2px;padding-left:15px;'>",$cert_parsed['extensions']['subjectAltName'])."</span></font></td></tr>";

	                // content - extra recipients
	                foreach (explode(";", $c->h_recipients) as $r) {
	                    if($Common->validate_mail($r)) {
	                        // add start of mail
	                        if(!array_key_exists($r, $h_recipients_mails)) { $h_recipients_mails[$r] = $content_h_recipients_mails; }
	                        // save content for user
			                $h_recipients_mails[$r][] = "<tr><td style='$td_style_title'>".$Mail->font_bold.$c->hostname."</font></td></tr>";
			                $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("IP").": ".$c->ip."</font></td></tr>";
			                $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("Subject").":</font> ".$Mail->font_bold.$cert_parsed['subject']['CN']."</font></td></tr>";
			                $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("Status").": <span style='color:$color;padding:0px;margin:0px;'>".$status." (".$cert_parsed['custom_validDays']." "._("days").")</span></font></td></tr>";
			                $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("Issuer").": ".$cert_parsed['issuer']['O']."</font></td></tr>";
			                $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("Serial").": <a href='https://".$mail_sender_settings->url."/".$tenant->href."/certificates/".$cert_parsed['serialNumber']."/' style='text-decoration:none;color:#333'>".$cert_parsed['serialNumberHex']."</a></font></td></tr>";
			                $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("Expires").": ".$cert_parsed['custom_validTo']."</font></td></tr>";
			                if(strlen($cert_parsed['extensions']['subjectAltName'])>0)
			                $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("Altnames").":<br><span style='padding:2px;padding-left:15px;'>".str_replace(",","</span><br><span style='padding:0px;padding:2px;padding-left:15px;'>",$cert_parsed['extensions']['subjectAltName'])."</span></font></td></tr>";
	                    }
	                }                	// ignored no more
                	$processed++;
            	}
	        }
			$content2[] = "</table>";

	        // end table for extra recepients
	        if(sizeof($h_recipients_mails)>0) {
	            foreach ($h_recipients_mails as $email => $items) {
	                $h_recipients_mails[$email][] = "</table>";
	            }
	        }


	        // added for ignored certs check
	        if ($processed>0) {
				// recipients
				$to = explode(";", str_replace(",",";",$tenant->recipients));
				// $to = ["miha.petkovsek@telemach.si"];

	            // set proper content
	            $selected_content = $tenant->mail_style=="list" ? $content2 : $content;

				// send
				$Mail->send ("Telemach php-ssl :: changed certificates [".$tenant->name."]", $to, [], [], implode("\n", $selected_content), false);

		        // send to extra recepients
		        if(sizeof($h_recipients_mails)) {
		            foreach ($h_recipients_mails as $extra_mail=>$extra_content) {
		                // send
		                $Mail->send ("Telemach php-ssl :: changed certificates [".$tenant->name."]", [$extra_mail], $to, [], implode("\n", $extra_content), false);
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
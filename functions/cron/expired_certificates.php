<?php

/**
 *
 * Expired certificates - cronjob
 *
 * $j object is passed to scrip :: $j->t_id is tenant id
 *
 */

# load classes
$Result       = new Result ();
$Common       = new Common ();
$URL          = new URL ();
$Database     = new Database_PDO ();


# script can only be run from cli
if(php_sapi_name()!="cli") {
    $Common->errors[] = "This script can only be run from cli!";
    $Common->result_die ();
}

# save tenant id
$tenant_id = $j->t_id;

# fake user
$cron_user = new stdClass ();
$cron_user->t_id = $tenant_id;

#
# execute
#
try {
    // init certs
    $Certificates = new Certificates ($Database, $cron_user);

    // get config for $expired_days and $expired_after_days
    include(dirname(__FILE__)."/../../config.php");

    // fetch all certificates that will expire
    $expired_certificates = $Certificates->get_expired ($expired_days, $expired_after_days);

    // mail diff
    if(sizeof($expired_certificates)>0) {

        // new array for expired and expire soon
        $all_expired_certs = [
                        "expire_soon" =>[],
                        "expired"     =>[]
                        ];

        // save to new array
        foreach ($expired_certificates as $c) {
            if(date("Y-m-d H:i:s")>$c->expires) {
                $all_expired_certs["expired"][] = $c;
            }
            else {
                $all_expired_certs["expire_soon"][] = $c;
            }
        }

        // reverse second array
        $all_expired_certs["expired"] = array_reverse($all_expired_certs["expired"]);

        // init mailer
        $Mail = new mailer ();
        // mail
        global $mail_sender_settings;

        // tenant
        $tenant = $Database->getObject("tenants", $tenant_id);

        //
        // style1 = table
        //
        $content = [];

        // table
        $content[] = "<table border='0' cellpadding='3' cellspacing='0'>";

        // loop
        foreach ($all_expired_certs as $type=>$certificates) {
            // only show if some are present
            if(sizeof($certificates)>0) {

                // title
                $title    = $type=="expired" ? "Expired certificates" : "Certificates that will expire soon";
                $color    = $type=="expired" ? "#E74C3C" : "#FF5733";
                $color_bg = $type=="expired" ? "rgba(255,87,51,0.1)" : "rgba(248,196,113,0.1)";

                $content[] = "<tr>";
                $content[] = "  <td colspan='5' style='padding-top:20px;padding-bottom: 20px;'>".$Mail->font_title._($title)."</font></td>";
                $content[] = "</tr>";

                $content[] = "<tr>";
                $content[] = "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Certificate")."</font></th>";
                $content[] = "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Serial")." / "._("Issuer")."</font></th>";
                $content[] = "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Valid untill")."</font></th>";
                $content[] = "  <th style='border-bottom:2px solid #003551; text-align:center'>".$Mail->font_norm._("Expires")."</font></th>";
                $content[] = "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Hosts")."</font></th>";
                $content[] = "</tr>";

                foreach ($certificates as $c) {

                    // parse cert
                    $cert_parsed = $Certificates->parse_cert ($c->certificate);

                    // CN - array ?
                    if(is_array($cert_parsed['subject']['CN'])) {
                        $cert_parsed['subject']['CN'] = implode("<br>", $cert_parsed['subject']['CN']);
                    }

                    // get hosts
                    $hosts = $Certificates->get_certificate_hosts ($c->id);
                    $all_hosts = [];
                    if(sizeof($hosts)>0) {
                        foreach ($hosts as $h) {
                            $h->ip = $Certificates->validate_ip ($h->hostname) ? $h->hostname : $h->ip;
                            if($Certificates->validate_ip ($h->hostname)) {
                                $all_hosts[] = $h->hostname;
                            }
                            else {
                                $all_hosts[] = $h->hostname." [".$h->ip."]";
                            }
                        }
                    }
                    else {
                        $all_hosts[] = "/";
                    }

                    $content[] = "<tr>";
                    $content[] = "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;white-space:nowrap;padding-left: 5px;'>".$Mail->font_bold.$cert_parsed['subject']['CN']."</font></td>";
                    $content[] = "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;'><strong><a href='https://".$mail_sender_settings->url."/".$tenant->href."/certificates/".$cert_parsed['serialNumber']."/'>".$Mail->font_bold.$cert_parsed['serialNumberHex']."</strong></font></a><br>".$Mail->font_norm.$cert_parsed['issuer']['O']."</font></td>";
                    $content[] = "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;white-space:nowrap;padding-left: 5px;'>".$Mail->font_norm.str_replace(" ", "<br>",$cert_parsed['custom_validTo'])."</font></td>";
                    $content[] = "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;'>".$Mail->font_norm."<div style='color:$color;border-radius:4px;margin-top:5px;padding:2px 6px;border:1px solid $color;background:$color_bg;text-align: center;white-space:nowrap;'>".$cert_parsed['custom_validDays']." "._("days")."</div></font></td>";
                    $content[] = "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;'>".$Mail->font_ligh.implode("<br>", $all_hosts)."</font></td>";

                    $content[] = "</tr>";
                }
            }
        }
        $content[] = "</table>";


        //
        // style 2 - item per line
        //
        $content2 = [];

        // separate recipients notification
        $content_h_recipients_mails = [];
        $h_recipients_mails         = [];

        // table
        $content2[] = "<table border='0' cellpadding='3' cellspacing='0'>";

        // loop
        $m=0;
        foreach ($all_expired_certs as $type=>$certificates) {
            // only show if some are present
            if(sizeof($certificates)>0) {
                // title
                $title    = $type=="expired" ? "Expired certificates" : "Certificates that will expire soon";
                $color    = $type=="expired" ? "#E74C3C" : "#FF5733";
                $color_bg = $type=="expired" ? "rgba(255,87,51,0.1)" : "rgba(248,196,113,0.1)";
                $status   = $type=="expired" ? "Expired" : "Expires soon";
                $padding  = $m==0 ? "0" : "40";

                $content2[] = "<tr>";
                $content2[] = "  <td style='padding-top:{$padding}px;padding-left:0px;'>".$Mail->font_title._($title).":</font></td>";
                $content2[] = "</tr>";

                // save initial
                $content_h_recipients_mails = $content2;

                // loop
                foreach ($certificates as $c) {
                    // parse cert
                    $cert_parsed = $Certificates->parse_cert ($c->certificate);
                    // get hosts
                    $hosts = $Certificates->get_certificate_hosts ($c->id);
                    $all_hosts = [];
                    if(sizeof($hosts)>0) {
                        foreach ($hosts as $h) {
                            $h->ip = $Certificates->validate_ip ($h->hostname) ? $h->hostname : $h->ip;
                            if($Certificates->validate_ip ($h->hostname)) {
                                $all_hosts[] = $h->hostname;
                            }
                            else {
                                $all_hosts[] = $h->hostname." [".$h->ip."]";
                            }
                        }
                    }
                    else {
                        $all_hosts[] = "/";
                    }

                    // try to prevent linkable text
                    $all_hosts                                   = $Mail->prevent_linkable_text($all_hosts);
                    $cert_parsed['subject']['CN']                = $Mail->prevent_linkable_text($cert_parsed['subject']['CN']);
                    $cert_parsed['extensions']['subjectAltName'] = $Mail->prevent_linkable_text($cert_parsed['extensions']['subjectAltName']);
                    $cert_parsed['issuer']['O']                  = $Mail->prevent_linkable_text($cert_parsed['issuer']['O']);

                    $td_style_title = "vertical-align:top;padding:1px 5px;white-space:nowrap;padding-left:0px;padding-bottom: 7px;padding-top:20px;";
                    $td_style = "border-left:1px solid #ddd;vertical-align:top;padding:1px 5px;white-space:nowrap;padding-left:10px;";

                    // content
                    $content2[] = "<tr><td style='$td_style_title'>".$Mail->font_bold.$cert_parsed['subject']['CN']."</font></td></tr>";
                    $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Subject").": ".$cert_parsed['subject']['CN']."</font></td></tr>";
                    $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Status").": <span style='color:$color;padding:0px;margin:0px;'>".$status." (".$cert_parsed['custom_validDays']." "._("days").")</span> </font></td></tr>";
                    $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Issuer").": ".$cert_parsed['issuer']['O']."</font></td></tr>";
                    $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Serial").": <a href='https://".$mail_sender_settings->url."/".$tenant->href."/certificates/".$cert_parsed['serialNumber']."/' style='text-decoration:none;color:#333'>".$cert_parsed['serialNumberHex']."</a></font></td></tr>";
                    $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Expires").": ".$cert_parsed['custom_validTo']."</font></td></tr>";
                    if(strlen($cert_parsed['extensions']['subjectAltName'])>0)
                    $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Altnames").":<br><span style='padding:2px;padding-left:15px;'>".str_replace(",","</span><br><span style='padding:2px;padding-left:15px;'>",$cert_parsed['extensions']['subjectAltName'])."</span></font></td></tr>";
                    $content2[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Hosts").":<br><span style='padding:2px;padding-left:15px;'>".implode("</span><br><span style='padding:2px;padding-left:15px;'>", $all_hosts)."</span></font></td></tr>";



                    // content - extra recipients
                    foreach (explode(";", $c->h_recipients) as $r) {
                        if($Common->validate_mail($r)) {
                            // add start of mail
                            if(!array_key_exists($r, $h_recipients_mails)) { $h_recipients_mails[$r] = $content_h_recipients_mails; }
                            // save content for user
                            $h_recipients_mails[$r][] = "<tr><td style='$td_style_title'>".$Mail->font_bold.$cert_parsed['subject']['CN']."</font></td></tr>";
                            $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("Subject").": ".$cert_parsed['subject']['CN']."</font></td></tr>";
                            $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("Status").": <span style='color:$color;padding:0px;margin:0px;'>".$status." (".$cert_parsed['custom_validDays']." "._("days").")</span> </font></td></tr>";
                            $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("Issuer").": ".$cert_parsed['issuer']['O']."</font></td></tr>";
                            $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("Serial").": <a href='https://".$mail_sender_settings->url."/".$tenant->href."/certificates/".$cert_parsed['serialNumber']."/' style='text-decoration:none;color:#333'>".$cert_parsed['serialNumberHex']."</a></font></td></tr>";
                            $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("Expires").": ".$cert_parsed['custom_validTo']."</font></td></tr>";
                            if(strlen($cert_parsed['extensions']['subjectAltName'])>0)
                            $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("Altnames").":<br><span style='padding:2px;padding-left:15px;'>".str_replace(",","</span><br><span style='padding:2px;padding-left:15px;'>",$cert_parsed['extensions']['subjectAltName'])."</span></font></td></tr>";
                            $h_recipients_mails[$r][] = "<tr><td style='$td_style'>".$Mail->font_norm._("Hosts").":<br><span style='padding:2px;padding-left:15px;'>".implode("</span><br><span style='padding:2px;padding-left:15px;'>", $all_hosts)."</span></font></td></tr>";
                        }
                    }

                }
                $m++;
            }
        }
        $content2[] = "</table>";

        // end table for extra recepients
        if(sizeof($h_recipients_mails)>0) {
            foreach ($h_recipients_mails as $email => $items) {
                $h_recipients_mails[$email][] = "</table>";
            }
        }

        // recipients
        $to = explode(",", $tenant->recipients);

        // set proper content
        $selected_content = $tenant->mail_style=="list" ? $content2 : $content;

        // send
        $Mail->send ("Telemach php-ssl :: certificate expiration [".$tenant->name."]", $to, [], [], implode("\n", $selected_content), false);

        // send to extra recepients
        if(sizeof($h_recipients_mails)) {
            foreach ($h_recipients_mails as $extra_mail=>$extra_content) {
                // send
                $Mail->send ("Telemach php-ssl :: certificate expiration [".$tenant->name."]", [$extra_mail], $to, [], implode("\n", $extra_content), false);
            }
        }
    }
} catch (Exception $e) {
    // print error
    $Common->errors[] = $e->getMessage();
    $Common->show_cli ($Common->get_last_error());
}
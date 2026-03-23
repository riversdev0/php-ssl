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

        // all users
        $User = new User ($Database);
        $all_users = $User->get_all ("email");

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

        // tenant recipients
        $email_to_tenant_recipents = array_values(array_filter(
            array_map('trim', explode(";", str_replace(",", ";", $tenant->recipients))),
            fn($e) => $Common->validate_mail($e)));

        $use_list = ($tenant->mail_style == "list");

        // headers
        $header_list = [
            "<table border='0' cellpadding='3' cellspacing='0'>",
        ];
        $header_table = [
            "<table border='0' cellpadding='3' cellspacing='0'>",
        ];

        // initialize per-recipient content: $content[$email] = [rows]
        $content = [];
        foreach ($email_to_tenant_recipents as $email) {
            $content[$email] = $use_list ? $header_list : $header_table;
        }

        // track private zone creator emails so we don't BCC tenant recipients to their notifications
        $private_zone_emails = [];

        $m = 0;
        foreach ($all_expired_certs as $type => $certificates) {
            if (sizeof($certificates) > 0) {

                $title    = $type == "expired" ? "Expired certificates" : "Certificates that will expire soon";
                $color    = $type == "expired" ? "#E74C3C" : "#FF5733";
                $color_bg = $type == "expired" ? "rgba(255,87,51,0.1)" : "rgba(248,196,113,0.1)";
                $status   = $type == "expired" ? "Expired" : "Expires soon";
                $padding  = $m == 0 ? "0" : "40";

                // table-style section header rows
                $table_section_rows = [
                    "<tr>",
                    "  <td colspan='6' style='padding-top:20px;padding-bottom: 20px;'>".$Mail->font_title._($title)."</font></td>",
                    "</tr>",
                    "<tr>",
                    "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Certificate")."</font></th>",
                    "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Serial")." / "._("Issuer")."</font></th>",
                    "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Zone")."</font></th>",
                    "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Valid untill")."</font></th>",
                    "  <th style='border-bottom:2px solid #003551; text-align:center'>".$Mail->font_norm._("Expires")."</font></th>",
                    "  <th style='border-bottom:2px solid #003551; text-align:left'>".$Mail->font_norm._("Hosts")."</font></th>",
                    "</tr>",
                ];

                // list-style section header rows
                $list_section_rows = [
                    "<tr>",
                    "  <td style='padding-top:{$padding}px;padding-left:0px;'>".$Mail->font_title._($title).":</font></td>",
                    "</tr>",
                ];

                // add section header to tenant recipients
                $section_rows = $use_list ? $list_section_rows : $table_section_rows;
                foreach ($email_to_tenant_recipents as $email) {
                    array_push($content[$email], ...$section_rows);
                }

                foreach ($certificates as $c) {
                    // parse cert
                    $cert_parsed = $Certificates->parse_cert ($c->certificate);

                    // CN - array ?
                    if (is_array($cert_parsed['subject']['CN'])) {
                        $cert_parsed['subject']['CN'] = implode("<br>", $cert_parsed['subject']['CN']);
                    }

                    // get hosts
                    $hosts = $Certificates->get_certificate_hosts ($c->id);
                    $all_hosts = [];
                    if (sizeof($hosts) > 0) {
                        foreach ($hosts as $h) {
                            $h->ip = $Certificates->validate_ip ($h->hostname) ? $h->hostname : $h->ip;
                            if ($Certificates->validate_ip ($h->hostname)) {
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
                    $all_hosts                                   = array_map([$Mail, 'prevent_linkable_text'], $all_hosts);
                    $cert_parsed['subject']['CN']                = $Mail->prevent_linkable_text($cert_parsed['subject']['CN']);
                    $cert_parsed['extensions']['subjectAltName'] = $Mail->prevent_linkable_text($cert_parsed['extensions']['subjectAltName']);
                    $cert_parsed['issuer']['O']                  = $Mail->prevent_linkable_text($cert_parsed['issuer']['O']);
                    $zone_name                                   = $Mail->prevent_linkable_text($c->zone_name ?? "/");

                    $td_style_title = "vertical-align:top;padding:1px 5px;white-space:nowrap;padding-left:0px;padding-bottom: 7px;padding-top:20px;";
                    $td_style       = "border-left:1px solid #ddd;vertical-align:top;padding:1px 5px;white-space:nowrap;padding-left:10px;";

                    // table-style rows
                    $table_rows = [
                        "<tr>",
                        "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;white-space:nowrap;padding-left: 5px;'>".$Mail->font_bold.$cert_parsed['subject']['CN']."</font></td>",
                        "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;'><strong><a href='https://".$mail_sender_settings->url."/".$tenant->href."/certificates/".$cert_parsed['serialNumber']."/'>".$Mail->font_bold.$cert_parsed['serialNumberHex']."</strong></font></a><br>".$Mail->font_norm.$cert_parsed['issuer']['O']."</font></td>",
                        "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;white-space:nowrap;padding-left: 5px;'>".$Mail->font_norm.$zone_name."</font></td>",
                        "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;white-space:nowrap;padding-left: 5px;'>".$Mail->font_norm.str_replace(" ", "<br>", $cert_parsed['custom_validTo'])."</font></td>",
                        "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;'>".$Mail->font_norm."<div style='color:$color;border-radius:4px;margin-top:5px;padding:2px 6px;border:1px solid $color;background:$color_bg;text-align: center;white-space:nowrap;'>".$cert_parsed['custom_validDays']." "._("days")."</div></font></td>",
                        "  <td style='border-bottom:1px solid #ddd;vertical-align:top;padding:2px 5px;'>".$Mail->font_ligh.implode("<br>", $all_hosts)."</font></td>",
                        "</tr>",
                    ];

                    // list-style rows
                    $list_rows = [
                        "<tr><td style='$td_style_title'>".$Mail->font_bold.$cert_parsed['subject']['CN']."</font></td></tr>",
                        "<tr><td style='$td_style'>".$Mail->font_norm._("Subject").": ".$cert_parsed['subject']['CN']."</font></td></tr>",
                        "<tr><td style='$td_style'>".$Mail->font_norm._("Zone").": ".$zone_name."</font></td></tr>",
                        "<tr><td style='$td_style'>".$Mail->font_norm._("Status").": <span style='color:$color;padding:0px;margin:0px;'>".$status." (".$cert_parsed['custom_validDays']." "._("days").")</span> </font></td></tr>",
                        "<tr><td style='$td_style'>".$Mail->font_norm._("Issuer").": ".$cert_parsed['issuer']['O']."</font></td></tr>",
                        "<tr><td style='$td_style'>".$Mail->font_norm._("Serial").": <a href='https://".$mail_sender_settings->url."/".$tenant->href."/certificates/".$cert_parsed['serialNumber']."/' style='text-decoration:none;color:#333'>".$cert_parsed['serialNumberHex']."</a></font></td></tr>",
                        "<tr><td style='$td_style'>".$Mail->font_norm._("Expires").": ".$cert_parsed['custom_validTo']."</font></td></tr>",
                        "<tr><td style='$td_style'>".$Mail->font_norm._("Hosts").":<br><span style='padding:2px;padding-left:15px;'>".implode("</span><br><span style='padding:2px;padding-left:15px;'>", $all_hosts)."</span></font></td></tr>",
                    ];
                    if (strlen($cert_parsed['extensions']['subjectAltName']) > 0)
                        $list_rows[] = "<tr><td style='$td_style'>".$Mail->font_norm._("Altnames").":<br><span style='padding:2px;padding-left:15px;'>".str_replace(",", "</span><br><span style='padding:2px;padding-left:15px;'>", $cert_parsed['extensions']['subjectAltName'])."</span></font></td></tr>";

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

                        // add to per-cert recipients (always list style)
                        foreach (explode(";", $c->h_recipients) as $r) {
                            $r = trim($r);
                            if ($Common->validate_mail($r)) {
                                if (!isset($content[$r])) { $content[$r] = $header_list; }
                                array_push($content[$r], ...$list_rows);
                            }
                        }
                    }
                }

                $m++;
            }
        }

        // close tables + footer
        foreach ($content as $email => &$rows) {
            $rows[] = "</table>";
            $rows[] = "<br><br>".$Mail->font_norm."Visit <a href='".$mail_sender_settings->www."' style='color:#003551;'>".$mail_sender_settings->www."</a></font>";
        }
        unset($rows);

        // send to tenant recipients together
        $Mail->send ("Telemach php-ssl :: certificate expiration [".$tenant->name."]", $email_to_tenant_recipents, [], [], implode("\n", $content[$email_to_tenant_recipents[0]]), false);

        // Log
        $Log = new Log ($Database);
        $Log->write ("users", NULL, $tenant->id, null, "notification", true, "Certificate expire notification email sent to all tenant admins", json_encode([$email]), json_encode(["title"=>"Telemach php-ssl :: certificate expiration [".$tenant->name."]", "data"=>implode("\n", $content[$email_to_tenant_recipents[0]])]), false);

        // send to per-cert recipients individually; private zone creators get no BCC to tenant recipients
        foreach ($content as $email => $rows) {
            if (!in_array($email, $email_to_tenant_recipents)) {
                $bcc = isset($private_zone_emails[$email]) ? [] : $email_to_tenant_recipents;
                $Mail->send ("Telemach php-ssl :: certificate expiration", [$email], [], $bcc, implode("\n", $rows), false);
                // Log
                $Log->write ("users", $all_users[$email]->id ?? null, $tenant->id, null, "notification", true, "Certificate expire notification email sent to user ".(isset($all_users[$email]) ? $all_users[$email]->name : $email)." (".$email.")", json_encode([$email]), json_encode(["title"=>"Telemach php-ssl :: certificate expiration", "data"=>$rows]), false);
            }
        }
    }
} catch (Exception $e) {
    // print error
    $Common->errors[] = $e->getMessage();
    $Common->show_cli ($Common->get_last_error());
}

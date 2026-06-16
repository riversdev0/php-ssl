<?php

/**
 * Nmap network scan runner — cronjob
 *
 * $j object is passed from cron.php :: $j->t_id is tenant id
 *
 * Picks up all scans with status = 'Requested' for the tenant,
 * marks each 'Scanning' immediately, then runs nmap.
 */

# load classes
$Result   = new Result();
$Common   = new Common();
$Database = new Database_PDO();

# cli only
if (php_sapi_name() !== 'cli') {
    $Common->errors[] = "This script can only be run from cli!";
    $Common->result_die();
}

$tenant_id = (int) $j->t_id;

$Nmap = new Nmap($Database);

if (!file_exists($Nmap->nmap_path) || !is_executable($Nmap->nmap_path)) {
    print "nmap_scan: nmap not found at {$Nmap->nmap_path} — skipping.\n";
    return;
}

$Nmap->run_pending($tenant_id);

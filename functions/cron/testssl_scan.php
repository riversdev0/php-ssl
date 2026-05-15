<?php

/**
 * testSSL scan runner — cronjob
 *
 * $j object is passed from cron.php :: $j->t_id is tenant id
 *
 * Picks up all scans with status = 'Requested' for the tenant,
 * marks each 'Scanning' immediately, then runs testssl.sh.
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

$tenant_id = (int)$j->t_id;

$TestSSL = new TestSSL($Database);

if (!file_exists($TestSSL->testssl_path)) {
    print "testssl_scan: testssl.sh not found at {$TestSSL->testssl_path} — skipping.\n";
    return;
}

$TestSSL->run_pending($tenant_id);

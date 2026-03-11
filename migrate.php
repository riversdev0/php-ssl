<?php

/**
 * Database migration CLI runner.
 *
 * Usage:
 *   php migrate.php          — show status and apply all pending migrations
 *   php migrate.php status   — show status only, do not apply
 *
 * Run after every git pull that includes new migration files.
 */

if (php_sapi_name() !== 'cli') {
	http_response_code(403);
	exit("This script must be run from the command line.\n");
}

require(__DIR__ . '/functions/autoload.php');

$command        = $argv[1] ?? 'apply';
$current        = $Migration->get_current_version();
$latest         = $Migration->get_latest_version();
$pending        = $Migration->get_pending();
$pending_count  = count($pending);

echo "DB version : {$current}\n";
echo "Latest     : {$latest}\n";
echo "Pending    : {$pending_count}\n";

if ($pending_count > 0) {
	echo "\nPending migrations:\n";
	foreach ($pending as $f) {
		echo "  - {$f}\n";
	}
}

if ($command === 'status' || $pending_count === 0) {
	echo ($pending_count === 0 ? "\nDatabase is up to date." : "\nRun without 'status' to apply.") . "\n";
	exit(0);
}

// Apply
echo "\nApplying {$pending_count} migration(s)...\n";

$results   = $Migration->apply_all();
$exit_code = 0;

foreach ($results as $r) {
	if ($r['success']) {
		echo "  [OK]   " . $r['file'] . "\n";
	} else {
		echo "  [FAIL] " . $r['file'] . " — " . $r['error'] . "\n";
		$exit_code = 1;
	}
}

$applied = count(array_filter($results, fn($r) => $r['success']));
$new_version = $Migration->get_current_version();
echo "\nDone. {$applied}/{$pending_count} applied. DB version now: {$new_version}\n";

exit($exit_code);

<?php

/**
 *
 * Database backup - cronjob (per-tenant)
 *
 * $j object is passed to script :: $j->t_id is tenant id
 *
 * Dumps only the rows belonging to this tenant into db/bkp/php-ssl-<tenant>-<date>.sql.
 * The resulting SQL file can be used to restore a single tenant's data without
 * affecting other tenants (uses DELETE + INSERT per table with FOREIGN_KEY_CHECKS=0).
 * Housekeeping removes backups older than $backup_retention_period days (default 30).
 *
 */

# load classes
$Result   = new Result ();
$Common   = new Common ();
$Database = new Database_PDO ();

# script can only be run from cli
if (php_sapi_name() != "cli") {
	$Common->errors[] = "This script can only be run from cli!";
	$Common->result_die ();
}

# save tenant id
$tenant_id = $j->t_id;

# get config (db credentials + optional $backup_retention_period)
include(dirname(__FILE__) . "/../../config.php");

# retention period in days
$retention_days = (isset($backup_retention_period) && is_numeric($backup_retention_period)) ? (int)$backup_retention_period : 30;

#
# execute
#
try {
	# get tenant
	$tenant = $Database->getObject("tenants", $tenant_id);
	if (!$tenant) {
		throw new Exception("Tenant not found: $tenant_id");
	}

	# backup directory
	$bkp_dir = realpath(dirname(__FILE__) . "/../../db") . "/bkp";
	if (!is_dir($bkp_dir)) {
		if (!mkdir($bkp_dir, 0750, true)) {
			throw new Exception("Failed to create backup directory: $bkp_dir");
		}
	}

	# backup filename
	$date     = date("YmdHis");
	$filename = $bkp_dir . "/php-ssl-" . $tenant->href . "-" . $date . ".sql";

	# direct PDO connection for the dump
	$dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8";
	$pdo = new PDO($dsn, $db['user'], $db['pass'], [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]);

	$tid = (int)$tenant_id;
	$fh  = fopen($filename, 'w');
	if (!$fh) {
		throw new Exception("Cannot open backup file for writing: $filename");
	}

	# helper: fetch rows and write INSERT for a single table
	$dump_table = function(string $table, string $where, array $params, bool $ignore = false) use ($pdo, $fh) {
		$keyword = $ignore ? "INSERT IGNORE" : "INSERT";

		fwrite($fh, "\n-- Table: `{$table}`\n");

		$stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE {$where}");
		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (empty($rows)) {
			fwrite($fh, "-- (no rows)\n");
			return;
		}

		$col_names   = '`' . implode('`, `', array_keys($rows[0])) . '`';
		$values_list = [];
		foreach ($rows as $row) {
			$vals          = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($row));
			$values_list[] = '(' . implode(', ', $vals) . ')';
		}
		fwrite($fh, "{$keyword} INTO `{$table}` ({$col_names}) VALUES\n" . implode(",\n", $values_list) . ";\n");
	};

	# --- header ---
	fwrite($fh, "-- php-ssl per-tenant backup\n");
	fwrite($fh, "-- Tenant : {$tenant->name} (id={$tid})\n");
	fwrite($fh, "-- Created: " . date('Y-m-d H:i:s') . "\n");
	fwrite($fh, "-- Database: {$db['name']}\n\n");
	fwrite($fh, "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n");
	fwrite($fh, "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n");

	# --- DELETEs ---
	# logs.object_t_id and cas.t_id have no FK cascade to tenants — delete explicitly
	# deleting the tenant row cascades all other related tables automatically
	fwrite($fh, "\n-- Delete tenant data\n");
	fwrite($fh, "DELETE FROM `logs` WHERE object_t_id = {$tid};\n");
	fwrite($fh, "DELETE FROM `cas`  WHERE t_id = {$tid};\n");
	fwrite($fh, "DELETE FROM `tenants` WHERE id = {$tid};\n");

	# --- INSERTs in FK dependency order (parent → child) ---
	fwrite($fh, "\n-- Inserts in FK order\n");
	# tenants (root)
	$dump_table('tenants',         "id = ?",                                    [$tid]);
	# users, domains, agents, port groups, config, cron (FK → tenants)
	$dump_table('users',           "t_id = ?",                                  [$tid]);
	$dump_table('domains',         "t_id = ?",                                  [$tid]);
	$dump_table('agents',          "t_id = ?",                                  [$tid]);
	$dump_table('ssl_port_groups', "t_id = ?",                                  [$tid]);
	$dump_table('config',          "t_id = ?",                                  [$tid]);
	$dump_table('cron',            "t_id = ?",                                  [$tid]);
	# zones (FK → tenants, agents, users)
	$dump_table('zones',           "t_id = ?",                                  [$tid]);
	# pkey is shared across tenants — INSERT IGNORE, no DELETE
	# covers pkey rows referenced by certificates, cas, and csrs
	$dump_table('pkey',
		"id IN (
			SELECT pkey_id FROM certificates WHERE t_id = ? AND pkey_id IS NOT NULL
			UNION
			SELECT pkey_id FROM cas  WHERE t_id = ? AND pkey_id IS NOT NULL
			UNION
			SELECT pkey_id FROM csrs WHERE t_id = ? AND pkey_id IS NOT NULL
		)",
		[$tid, $tid, $tid], true);
	# certificates (FK → tenants, zones, pkey)
	$dump_table('certificates',    "t_id = ?",                                  [$tid]);
	# hosts (FK → zones; no direct t_id)
	$dump_table('hosts',           "z_id IN (SELECT id FROM zones WHERE t_id = ?)", [$tid]);
	# cas — root CAs first (parent_ca_id IS NULL), then intermediates
	$dump_table('cas',             "t_id = ? ORDER BY (parent_ca_id IS NOT NULL) ASC, id ASC", [$tid]);
	# csr_templates (FK → tenants)
	$dump_table('csr_templates',   "t_id = ?",                                  [$tid]);
	# csrs (FK → tenants, pkey, certificates; self-ref renewed_by)
	$dump_table('csrs',            "t_id = ?",                                  [$tid]);
	# passkeys (FK → users ON DELETE CASCADE; no t_id — scope via users)
	$dump_table('passkeys',        "user_id IN (SELECT id FROM users WHERE t_id = ?)", [$tid]);
	# logs (object_t_id — no FK, but tenant-scoped)
	$dump_table('logs',            "object_t_id = ?",                           [$tid]);

	# --- footer ---
	fwrite($fh, "\n/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n");
	fwrite($fh, "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n");

	fclose($fh);

	# housekeeping: remove old backups for this tenant only
	$old_files = glob($bkp_dir . "/php-ssl-" . $tenant->href . "-*.sql");
	$cutoff    = strtotime("-{$retention_days} days");
	$removed   = 0;
	if ($old_files) {
		foreach ($old_files as $file) {
			if (filemtime($file) < $cutoff) {
				unlink($file);
				$removed++;
			}
		}
	}

	# log
	$Log = new Log ($Database);
	$msg = "Database backup created: " . basename($filename) . ($removed > 0 ? " ($removed old backup(s) removed)" : "");
	$Log->write("users", NULL, $tenant_id, null, "notification", false, $msg, NULL, NULL, false);

} catch (Exception $e) {
	$Common->errors[] = $e->getMessage();
	$Common->show_cli ($Common->get_last_error());
}

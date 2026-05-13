<?php

/**
 *
 * Class for common functions
 *
 */
class Common extends Validate
{

	/**
	 * Check if config exists
	 * @method config_exists
	 * @return bool
	 */
	public function config_exists()
	{
		return file_exists(dirname(__FILE__) . "/../../config.php") ? true : false;
	}

	/**
	 * Print system warning alerts below breadcrumbs.
	 * Checks:
	 *   - $installed is not set or false
	 *   - Logged-in user is using the default password (admin/admin)
	 *
	 * @method print_system_warnings
	 * @return void
	 */
	public function print_system_warnings(): void
	{
		global $installed, $user, $Migration;

		$warnings = [];

		// Check $installed flag
		if (!isset($installed) || $installed !== true) {
			$warnings[] = ['text' => 'Application is not marked as installed. Open <code>config.php</code> and set <code>$installed = true;</code>'];
		}

		// Check for default password (admin/admin — sha512 hash)
		if (isset($user->password) && $user->password === hash('sha512', 'admin')) {
			$warnings[] = ['text' => 'You are using the <strong>default password</strong>. Please change it immediately in <a href="/' . htmlspecialchars($user->href ?? '', ENT_QUOTES) . '/user/profile/">your profile</a>.'];
		}

		// Check for pending database migrations (admins only)
		if (isset($Migration) && isset($user->admin) && $user->admin === '1') {
			$pending = $Migration->get_pending();
			if (!empty($pending)) {
				$count   = count($pending);
				$current = $Migration->get_current_version();
				$latest  = $Migration->get_latest_version();
				$label   = "DB schema out of date (version {$current} → {$latest}), {$count} change(s) pending -";
				$btn     = "{$label} <button class='btn btn-sm btn-warning ms-2' id='btn-apply-migrations'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-settings"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg>'."Upgrade database</button>";
				$js      = "<script>document.getElementById('btn-apply-migrations').addEventListener('click',function(){this.disabled=true;this.textContent='Applying...';fetch('/route/ajax/apply-migrations.php',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(d=>{location.reload();}).catch(()=>{this.textContent='Error — check console';});});</script>";
				$warnings[] = ['text' => $btn . $js];
			}
		}

		if (empty($warnings)) {
			return;
		}

		$icon = "<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon alert-icon'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 9v4' /><path d='M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.871l-8.106 -13.534a1.914 1.914 0 0 0 -3.274 0z' /></svg>";

		foreach ($warnings as $warning) {
			print "<div class='alert alert-warning container-fluid mt-2 mb-0' role='alert'>";
			print $icon;
			print "<div>{$warning['text']}</div>";
			print "</div>\n";
		}
	}

	/**
	 * Install the database: create DB, create app user, grant privileges, import SCHEMA.sql.
	 * Checks that $installed === false before proceeding.
	 * On any error after the database is created, rolls back by dropping it.
	 *
	 * @method install_database
	 * @param  array $params  Keys: admin_user, admin_pass, db_host, db_port, db_name,
	 *                              app_user, app_pass, app_user_host, reinstall (bool)
	 * @return array  ['success' => bool, 'message' => string, 'log' => string[], 'errors' => string[]]
	 */
	public function install_database(array $params): array
	{
		global $installed;

		// Safety check: refuse to run if already installed
		if ($installed === true) {
			return ['success' => false, 'errors' => ['Installation refused: $installed is set to true in config.php. Set it to false to reinstall.'], 'log' => []];
		}

		$admin_user    = trim($params['admin_user'] ?? '');
		$admin_pass    = $params['admin_pass'] ?? '';
		$db_host       = trim($params['db_host'] ?? '127.0.0.1');
		$db_port       = (int)($params['db_port'] ?? 3306);
		$db_name       = trim($params['db_name'] ?? '');
		$app_user      = trim($params['app_user'] ?? '');
		$app_pass      = $params['app_pass'] ?? '';
		$app_user_host = trim($params['app_user_host'] ?? $db_host);
		$reinstall     = !empty($params['reinstall']);

		// Basic validation
		if ($admin_user === '') return ['success' => false, 'errors' => ['Admin username is required.'], 'log' => []];
		if ($db_name === '')    return ['success' => false, 'errors' => ['Database name is required.'], 'log' => []];
		if ($app_user === '')   return ['success' => false, 'errors' => ['Application username is required.'], 'log' => []];

		// Sanitize identifiers (only letters, digits, hyphens, underscores, dots are allowed)
		$safe_id = '/^[a-zA-Z0-9_\-\.]+$/';
		foreach (['db_name' => $db_name, 'app_user' => $app_user, 'app_user_host' => $app_user_host] as $field => $value) {
			if (!preg_match($safe_id, $value)) {
				return ['success' => false, 'errors' => ["Invalid characters in field: {$field}"], 'log' => []];
			}
		}

		$schema_path = dirname(__FILE__) . '/../../db/SCHEMA.sql';
		if (!file_exists($schema_path)) {
			return ['success' => false, 'errors' => ['db/SCHEMA.sql not found.'], 'log' => []];
		}

		// Connect as admin (no specific database selected)
		try {
			$dsn = "mysql:host={$db_host};port={$db_port};charset=utf8";
			$pdo = new PDO($dsn, $admin_user, $admin_pass, [
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			]);
		} catch (PDOException $e) {
			return ['success' => false, 'errors' => ['Failed to connect to MySQL: ' . $e->getMessage()], 'log' => []];
		}

		$db_created = false;
		$log        = [];

		try {
			// Drop database if reinstall requested
			if ($reinstall) {
				$pdo->exec("DROP DATABASE IF EXISTS `{$db_name}`");
				$log[] = "Dropped existing database `{$db_name}`.";
			}

			// Create database
			$pdo->exec("CREATE DATABASE `{$db_name}` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
			$db_created = true;
			$log[] = "Created database `{$db_name}`.";

			// Escape single quotes in password for inline SQL
			$app_pass_esc = str_replace("'", "''", $app_pass);

			// Create app user (handle pre-existing user gracefully)
			try {
				$pdo->exec("CREATE USER `{$app_user}`@`{$app_user_host}` IDENTIFIED BY '{$app_pass_esc}'");
				$log[] = "Created user `{$app_user}`@`{$app_user_host}`.";
			} catch (PDOException $ue) {
				// Error 1396: user already exists, update password instead
				if (strpos($ue->getMessage(), '1396') !== false || stripos($ue->getMessage(), 'already exists') !== false) {
					$pdo->exec("ALTER USER `{$app_user}`@`{$app_user_host}` IDENTIFIED BY '{$app_pass_esc}'");
					$log[] = "User `{$app_user}`@`{$app_user_host}` already existed; password updated.";
				} else {
					throw $ue;
				}
			}

			// Grant all privileges on the new database
			$pdo->exec("GRANT ALL PRIVILEGES ON `{$db_name}`.* TO `{$app_user}`@`{$app_user_host}`");
			$pdo->exec("FLUSH PRIVILEGES");
			$log[] = "Granted all privileges on `{$db_name}` to `{$app_user}`@`{$app_user_host}`.";

			// Select the new database for schema import
			$pdo->exec("USE `{$db_name}`");

			// Import SCHEMA.sql
			$sql        = file_get_contents($schema_path);
			$statements = $this->split_sql_statements($sql);
			$count      = 0;

			foreach ($statements as $stmt) {
				$stmt = trim($stmt);
				if ($stmt === '') continue;
				$pdo->exec($stmt);
				$count++;
			}

			$log[] = "Imported {$count} SQL statements from SCHEMA.sql.";

			// Mark all existing migration files as applied — SCHEMA.sql already incorporates them.
			// This prevents the migration runner from trying to re-apply them on a fresh install.
			$migrations_dir = dirname(__FILE__) . '/../../db/migrations/';
			$migration_files = glob($migrations_dir . '*.sql');
			if ($migration_files) {
				sort($migration_files);
				$marked = 0;
				foreach ($migration_files as $mf) {
					$filename = basename($mf);
					$pdo->exec("INSERT IGNORE INTO `migrations` (`filename`) VALUES ('" . str_replace("'", "''", $filename) . "')");
					$marked++;
				}
				$log[] = "Marked {$marked} migration file(s) as applied.";
			}

			return [
				'success' => true,
				'message' => 'Database installed successfully.',
				'log'     => $log,
			];

		} catch (Exception $e) {
			// Rollback: drop database if it was created during this run
			if ($db_created) {
				try {
					$pdo->exec("DROP DATABASE IF EXISTS `{$db_name}`");
					$log[] = "Rolled back: dropped database `{$db_name}`.";
				} catch (Exception $re) {
					$log[] = "Rollback failed: " . $re->getMessage();
				}
			}
			return [
				'success' => false,
				'errors'  => [$e->getMessage()],
				'log'     => $log,
			];
		}
	}

	/**
	 * Split a SQL file into individual executable statements.
	 * Strips line comments (-- and #). Preserves MySQL conditional comments.
	 *
	 * @method split_sql_statements
	 * @param  string $sql
	 * @return string[]
	 */
	private function split_sql_statements(string $sql): array
	{
		$statements  = [];
		$current     = '';
		$in_string   = false;
		$string_char = '';
		$len         = strlen($sql);

		for ($i = 0; $i < $len; $i++) {
			$ch = $sql[$i];

			if ($in_string) {
				$current .= $ch;
				if ($ch === $string_char && ($i === 0 || $sql[$i - 1] !== '\\')) {
					$in_string = false;
				}
				continue;
			}

			if ($ch === '"' || $ch === "'") {
				$in_string   = true;
				$string_char = $ch;
				$current    .= $ch;
				continue;
			}

			// Line comment: -- or #
			if (($ch === '-' && ($sql[$i + 1] ?? '') === '-') || $ch === '#') {
				while ($i < $len && $sql[$i] !== "\n") {
					$i++;
				}
				$current .= "\n";
				continue;
			}

			// Block comment
			if ($ch === '/' && ($sql[$i + 1] ?? '') === '*') {
				$is_conditional = ($sql[$i + 2] ?? '') === '!';
				$block = '/';
				$i++;
				while ($i < $len) {
					$block .= $sql[$i];
					if ($sql[$i] === '/' && $sql[$i - 1] === '*') {
						break;
					}
					$i++;
				}
				// Keep MySQL conditional comments (/*!...*/), drop regular comments
				if ($is_conditional) {
					$current .= $block;
				}
				continue;
			}

			if ($ch === ';') {
				$statements[] = $current;
				$current = '';
				continue;
			}

			$current .= $ch;
		}

		if (trim($current) !== '') {
			$statements[] = $current;
		}

		return $statements;
	}

	/**
	 * Checks whether the backup script can write to db/bkp/.
	 * Prints a Tabler warning alert and returns false if the directory is not writable
	 * (or cannot be created). Returns true silently when everything is fine.
	 *
	 * @method validate_backup_directory
	 * @return bool  true = writable, false = not writable (alert already printed)
	 */
	public function validate_backup_directory(): bool
	{
		$bkp_dir    = realpath(dirname(__FILE__) . "/../../db") . "/bkp";
		$bkp_parent = dirname($bkp_dir);

		if (is_dir($bkp_dir)) {
			$writable = is_writable($bkp_dir);
			$detail   = "directory <code>db/bkp/</code> exists but is not writable by the web server process";
		} else {
			$writable = is_writable($bkp_parent);
			$detail   = "directory <code>db/bkp/</code> does not exist and <code>db/</code> is not writable — backup script cannot create it";
		}

		if ($writable) {
			return true;
		}

		$icon = "<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon alert-icon'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 9v4' /><path d='M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.871l-8.106 -13.534a1.914 1.914 0 0 0 -3.274 0z' /><path d='M12 16h.01' /></svg>";
		print "<div class='alert alert-warning container-fluid mt-2 mb-2' role='alert' style='display:block'>";
		print $icon;
		print "<div><strong>" . _("Backup script: missing write permission") . "</strong> — " . $detail . ".</div>";
		print "</div>\n";

		return false;
	}

	/**
	 * Validates the live database structure against db/SCHEMA.sql.
	 * Checks for missing tables, missing/wrong columns, missing indexes and missing FK constraints.
	 * Returns a list of issues; each issue carries the SQL fix statement needed to repair it.
	 *
	 * @method validate_database
	 * @param  Database_PDO $Database
	 * @return array  ['ok'=>bool, 'error'=>string|null, 'issues'=>[['table','type','description','fix_sql']]]
	 */
	public function validate_database(Database_PDO $Database): array
	{
		$schema_path = dirname(__FILE__) . '/../../db/SCHEMA.sql';
		if (!file_exists($schema_path)) {
			return ['ok' => false, 'error' => 'db/SCHEMA.sql not found', 'issues' => []];
		}

		$db_name  = $Database->dbname;
		$expected = $this->vdb_parse_schema(file_get_contents($schema_path));

		// Fetch actual DB state from INFORMATION_SCHEMA
		$act_tables  = array_column(array_map('get_object_vars',
			$Database->getObjectsQuery(
				"SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
				 WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'",
				[$db_name]
			)
		), 'TABLE_NAME');
		$act_columns = $this->vdb_get_columns($Database, $db_name);
		$act_indexes = $this->vdb_get_indexes($Database, $db_name);
		$act_fks     = $this->vdb_get_fks($Database, $db_name);

		$issues = [];

		foreach ($expected as $table => $def) {

			// --- Missing table ---
			if (!in_array($table, $act_tables)) {
				$issues[] = [
					'table'       => $table,
					'type'        => 'missing_table',
					'description' => "Table `{$table}` does not exist",
					'fix_sql'     => $def['create_sql'],
				];
				continue; // columns/indexes cannot be checked on a missing table
			}

			// --- Missing / wrong columns ---
			$a_cols = $act_columns[$table] ?? [];
			foreach ($def['columns'] as $col => $ci) {
				if (!isset($a_cols[$col])) {
					$issues[] = [
						'table'       => $table,
						'type'        => 'missing_column',
						'description' => "Column `{$table}`.`{$col}` is missing",
						'fix_sql'     => "ALTER TABLE `{$table}` ADD COLUMN {$ci['original']};",
					];
				} else {
					$exp_type  = $this->vdb_normalize_type($ci['type']);
					$act_type  = $this->vdb_normalize_type($a_cols[$col]['type']);
					$exp_null  = $ci['nullable'];
					$act_null  = $a_cols[$col]['nullable'];
					if ($exp_type !== $act_type || $exp_null !== $act_null) {
						$exp_desc = $exp_type . ($exp_null ? '' : ' NOT NULL');
						$act_desc = $act_type . ($act_null ? '' : ' NOT NULL');
						$issues[] = [
							'table'       => $table,
							'type'        => 'wrong_column',
							'description' => "Column `{$table}`.`{$col}`: expected `{$exp_desc}`, got `{$act_desc}`",
							'fix_sql'     => "ALTER TABLE `{$table}` MODIFY COLUMN {$ci['original']};",
						];
					}
				}
			}

			// --- Missing indexes ---
			$a_idx = $act_indexes[$table] ?? [];
			foreach ($def['indexes'] as $idx_name => $ii) {
				if (isset($a_idx[$idx_name])) continue;
				if ($ii['type'] === 'PRIMARY') {
					$fix = "ALTER TABLE `{$table}` ADD PRIMARY KEY ({$ii['columns']});";
					$desc = "Primary key on `{$table}` is missing";
				} elseif ($ii['type'] === 'UNIQUE') {
					$fix = "ALTER TABLE `{$table}` ADD UNIQUE KEY `{$idx_name}` ({$ii['columns']});";
					$desc = "Unique index `{$idx_name}` on `{$table}` is missing";
				} else {
					$fix = "ALTER TABLE `{$table}` ADD KEY `{$idx_name}` ({$ii['columns']});";
					$desc = "Index `{$idx_name}` on `{$table}` is missing";
				}
				$issues[] = ['table' => $table, 'type' => 'missing_index', 'description' => $desc, 'fix_sql' => $fix];
			}

			// --- Missing FK constraints ---
			$a_fks = $act_fks[$table] ?? [];
			foreach ($def['fks'] as $fk_name => $fki) {
				if (isset($a_fks[$fk_name])) continue;
				$issues[] = [
					'table'       => $table,
					'type'        => 'missing_fk',
					'description' => "Foreign key `{$fk_name}` on `{$table}` is missing",
					'fix_sql'     => "ALTER TABLE `{$table}` ADD CONSTRAINT `{$fk_name}` FOREIGN KEY ({$fki['columns']}) REFERENCES `{$fki['ref_table']}` ({$fki['ref_cols']}) {$fki['actions']};",
				];
			}
		}

		return ['ok' => empty($issues), 'error' => null, 'issues' => $issues];
	}

	/**
	 * Parse all CREATE TABLE statements from a SQL schema dump.
	 * Returns [tableName => ['columns', 'indexes', 'fks', 'create_sql']]
	 */
	private function vdb_parse_schema(string $sql): array
	{
		$tables = [];
		if (!preg_match_all('/CREATE\s+TABLE\s+`(\w+)`\s*\((.*?)\)\s*ENGINE[^;]*;/si', $sql, $matches, PREG_SET_ORDER)) {
			return $tables;
		}
		foreach ($matches as $m) {
			$table   = $m[1];
			$body    = $m[2];
			$columns = [];
			$indexes = [];
			$fks     = [];

			foreach (explode("\n", $body) as $line) {
				$line = rtrim(trim($line), ',');
				if ($line === '') continue;

				if (preg_match('/^`(\w+)`\s+(.+)$/i', $line, $c)) {
					// Column definition
					$cname = $c[1];
					$cdef  = $c[2];
					preg_match('/^((?:enum|set)\s*\([^)]+\)|\S+)(\s+unsigned)?/i', $cdef, $tm);
					$columns[$cname] = [
						'type'     => trim(($tm[1] ?? '') . ($tm[2] ?? '')),
						'nullable' => stripos($cdef, 'NOT NULL') === false,
						'original' => "`{$cname}` {$cdef}",
					];
				} elseif (preg_match('/^PRIMARY\s+KEY\s*\((.+)\)/i', $line, $k)) {
					$indexes['PRIMARY'] = ['type' => 'PRIMARY', 'columns' => $k[1]];
				} elseif (preg_match('/^(UNIQUE\s+KEY|KEY)\s+`(\w+)`\s*\((.+)\)/i', $line, $k)) {
					$indexes[$k[2]] = [
						'type'    => stripos($k[1], 'UNIQUE') !== false ? 'UNIQUE' : 'KEY',
						'columns' => $k[3],
					];
				} elseif (preg_match('/^CONSTRAINT\s+`(\w+)`\s+FOREIGN\s+KEY\s*\((.+?)\)\s+REFERENCES\s+`(\w+)`\s*\((.+?)\)(.*)/i', $line, $f)) {
					$fks[$f[1]] = [
						'columns'   => $f[2],
						'ref_table' => $f[3],
						'ref_cols'  => $f[4],
						'actions'   => trim($f[5]),
					];
				}
			}
			$tables[$table] = ['columns' => $columns, 'indexes' => $indexes, 'fks' => $fks, 'create_sql' => $m[0]];
		}
		return $tables;
	}

	/** Normalize column type for comparison — strips integer display widths (cosmetic in MySQL 8+). */
	private function vdb_normalize_type(string $type): string
	{
		$type = preg_replace('/\b(tinyint|smallint|mediumint|int|bigint)\s*\(\d+\)/i', '$1', $type);
		return strtolower(preg_replace('/\s+/', ' ', trim($type)));
	}

	private function vdb_get_columns(Database_PDO $Database, string $db_name): array
	{
		$result = [];
		foreach ($Database->getObjectsQuery(
			"SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
			 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ?
			 ORDER BY TABLE_NAME, ORDINAL_POSITION",
			[$db_name]
		) as $row) {
			$result[$row->TABLE_NAME][$row->COLUMN_NAME] = [
				'type'     => $row->COLUMN_TYPE,
				'nullable' => $row->IS_NULLABLE === 'YES',
			];
		}
		return $result;
	}

	private function vdb_get_indexes(Database_PDO $Database, string $db_name): array
	{
		$result = [];
		foreach ($Database->getObjectsQuery(
			"SELECT TABLE_NAME, INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
			 WHERE TABLE_SCHEMA = ? GROUP BY TABLE_NAME, INDEX_NAME",
			[$db_name]
		) as $row) {
			$result[$row->TABLE_NAME][$row->INDEX_NAME] = true;
		}
		return $result;
	}

	private function vdb_get_fks(Database_PDO $Database, string $db_name): array
	{
		$result = [];
		foreach ($Database->getObjectsQuery(
			"SELECT rc.CONSTRAINT_NAME, kcu.TABLE_NAME
			 FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
			 JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
			      ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
			      AND kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
			 WHERE rc.CONSTRAINT_SCHEMA = ?
			 GROUP BY rc.CONSTRAINT_NAME, kcu.TABLE_NAME",
			[$db_name]
		) as $row) {
			$result[$row->TABLE_NAME][$row->CONSTRAINT_NAME] = true;
		}
		return $result;
	}

	/**
	 * Prints a Tabler breadcrumb nav based on the current $_params.
	 * Last (active) item is not clickable; all preceding items are linked.
	 *
	 * URL structure: /{tenant}/{route}/{app}/{id1}/
	 *
	 * @method print_breadcrumbs
	 * @return void
	 */
	public function print_breadcrumbs(): void
	{
		global $_params;

		$tenant = $_params['tenant'] ?? '';
		$route  = $_params['route']  ?? 'dashboard';
		$app    = (isset($_params['app'])  && strlen($_params['app'])  > 0) ? $_params['app']  : null;
		$id1    = (isset($_params['id1'])  && strlen($_params['id1'])  > 0) ? $_params['id1']  : null;

		// Human-readable route names
		$route_labels = [
			'dashboard'    => _('Dashboard'),
			'zones'        => _('Zones'),
			'certificates' => _('Certificates'),
			'scanning'     => _('Scanning'),
			'logs'         => _('Logs'),
			'users'        => _('Users'),
			'tenants'      => _('Tenants'),
			'user'         => _('User'),
			'search'       => _('Search'),
			'fetch'        => _('Fetch'),
			'transform'       => _('Transform'),
			'ca-certificates' => _('CA Certificates'),
		];

		// Human-readable app names for specific route/app combinations
		$sub_labels = [
			'scanning' => ['agents' => _('Scan agents'), 'portgroups' => _('Port groups'), 'cron' => _('Cron jobs')],
			'user'     => ['profile' => _('Profile')],
		];

		// Build items: [label, url]  — url===null means active/last (not clickable)
		$items = [];

		if ($route === 'dashboard') {
			$items[] = [null];
		} else {
			$items[] = [_('Dashboard'), "/{$tenant}/dashboard/"];

			$route_label = $route_labels[$route] ?? ucfirst($route);

			if ($app === null) {
				$items[] = [$route_label, null];
			} else {
				$items[] = [$route_label, "/{$tenant}/{$route}/"];

				$app_label = isset($sub_labels[$route][$app])
					? $sub_labels[$route][$app]
					: htmlspecialchars($app, ENT_QUOTES, 'UTF-8');

				if ($id1 === null) {
					$items[] = [$app_label, null];
				} else {
					$items[] = [$app_label, "/{$tenant}/{$route}/{$app}/"];
					$items[] = [htmlspecialchars($id1, ENT_QUOTES, 'UTF-8'), null];
				}
			}
		}

		// Render — right-aligned, links in text-secondary, active item unstyled
		$html = "<ol class='breadcrumb justify-content-end' aria-label='breadcrumbs'>\n";
		foreach ($items as [$label, $url]) {
			if ($url !== null) {
				$html .= "  <li class='breadcrumb-item'><a class='text-secondary' href='" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "'>{$label}</a></li>\n";
			} else {
				$html .= "  <li class='breadcrumb-item active' aria-current='page'>{$label}</li>\n";
			}
		}
		$html .= "</ol>\n";

		print $html;
	}
}




/**
 *
 *
 * Global functions
 *
 *
 *
 */





/**
 * Check if required php features are missing
 * @param  mixed $required_extensions
 * @param  mixed $required_functions
 * @return string|bool
 */
function php_feature_missing($required_extensions = null, $required_functions = null)
{
	if (is_array($required_extensions)) {
		foreach ($required_extensions as $ext) {
			if (extension_loaded($ext))
				continue;

			return _('Required PHP extension not installed: ') . $ext;
		}
	}

	if (is_array($required_functions)) {
		foreach ($required_functions as $function) {
			if (function_exists($function))
				continue;

			$ini_path = trim(php_ini_loaded_file());
			$disabled_functions = ini_get('disable_functions');
			if (is_string($disabled_functions) && in_array($function, explode(';', $disabled_functions)))
				return _('Required function disabled') . " : $ini_path, disable_functions=$function";

			return _('Required function not found: ') . $function . '()';
		}
	}

	return false;
}


/**
 * Check if required php features are missing
 * @param  mixed $required_extensions
 * @param  mixed $required_functions
 * @return array
 */
function php_feature_missing_all($required_extensions = null, $required_functions = null)
{

	$errors = [];

	if (is_array($required_extensions)) {
		foreach ($required_extensions as $ext) {
			if (extension_loaded($ext))
				continue;

			$errors[] = $ext;
		}
	}

	return $errors;
}


/**
 * Returns a short purpose description for a known PHP extension used by this project.
 *
 * @param  string $extension  Extension name (e.g. 'curl', 'openssl')
 * @return string|null        Purpose string, or null if extension is unknown
 */
function php_extension_purpose(string $extension): ?string
{
	$purposes = [
		'curl'      => 'Remote agent communication via HTTP API calls',
		'openssl'   => 'SSL/TLS certificate scanning, parsing and fingerprinting',
		'pcntl'     => 'Multi-process forking for parallel host scanning',
		'posix'     => 'Process management (PID, signals, FIFOs) companion to pcntl',
		'pdo'       => 'Database abstraction layer (prepared statements, transactions)',
		'pdo_mysql' => 'MySQL driver for PDO database connectivity',
		'session'   => 'User authentication sessions and theme preference storage',
		'hash'      => 'Password hashing (SHA-512) and CSRF token generation',
		'gettext'   => 'Internationalisation — translates UI strings via _()',
	];

	return $purposes[strtolower($extension)] ?? null;
}


/**
 * Cronjob helpr function for scanning via forked process
 *
 * @method scan_host
 * @param  object $host
 * @param  datetime $execution_time
 * @param  int $tenant_id
 * @return void
 */
function scan_host($host, $execution_time, $tenant_id)
{
	# load classes
	$Database = new Database_PDO();
	$SSL = new SSL($Database);

	// try to fetch cert
	$host_certificate = $SSL->fetch_website_certificate($host, $execution_time, $tenant_id);

	// update cert if fopund
	if ($host_certificate !== false) {
		$cert_id = $SSL->update_db_certificate($host_certificate, $host->t_id, $host->z_id, $execution_time);
		// extract and store CA certs from the chain
		if (!empty($host_certificate['chain'])) {
			$SSL->upsert_chain_cas($host_certificate['chain'], $host->t_id);
		}
		// get IP if not set from remote agent
		$ip = !isset($host_certificate['ip']) ? $SSL->resolve_ip($host->hostname) : $host_certificate['ip'];
		// if Id of certificate changed
		if($host->c_id!=$cert_id) {
			// get new cert
			$certificate = $Database->getObject ("certificates", $cert_id);
			// assign
			$SSL->assign_host_certificate ($host, $ip, $host_certificate['port'], $certificate, $host_certificate['tls_proto'], $execution_time, null);
		}
	}
	// dummy return
	exit(1);
}

<?php

/**
 * Database migration runner.
 *
 * Migration files live in db/migrations/ and are named NNNN_description.sql
 * (e.g. 0001_add_changepass_to_users.sql). They are applied in alphabetical
 * (numeric) order. Each applied filename is recorded in the `migrations` table
 * so that it is never run twice.
 *
 * Usage (web):
 *   $Migration->get_pending()   → string[] of unapplied filenames
 *   $Migration->apply_all()     → array of per-file result objects
 *
 * Usage (CLI):  php migrate.php
 */
class Migrations
{
	/** @var Database_PDO */
	private $Database;

	/** @var string Absolute path to the migrations directory */
	private $dir;

	public function __construct(Database_PDO $Database)
	{
		$this->Database = $Database;
		$this->dir      = dirname(__FILE__) . '/../../db/migrations/';
	}

	/**
	 * Return all migration filenames on disk, sorted numerically.
	 *
	 * @return string[]
	 */
	private function get_all_files(): array
	{
		$files = glob($this->dir . '*.sql');
		if ($files === false) return [];
		sort($files);
		return array_map('basename', $files);
	}

	/**
	 * Return filenames already recorded in the migrations table.
	 * Returns an empty array if the table does not exist yet.
	 *
	 * @return string[]
	 */
	private function get_applied(): array
	{
		try {
			$rows = $this->Database->getObjectsQuery('SELECT `filename` FROM `migrations` ORDER BY `filename`');
			return array_column(array_map(fn($r) => (array)$r, $rows), 'filename');
		} catch (Exception $e) {
			// Table may not exist on a fresh install before the schema is imported
			return [];
		}
	}

	/**
	 * Return list of migration filenames that have not been applied yet.
	 *
	 * @return string[]
	 */
	public function get_pending(): array
	{
		return array_values(array_diff($this->get_all_files(), $this->get_applied()));
	}

	/**
	 * Quick boolean check — true if any migrations are pending.
	 *
	 * @return bool
	 */
	public function has_pending(): bool
	{
		return count($this->get_pending()) > 0;
	}

	/**
	 * Return the numeric version of the last applied migration.
	 * The version is the leading integer prefix of the filename (e.g. 0042 → 42).
	 * Returns 0 if no migrations have been applied yet.
	 *
	 * @return int
	 */
	public function get_current_version(): int
	{
		$applied = $this->get_applied();
		if (empty($applied)) {
			return 0;
		}
		sort($applied);
		$last = end($applied);
		// Extract leading digits: "0042_some_change.sql" → 42
		if (preg_match('/^(\d+)/', $last, $m)) {
			return (int)$m[1];
		}
		return 0;
	}

	/**
	 * Return the numeric version of the latest migration file on disk.
	 * Returns 0 if no migration files exist.
	 *
	 * @return int
	 */
	public function get_latest_version(): int
	{
		$all = $this->get_all_files();
		if (empty($all)) {
			return 0;
		}
		$last = end($all);
		if (preg_match('/^(\d+)/', $last, $m)) {
			return (int)$m[1];
		}
		return 0;
	}

	/**
	 * Apply all pending migrations in order.
	 *
	 * Each migration file is split into individual SQL statements and executed
	 * one by one. If a statement fails the migration is aborted and the error
	 * is returned; already-applied migrations are not rolled back.
	 *
	 * @return array  Each element: ['file'=>string, 'success'=>bool, 'error'=>string|null]
	 */
	public function apply_all(): array
	{
		$results = [];
		$pending = $this->get_pending();

		foreach ($pending as $filename) {
			$path = $this->dir . $filename;

			if (!file_exists($path)) {
				$results[] = ['file' => $filename, 'success' => false, 'error' => 'File not found'];
				break;
			}

			$sql        = file_get_contents($path);
			$statements = $this->split_sql($sql);
			$error      = null;

			foreach ($statements as $stmt) {
				$stmt = trim($stmt);
				if ($stmt === '') continue;
				try {
					$this->Database->runQuery($stmt);
				} catch (Exception $e) {
					$error = $e->getMessage();
					break;
				}
			}

			if ($error !== null) {
				$results[] = ['file' => $filename, 'success' => false, 'error' => $error];
				break; // Stop on first failure — do not apply subsequent migrations
			}

			// Record as applied
			try {
				$this->Database->insertObject('migrations', ['filename' => $filename]);
			} catch (Exception $e) {
				$results[] = ['file' => $filename, 'success' => false, 'error' => 'Could not record migration: ' . $e->getMessage()];
				break;
			}

			$results[] = ['file' => $filename, 'success' => true, 'error' => null];
		}

		return $results;
	}

	/**
	 * Split a SQL file into individual statements.
	 * Strips -- and # line comments; preserves MySQL conditional comments.
	 *
	 * @param  string   $sql
	 * @return string[]
	 */
	private function split_sql(string $sql): array
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
				while ($i < $len && $sql[$i] !== "\n") $i++;
				$current .= "\n";
				continue;
			}

			// Block comment /* ... */ — keep conditional /*!...*/
			if ($ch === '/' && ($sql[$i + 1] ?? '') === '*') {
				$is_conditional = ($sql[$i + 2] ?? '') === '!';
				$block = '/';
				$i++;
				while ($i < $len) {
					$block .= $sql[$i];
					if ($sql[$i] === '/' && $sql[$i - 1] === '*') break;
					$i++;
				}
				if ($is_conditional) $current .= $block;
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
}

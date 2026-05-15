<?php

/**
 *
 * Class to handle cron entries
 *
 */
class Cron extends Common
{

	/**
	 * Valid scripts
	 * @var array
	 */
	private $valid_cronjob_scripts = [
		"update_certificates"  => "update_certificates",
		"axfr_transfer"        => "axfr_transfer",
		"remove_orphaned"      => "remove_orphaned",
		"expired_certificates" => "expired_certificates",
		"backup"               => "backup",
		"testssl_scan"         => "testssl_scan"
	];

	/**
	 * All crnjobs
	 * @var array
	 */
	private $cronjobs = [];

	/**
	 * User details
	 * @var bool
	 */
	private $user = false;

	/**
	 * Database object
	 * @var bool
	 */
	private $Database = false;

	/**
	 * Execution time
	 * @var bool
	 */
	private $exec_time = false;





	/**
	 * Constructor
	 *
	 * @method __construct
	 * @param  Database_PDO $Database
	 * @param  object $user
	 */
	public function __construct(Database_PDO $Database, $user = NULL)
	{
		// Save database object
		$this->Database = $Database;
		// user
		if (is_object($user)) {
			$this->user = $user;
		}
	}

	/**
	 * Fetch all cronjobs
	 * @method fetch_cronjobs
	 * @return false|array
	 */
	public function fetch_cronjobs()
	{
		// Fetch all cronjobs
		try {
			$this->cronjobs = $this->Database->getObjectsQuery("select * from cron;");
		}
		catch (Exception $e) {
			$this->errors[] = "Unable to fetch cron jobs";
			$this->result_die();
		}
		// result
		return $this->cronjobs;
	}

	/**
	 * Fetch specific cronjob
	 * @method fetch_cronjob
	 * @param  int $tenant_id
	 * @param  string $script
	 * @return bool|object
	 */
	public function fetch_cronjob($tenant_id = 0, $script = "")
	{
		try {
			if (!$this->validate_script($script)) {
				throw new Exception("Invalid script");
			}
			return $this->Database->getObjectQuery("select * from cron where t_id = ? and script = ? order by hour,minute asc", [$tenant_id, $script]);

		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			return false;
		}
	}

	/**
	 * Fetch all cronjobs for tenant or all (admin)
	 * @method fetch_tenant_cronjobs
	 * @param  bool $reindex
	 * @return array
	 */
	public function fetch_tenant_cronjobs($reindex = false)
	{
		// Fetch all cronjobs
		try {
			if ($this->user->admin == "1") {
				$cronjobs = $this->Database->getObjectsQuery("select * from cron order by hour,minute asc");
			}
			else {
				$cronjobs = $this->Database->getObjectsQuery("select * from cron where t_id = ? order by hour,minute asc", [$this->user->t_id]);
			}
		}
		catch (Exception $e) {
			$this->errors[] = "Unable to fetch cron jobs [" . $e->getMessage() . "]";
			$this->result_die();
		}
		// reindex
		if ($reindex) {
			$out = [];
			foreach ($cronjobs as $j) {
				$out[$j->t_id][$j->script] = $j;
			}
			$cronjobs = $out;
		}
		// result
		return $cronjobs;
	}

	/**
	 * Return array of valid scriots
	 * @method get_valid_scripts
	 * @return [type]
	 */
	public function get_valid_scripts()
	{
		return $this->valid_cronjob_scripts;
	}

	/**
	 * Add names to scripts
	 * @method name_script
	 * @param  string $name
	 * @return array
	 */
	public function name_script($name = "")
	{
		if ($name == "update_certificates") {
			return [
				"name" => "Update SSL certificates",
				"desc" => "This script will check for new certificates from all tenant hosts from all available zones and will send mail if certificate change occurs"
			];
		}
		elseif ($name == "axfr_transfer") {
			return [
				"name" => "Zone transfers",
				"desc" => "This script will sync all hosts for AXFR zones from DNS server (AXFR transfer) and add / remove new hosts to local database if needed"
			];
		}
		elseif ($name == "remove_orphaned") {
			return [
				"name" => "Remove orhaned certificates",
				"desc" => "This script removes all orphaned certificates that are no longer attached to any host"
			];
		}
		elseif ($name == "expired_certificates") {
			return [
				"name" => "Notify about expired certificates",
				"desc" => "This script will check any certificates that are about to expire or have expired and email notification to owners and administrators"
			];
		}
		elseif ($name == "backup") {
			return [
				"name" => "SQL backup",
				"desc" => "This script will backup SQL database for specific user"
			];
		}
		else {
			return [
				"name" => $name,
				"desc" => ""
			];
		}
	}

	/**
	 * Execute cronjobs at specific time
	 * @method execute_cronjobs
	 * @param  string $execution_time
	 * @param  array $cli_arguments
	 * @return void
	 */
	public function execute_cronjobs($execution_time, $cli_arguments = [])
	{

		// save time
		$this->exec_time = $execution_time;

		if (sizeof($this->cronjobs) > 0) {
			foreach ($this->cronjobs as $j) {
				// does it need to be executed?
				if ($this->needs_execution($j, $cli_arguments)) {
					// stamp last_executed before running so schedule won't re-trigger in the same window
					$this->update_last_executed($j->id);
					// execute script
					include(dirname(__FILE__) . "/../cron/{$j->script}.php");
					// clear force only after the script has finished — if it crashes, force stays set
					if (!empty($j->force)) {
						$this->clear_force($j->id);
					}
				}
			}

			// always run testssl_scan for every tenant that has any cron entry
			$tenant_ids = array_unique(array_column($this->cronjobs, 't_id'));
			foreach ($tenant_ids as $tid) {
				$j = (object)['t_id' => $tid, 'script' => 'testssl_scan'];
				include(dirname(__FILE__) . "/../cron/testssl_scan.php");
			}
		}
	}

	/**
	 * Stamp last_executed for a cron entry (called before the script runs).
	 * @param  int $cron_id
	 */
	private function update_last_executed($cron_id = 0)
	{
		try {
			$this->Database->runQuery("update cron set last_executed = ? where id = ?", [$this->exec_time, $cron_id]);
		}
		catch (Exception $e) {
			$this->errors[] = "Unable to update cron execution time";
		}
	}

	/**
	 * Clear the force flag after a successful script run.
	 * @param  int $cron_id
	 */
	private function clear_force($cron_id = 0)
	{
		try {
			$this->Database->runQuery("update cron set `force` = 0 where id = ?", [$cron_id]);
		}
		catch (Exception $e) {
			$this->errors[] = "Unable to clear force flag";
		}
	}

	/**
	 * Set or clear force execution flag for a cronjob
	 * @method set_force_execution
	 * @param  int $cron_id
	 * @param  int $force  1 to force, 0 to clear
	 * @return bool
	 */
	public function set_force_execution($cron_id = 0, $force = 1)
	{
		try {
			$this->Database->runQuery("update cron set `force` = ? where id = ?", [intval($force), $cron_id]);
			return true;
		}
		catch (Exception $e) {
			$this->errors[] = "Unable to set force execution";
			return false;
		}
	}

	/**
	 * Checks if cronjob needs to be executed
	 * @method needs_execution
	 * @param  object] $crontab_entry ('15 * * 1 *')
	 * @param  array $cli_arguments
	 * @return bool
	 */
	private function needs_execution($crontab_entry = "", $cli_arguments = [])
	{
		// cli overrides
		if (isset($cli_arguments[1]) && isset($cli_arguments[2])) {
			if ($crontab_entry->t_id == $cli_arguments[1] && $this->validate_script($crontab_entry->script) && $crontab_entry->script == $cli_arguments[2]) {
				return true;
			}
		}
		// validate script
		if (!$this->validate_script($crontab_entry->script)) {
			return false;
		}

		// force execution flag
		if (!empty($crontab_entry->force)) {
			return true;
		}

		// current time
		$time = explode(' ', date('i G j n w', strtotime($this->exec_time)));
		// crontab
		$crontab = explode(' ', "{$crontab_entry->minute} {$crontab_entry->hour} {$crontab_entry->day} {$crontab_entry->month} {$crontab_entry->weekday}");

		// check each value
		foreach ($crontab as $k => &$v) {
			$time[$k] = intval($time[$k]);
			$v = explode(',', $v);
			foreach ($v as &$v1) {
				$v1 = preg_replace(array('/^\*$/', '/^\d+$/', '/^(\d+)\-(\d+)$/', '/^\*\/(\d+)$/'), array('true', $time[$k] . '===\0', '(\1<=' . $time[$k] . ' and ' . $time[$k] . '<=\2)', $time[$k] . '%\1===0'), $v1);
			}
			$v = '(' . implode(' or ', $v) . ')';
		}
		$crontab = implode(' and ', $crontab);
		return eval('return ' . $crontab . ';');
	}

	/**
	 * Script validator
	 * @method validate_script
	 * @param  string $script
	 * @return bool
	 */
	private function validate_script($script = "")
	{
		return in_array($script, $this->valid_cronjob_scripts) ? true : false;
	}

	/**
	 * Random function generation
	 * @method rand
	 * @param  int $min
	 * @param  int $max
	 * @param  int $step
	 * @return numeric
	 */
	public function rand($min = 0, $max = 60, $step = 5)
	{
		// Generate a random number between 0 and 12
		$randomNumber = rand($min, ($max - $step) / $step);
		// multiply by step
		$randomNumber *= $step;
		// return
		return $randomNumber;
	}
}
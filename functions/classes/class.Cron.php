<?php

class Cron extends Common {

	private $valid_cronjob_scripts = [
									"update_certificates"  => "update_certificates",
									"axfr_transfer"        => "axfr_transfer",
									"remove_orphaned"      => "remove_orphaned",
									"expired_certificates" => "expired_certificates"
									];

	private $cronjobs = [];

	private $user = false;

	private $Database = false;


	public function __construct (Database_PDO $Database, $user = NULL) {
		// Save database object
		$this->Database = $Database;
		// user
		if(is_object($user)) {
			$this->user = $user;
		}
	}

	public function fetch_cronjobs () {
		// Fetch all cronjobs
		try {
			$this->cronjobs = $this->Database->getObjectsQuery("select * from cron;");
		} catch (Exception $e) {
			$this->errors[] = "Unable to fetch cron jobs";
			$this->result_die ();
		}
		// result
		return $this->cronjobs;
	}

	public function fetch_tenant_cronjobs ($reindex = false) {
		// Fetch all cronjobs
		try {
			if($this->user->admin=="1") {
				$cronjobs = $this->Database->getObjectsQuery("select * from cron order by hour,minute asc");
			}
			else {
				$cronjobs = $this->Database->getObjectsQuery("select * from cron where t_id = ? order by hour,minute asc", [$this->user->t_id]);
			}
		} catch (Exception $e) {
			$this->errors[] = "Unable to fetch cron jobs [".$e->getMessage()."]";
			$this->result_die ();
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

	public function get_valid_scripts () {
		return $this->valid_cronjob_scripts;
	}

	public function name_script ($name = "") {


		if ($name=="update_certificates") {
			return [
				"name" => "Update SSL certificates",
				"desc" => "This script will check for new certificates from all tenant hosts from all available zones and will send mail if certificate change occurs"
				];
		}
		elseif ($name=="axfr_transfer") {
			return [
				"name" => "Zone transfers",
				"desc" => "This script will sync all hosts for AXFR zones from DNS server (AXFR transfer) and add / remove new hosts to local database if needed"
				];
		}
		elseif ($name=="remove_orphaned") {
			return [
				"name" => "Remove orhaned certificates",
				"desc" => "This script removes all orphaned certificates that are no longer attached to any host"
				];
		}
		elseif ($name=="expired_certificates") {
			return [
				"name" => "Notify about expired certificates",
				"desc" => "This script will check any certificates that are about to expire or have expired and email notification to owners and administrators"
				];
		}
		else {
			return [
				"name" => $name,
				"desc" => ""
				];
		}
	}

	public function execute_cronjobs ($execution_time,  $cli_arguments = []) {
		if(sizeof($this->cronjobs)>0) {
			foreach ($this->cronjobs as $j) {
				// does it need to be executed?
				if ($this->needs_execution($j, $cli_arguments)) {
					// update time
					$this->update_crontime_execution ($j->id, $execution_time);
					// execute script
					include(dirname(__FILE__)."/../cron/{$j->script}.php");
				}
			}
		}
	}

	private function update_crontime_execution ($cron_id = 0, $execution_time = NULL) {
		try {
			$this->Database->runQuery("update cron set last_executed = ? where id = ?", [$execution_time, $cron_id]);
		} catch (Exception $e) {
			$this->errors[] = "Unable to update cron execution time";
		}
	}




	private function needs_execution ($j, $cli_arguments = []) {
		// cli overrides
		if (isset($cli_arguments[1]) && isset($cli_arguments[2])) {
			if($j->t_id == $cli_arguments[1] && $this->validate_script($j->script) && $j->script==$cli_arguments[2] ) {
				return true;
			}
		}
		// validations
		if (!$this->validate_script($j->script)) 		  { return false; }
		if (!$this->needs_execution_weekday($j->weekday)) { return false; }
		if (!$this->needs_execution_day($j->day)) 		  { return false; }
		if (!$this->needs_execution_hour($j->hour))   	  { return false; }
		if (!$this->needs_execution_minute($j->minute))   { return false; }
		// ok
		return true;
	}

	private function validate_script ($script = "") {
		return in_array($script, $this->valid_cronjob_scripts) ? true : false;
	}

	private function needs_execution_weekday ($weekday = "") {
		return ($weekday == "*" || date('w')==$weekday) ? true : false;
	}

	private function needs_execution_day ($day = "") {
		return ($day == "*" || date('d')==$day) ? true : false;
	}

	private function needs_execution_hour ($hour = "") {
		if ($hour == "*") 	{ return true; }
		if (date('G')==$hour) { return true; }
		// */
		if (strpos($hour, "*/")!==false) {
			$hour_divider = str_replace("*/", "", $hour);
			$divided = (int) date('G')/$hour_divider;
			if (floor($divided) == $divided) {
				return true;
			}
		}
		// default not valie
		return false;
	}

	private function needs_execution_minute ($minute = "") {
		$nowminute = (int) date('i');

		if ($minute == "*") 	{ return true; }
		if ($nowminute==$minute) { return true; }
		// */
		if (strpos($minute, "*/")!==false) {
			$minute_divider = str_replace("*/", "", $minute);
			$divided = $nowminute/$minute_divider;
			if (floor($divided) == $divided) {
				return true;
			}
		}
		// default not valie
		return false;
	}

	public function rand ($min = 0, $max = 60, $step = 5) {
	    // Generate a random number between 0 and 12
	    $randomNumber = rand($min, ($max-$step)/$step);
	    // multiply by step
	    $randomNumber *= $step;
	    // return
	    return $randomNumber;
	}
}
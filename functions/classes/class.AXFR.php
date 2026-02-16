<?php

/**
 *
 * Helper class for zone transfers
 *
 *
 */
class AXFR
{

	/**
	 * Nameservers to use for AXFR
	 * @var array
	 */
	private $nameservers = [];

	/**
	 * Name of zone
	 * @var string
	 */
	private $zone_name = "";

	/**
	 * Id of zone
	 * @var int
	 */
	private $zone_id = 0;

	/**
	 * Record types to transfer from zone
	 * @var [type]
	 */
	private $valid_record_types = ["A", "MX"];

	/**
	 * Connect via TCP
	 *
	 * 	true: tcp
	 *  false: udp
	 *
	 * @var bool
	 */
	private $use_tcp = true;

	/**
	 * NAme of tsig
	 * @var string
	 */
	private $tsig_name = "";

	/**
	 * TSIG key
	 * @var string
	 */
	private $tsig = "";

	/**
	 * Reges to post-process received records - include regex
	 * @var string
	 */
	private $regex_include = "";

	/**
	 * Reges to post-process received records - exclude regex
	 * @var string
	 */
	private $regex_exclude = "";

	/**
	 * Database link
	 * @var bool
	 */
	private $Database = false;

	/**
	 * Result object
	 * @var Result
	 */
	private $Result = false;

	/**
	 * DNS AXFR link
	 * @var bool
	 */
	private $link = false;

	/**
	 * Result from AXFR query
	 * @var array
	 */
	private $result = [
		"success" => false,
		"error" => "",
		"values" => []
	];

	/**
	 * Processed diff of existing, new and records to be removed
	 * @var array
	 */
	public $records = [
		"old_records" => [],
		"axfr_records" => [],
		"removed_records" => [],
		"new_records" => []
	];


	/**
	 * Constructoir
	 * @method __construct
	 * @param  Database_PDO $Database
	 */
	public function __construct(Database_PDO $Database)
	{
		// Save database object
		$this->Database = $Database;
		// Results
		$this->Result = new Result();
		// include Net_DNS2
		ini_set("include_path", dirname(__FILE__) . "/../assets/Net_DNS2");
		require_once(dirname(__FILE__) . "/../assets/Net_DNS2/Net/DNS2.php");
	}

	/**
	 * Execute transfer
	 * @method execute
	 * @return void
	 */
	public function execute()
	{
		try {
			// link
			$this->set_link();
			// make query
			$result = $this->link->query($this->zone_name, 'AXFR');
			// we are ok
			$this->result['success'] = true;
			// check response
			if (isset($result->answer)) {
				foreach ($result->answer as $rr) {
					if (in_array($rr->type, $this->valid_record_types)) {
						// save to result
						$this->result["values"][] = $rr;
					}
				}
			}
		}
		catch (exception $e) {
			$this->result['error'] = $e->getMessage();
		}

		// filter regexes
		$this->filter_results_include_regex();
		$this->filter_results_exclude_regex();
	}

	/**
	 * Set connection link for AXFR
	 * @method set_link
	 */
	private function set_link()
	{
		if ($this->link === false) {
			$this->link = new Net_DNS2_Resolver([
				'nameservers' => $this->nameservers,
				'use_tcp' => $this->use_tcp
			]);
		}

		// sign request if needed
		if (strlen($this->tsig) > 0) {
			$this->link->signTSIG($this->tsig_name, $this->tsig);
		}
	}

	/**
	 * Set nameservers
	 * @method set_nameservers
	 * @param  array $nameservers
	 */
	public function set_nameservers($nameservers = [])
	{
		if (is_array($nameservers)) {
			if (sizeof($nameservers) > 0) {
				$this->nameservers = $nameservers;
			}
		}
	}

	/**
	 * Set name of zone to query
	 * @method set_zone_name
	 * @param  string $name
	 */
	public function set_zone_name($name = "")
	{
		if (strlen($name) > 0) {
			$this->zone_name = $name;
		}
	}

	/**
	 * Set valid record types for result processing
	 * @method set_valid_types
	 * @param  array $types
	 */
	public function set_valid_types($types = [])
	{
		if (is_array($types)) {
			if (sizeof($types) > 0) {
				$this->valid_record_types = $types;
			}
		}
	}

	/**
	 * Connect using tcp
	 * @method set_tcp
	 * @param  bool $tcp
	 */
	public function set_tcp($tcp = true)
	{
		if (is_bool($tcp)) {
			$this->use_tcp = $tcp;
		}
	}

	/**
	 * Set tsig parameters
	 * @method set_tsig
	 * @param  string $name
	 * @param  string $tsig
	 */
	public function set_tsig($name = "", $tsig = "")
	{
		if (strlen($name) > 0) {
			$this->tsig_name = $name;
			$this->tsig = $tsig;
		}
	}

	/**
	 * Set include and exclude regexes
	 * @method set_regexes
	 * @param  string $regex_include
	 * @param  string $regex_exclude
	 */
	public function set_regexes($regex_include = "", $regex_exclude = "")
	{
		$this->regex_include = $regex_include;
		$this->regex_exclude = $regex_exclude;
	}

	/**
	 * Filter received results through receive regex
	 * @method filter_results_include_regex
	 * @return void
	 */
	private function filter_results_include_regex()
	{
		if (strlen($this->regex_include) > 0) {
			foreach ($this->result["values"] as $k => $rr) {
				if (!preg_match($this->regex_include, $rr->name) && !preg_match($this->regex_include, $rr->address)) {
					unset($this->result["values"][$k]);
				}
			}
		}
	}

	/**
	 * Filter received results through receive regex
	 * @method filter_results_include_regex
	 * @return void
	 */
	private function filter_results_exclude_regex()
	{
		if (strlen($this->regex_exclude) > 0) {
			foreach ($this->result["values"] as $k => $rr) {
				if (preg_match($this->regex_exclude, $rr->name) || preg_match($this->regex_exclude, $rr->address)) {
					unset($this->result["values"][$k]);
				}
			}
		}
	}

	/**
	 * Return records from AXFR
	 * @method get_records
	 * @return [type]
	 */
	public function get_records()
	{
		return $this->result;
	}





	public function calculate_diffs($zone_id = 0, $check_ip = 0)
	{
		// save zoneid
		$this->zone_id = $zone_id;
		// get existing
		$this->get_existing_zone_records();
		// axfr records
		$this->get_diff_axfr_records($check_ip);
		// new records
		$this->get_diff_new_records();
		// existing records
		$this->get_diff_old_records();
	}

	/**
	 * Get existing records from database
	 * @method get_existing_zone_records
	 * @return void
	 */
	private function get_existing_zone_records()
	{
		// get existing
		try {
			$records = $this->Database->getObjectsQuery("select hostname as name from hosts where z_id = ?", [$this->zone_id]);
		}
		catch (exception $e) {
		}
		// save
		if (sizeof($records) > 0) {
			foreach ($records as $r) {
				$this->records['old_records'][] = $r->name;
			}
		}
	}

	/**
	 * PRocess result
	 * @method get_diff_axfr_records
	 * @param  int $check_ip
	 * @return void
	 */
	private function get_diff_axfr_records($check_ip = 0)
	{
		// AXFR received records
		if (sizeof($this->result['values']) > 0) {
			foreach ($this->result['values'] as $rr) {
				$this->records['axfr_records'][] = $rr->name;
				$this->records['axfr_records'][] = $rr->cname;
				if ($check_ip == "1")
					$this->records['axfr_records'][] = $rr->address;
			}
		}
		// make unique
		$this->records['axfr_records'] = array_filter(array_unique($this->records['axfr_records']));
	}

	/**
	 * Set newly discovered records
	 * @method get_diff_new_records
	 * @return void
	 */
	private function get_diff_new_records()
	{
		// check for new ones
		foreach ($this->records['axfr_records'] as $r) {
			if (!in_array($r, $this->records['old_records'])) {
				$this->records['new_records'][] = $r;
			}
		}
	}

	/**
	 * Set records to be removed
	 * @method get_diff_old_records
	 * @return void
	 */
	private function get_diff_old_records()
	{
		// check which to remove
		foreach ($this->records['old_records'] as $r) {
			if (!in_array($r, $this->records['axfr_records'])) {
				$this->records['removed_records'][] = $r;
			}
		}
	}

	/**
	 * set diff for new records
	 * @method create_new_records
	 * @return void
	 */
	public function create_new_records()
	{
		if (sizeof($this->records['new_records']) > 0) {
			// pgid
			$pgid = $this->get_pg_id();
			// loop
			foreach ($this->records['new_records'] as $r) {
				try {
					$this->Database->insertObject("hosts", ["z_id" => $this->zone_id, "hostname" => $r, "pg_id" => $pgid, "last_change" => date("Y-m-d H:i:s")]);
				}
				catch (exception $e) {
					$this->Result->show("danger", $e->getMessage(), false, false, false);
				}
			}
		}
	}

	/**
	 * Delete records from database that are not in AXFR anymore
	 * @method delete_records
	 * @return void
	 */
	public function delete_records()
	{
		if (sizeof($this->records['removed_records']) > 0) {
			foreach ($this->records['removed_records'] as $r) {
				try {
					$this->Database->runQuery("delete from hosts where z_id = ? and hostname = ? collate utf8_general_ci", [$this->zone_id, $r]);
				}
				catch (exception $e) {
					$this->Result->show("danger", $e->getMessage(), false, false, false);
				}
			}
		}
	}

	/**
	 * Get ID for port-group, defaults to pg_ssl
	 * @method get_pg_id
	 * @return [type]
	 */
	private function get_pg_id()
	{
		try {
			$pg = $this->Database->getObjectQuery("select pg.id from zones as z, ssl_port_groups as pg where z.id = ? and z.t_id = pg.t_id and pg.name = 'pg_ssl'", [$this->zone_id]);
		}
		catch (exception $e) {
		}
		// return port-group
		return $pg->id;
	}
}
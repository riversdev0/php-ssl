<?php

/**
 * Class to work with zones
 */
class Zones extends Common
{

	/**
	 * Database holder
	 * @var Database_PDO|bool
	 */
	private $Database = false;

	/**
	 * User object
	 * @var object|bool
	 */
	private $user = false;



	/**
	 * Constructor
	 * @method __construct
	 * @param  Database_PDO $Database
	 * @param  object $user
	 */
	public function __construct(Database_PDO $Database, $user = false)
	{
		// Save database object
		$this->Database = $Database;
		// user
		if (is_object($user)) {
			$this->user = $user;
		}
	}

	/**
	 * Get available agents for tenant
	 * @method get_agents
	 * @param  int $tenant_id
	 * @return array
	 */
	public function get_tenant_agents($tenant_id = 0)
	{
		try {
			$agents = $this->Database->getObjectsQuery("select * from agents where id = 1 or t_id = ?", [$tenant_id]);
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		return $agents;
	}

	/**
	 * Returns all zones for admin and tenant zones for non-admin
	 * @method get_all
	 * @return array
	 */
	public function get_all()
	{
		// fetch
		// when impersonating, private zones are never shown (privacy must survive impersonation)
		$impersonating = isset($_SESSION['impersonate_original']);
		try {
			if ($this->user->admin == "1") {
				$zones = $impersonating
					? $this->Database->getObjectsQuery("select *,t.name as tenant_name,z.name as name,z.id as id, z.description as description,z.t_id as t_id, a.name as agname from zones as z, tenants as t, agents as a where z.t_id = t.id and z.agent_id = a.id and z.private_zone_uid IS NULL order by z.name asc")
					: $this->Database->getObjectsQuery("select *,t.name as tenant_name,z.name as name,z.id as id, z.description as description,z.t_id as t_id, a.name as agname from zones as z, tenants as t, agents as a where z.t_id = t.id and z.agent_id = a.id and (z.private_zone_uid IS NULL OR z.private_zone_uid = ?) order by z.name asc", [$this->user->id]);
			}
			else {
				$zones = $impersonating
					? $this->Database->getObjectsQuery("select *,t.name as tenant_name,z.name as name,z.id as id, z.description as description,z.t_id as t_id, a.name as agname from zones as z, tenants as t, agents as a where z.t_id = t.id and z.agent_id = a.id and z.t_id = ? and z.private_zone_uid IS NULL order by z.name asc", [$this->user->t_id])
					: $this->Database->getObjectsQuery("select *,t.name as tenant_name,z.name as name,z.id as id, z.description as description,z.t_id as t_id, a.name as agname from zones as z, tenants as t, agents as a where z.t_id = t.id and z.agent_id = a.id and z.t_id = ? and (z.private_zone_uid IS NULL OR z.private_zone_uid = ?) order by z.name asc", [$this->user->t_id, $this->user->id]);
			}
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// reindex
		if (sizeof($zones) > 0) {
			$zones_new = [];
			foreach ($zones as $z) {
				$zones_new[$z->id] = $z;
			}
			$zones = $zones_new;
		}
		// return
		return $zones;
	}

	/**
	 * Returns all hosts inside zone
	 * @method get_zone_hosts
	 * @param  int $zone_id
	 * @return array
	 */
	public function get_zone_hosts($zone_id)
	{
		// fetch
		try {
			$hosts = $this->Database->getObjectsQuery("select *,h.id as id,z.name as zone_name, z.t_id as t_id from zones as z, hosts as h, tenants as t where h.z_id = z.id and z.t_id = t.id and z.id = ? order by h.hostname asc", [$zone_id]);
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// return
		return $hosts;
	}

	/**
	 * Search hosts
	 * @method search_zone_hosts
	 * @param  string $search_string
	 * @return array
	 */
	public function search_zone_hosts($search_string = "")
	{
		// fetch
		$impersonating = isset($_SESSION['impersonate_original']);
		try {
			if ($this->user->admin == "1") {
				$pz_clause = $impersonating ? "and z.private_zone_uid is null" : "and (z.private_zone_uid is null or z.private_zone_uid = ".(int)$this->user->id.")";
				$hosts = $this->Database->getObjectsQuery("select *,h.id as id,z.name as zone_name from zones as z, hosts as h, tenants as t
				                                          	where h.z_id = z.id and z.t_id = t.id $pz_clause
				                                          	and (h.hostname like '%" . $search_string . "%' or h.ip like '%" . $search_string . "%')
				                                          	order by h.hostname asc");
			}
			else {
				$pz_clause = $impersonating ? "and z.private_zone_uid is null" : "and (z.private_zone_uid is null or z.private_zone_uid = ".(int)$this->user->id.")";
				$hosts = $this->Database->getObjectsQuery("select *,h.id as id,z.name as zone_name from zones as z, hosts as h, tenants as t
				                                          	where h.z_id = z.id and z.t_id = t.id and z.href = ? $pz_clause
				                                          	and (h.hostname like '%" . $search_string . "%' or h.ip like '%" . $search_string . "%')
				                                          	order by h.hostname asc", [$this->user->t_id]);
			}
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// return
		return $hosts;
	}

	/**
	 * Returns zone details
	 * @method get_zone
	 * @param  string $href
	 * @param  string $zone_name
	 * @return object
	 */
	public function get_zone($href = "", $zone_name = "")
	{
		if (is_numeric($zone_name)) {
			return $this->Database->getObjectQuery("select *,t.name as tenant_name,z.name as name,z.description as z_description,a.name as agname, z.id as id, z.t_id as t_id from zones as z, tenants as t, agents as a where z.t_id = t.id and z.agent_id = a.id and t.href = ? and z.id = ?  order by z.name asc", [$href, $zone_name]);
		}
		else {
			return $this->Database->getObjectQuery("select *,t.name as tenant_name,z.name as name,z.description as z_description,a.name as agname, z.id as id, z.t_id as t_id from zones as z, tenants as t, agents as a where z.t_id = t.id and z.agent_id = a.id and t.href = ? and z.name = ? order by z.name asc", [$href, $zone_name]);
		}
	}

	public function get_zone_raw($zone_id = 0)
	{
		return $this->Database->getObjectQuery("select * from zones where id = ?", [$zone_id]);
	}

	/**
	 * Makes sure added hostname is inside domain !
	 * @method is_host_inside_domain
	 * @param  string $hostname
	 * @param  string $domainname
	 * @return bool
	 */
	public function is_host_inside_domain($hostname = "", $domainname = "")
	{
		$dn_arr = array_reverse(explode(".", $domainname));
		$hn_arr = array_reverse(explode(".", $hostname));
		// check
		foreach ($dn_arr as $index => $var) {
			if ($hn_arr[$index] !== $var) {
				return false;
			}
		}
		// all good
		return true;
	}

	/**
	 * Count how many certificates are present is some zone
	 * @method count_zone_certs
	 * @param  int $zone_id
	 * @return int
	 */
	public function count_zone_certs($zone_id = 0)
	{
		// fetch
		try {
			$cnt = $this->Database->getObjectQuery("select count(distinct(c_id)) as cnt from hosts where z_id = ?", [$zone_id]);
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// return
		return $cnt->cnt;
	}

	/**
	 * Checks when last chekc for host occured in zone
	 * @method get_last_check
	 * @param  int $zone_id
	 * @return string
	 */
	public function get_last_check($zone_id = 0)
	{
		// fetch
		try {
			$last = $this->Database->getObjectQuery("select MAX(last_check) as last from hosts where z_id = ?", [$zone_id]);
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// return
		return $last->last;
	}

	/**
	 * Returns host details by id
	 * @method get_host
	 * @param  string $host_id
	 * @return object
	 */
	public function get_host($host_id = "")
	{
		// fetch
		try {
			$hosts = $this->Database->getObject("hosts", $host_id);
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// return
		return $hosts;
	}

	/**
	 * Returns host details by hostname and zone id, including current certificate and tenant data
	 * @method get_host_with_certificate
	 * @param  string $hostname
	 * @param  int    $zone_id
	 * @return object|null
	 */
	public function get_host_with_certificate($hostname = "", $zone_id = 0)
	{
		try {
			$host = $this->Database->getObjectQuery("
				SELECT h.*, z.name as zone_name, z.t_id as t_id, t.href as tenant_href, t.name as tenant_name,
				       t.recipients as tenant_recipients, c.certificate, c.serial as cert_serial
				FROM hosts h
				LEFT JOIN zones z ON h.z_id = z.id
				LEFT JOIN tenants t ON z.t_id = t.id
				LEFT JOIN certificates c ON h.c_id = c.id
				WHERE h.hostname = ? AND z.id = ?
			", [$hostname, $zone_id]);
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		return $host;
	}

	/**
	 * Returns previous certificate for a host
	 * @method get_host_old_certificate
	 * @param  int $cert_id
	 * @return object|null
	 */
	public function get_host_old_certificate($cert_id = 0)
	{
		try {
			$cert = $this->Database->getObjectQuery("SELECT * FROM certificates WHERE id = ?", [$cert_id]);
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		return $cert;
	}
}
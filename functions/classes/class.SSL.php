<?php

/**
 *
 * Class to scan certificates
 *
 */
class SSL extends Common
{

	/**
	 * Database holder
	 * @var bool|Database_PDO
	 */
	private $Database = false;

	/**
	 * Log object
	 * @var bool
	 */
	private $Log = false;

	/**
	 * SSL stream options array
	 * @var array
	 */
	private $stream_options = [];

	/**
	 * Errors
	 * @var array
	 */
	public $errors = [];

	/**
	 * List of all ports
	 * @var array
	 */
	private $all_ports = [];

	/**
	 * List of port groups
	 * @var array
	 */
	private $all_ports_groups = [];

	/**
	 * Array of Certificates existing in database
	 * @var array
	 */
	private $existing_db_certs = [];

	/**
	 * Scan timeout
	 * @var int
	 */
	private $timeout = 2;

	/**
	 * Stream
	 * @var bool
	 */
	private $stream = false;

	/**
	 * Hostname
	 * @var string
	 */
	private $hostname = "";

	/**
	 * Result
	 * @var bool
	 */
	private $result = false;



	/**
	 * Constructor
	 * @method __construct
	 * @param  Database_PDO $Database
	 */
	public function __construct(Database_PDO $Database)
	{
		// Save database object
		$this->Database = $Database;
		// set default options
		$this->set_stream_options();
		// fetch all ports
		$this->fetch_all_ports();
	}

	/**
	 * Gets all ports from database and reconfigures them
	 * @method fetch_all_ports
	 * @return bool]
	 */
	private function fetch_all_ports()
	{
		// if set exit
		if (sizeof($this->all_ports) > 0)
			return true;

		// Fetch all ports
		try {
			$ports = $this->Database->getObjectsQuery("select * from `ssl_port_groups`");

			if (sizeof($ports) > 0) {
				foreach ($ports as $p) {
					$ports = explode(",", $p->ports);
					$this->all_ports[$p->t_id][$p->id] = $ports;
				}
			}
			return true;
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
	}

	/**
	 * Returns all port group from database
	 * @method get_all_port_groups
	 * @return array|bool
	 */
	public function get_all_port_groups()
	{
		// if set exit
		if (sizeof($this->all_ports_groups) > 0) {
			return $this->all_ports_groups;
		}
		// Fetch all ports
		try {
			$ports = $this->Database->getObjectsQuery("select * from `ssl_port_groups`");
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// save
		if (is_array($ports)) {
			foreach ($ports as $p) {
				$ports = explode(",", $p->ports);
				$this->all_ports_groups[$p->t_id][$p->id]['name'] = $p->name;
				$this->all_ports_groups[$p->t_id][$p->id]['ports'] = $ports;
			}
		}
		return $this->all_ports_groups;
	}





	/**
	 *
	 * SSL cert fetching
	 *
	 */

	/**
	 * Sets default stream options
	 * @method set_stream_options
	 */
	private function set_stream_options()
	{
		// ssl options
		$this->stream_options['ssl'] = [
			'capture_peer_cert_chain' => true,
			'capture_peer_cert' => true,
			'allow_self_signed' => true,
			'SNI_enabled' => true,
			'verify_peer' => false,
			'capath' => '/etc/ssl/certs'
		];
	}

	/**
	 * Initis ssl stream
	 * @method init_stream
	 * @return void
	 */
	private function init_stream()
	{
		$this->stream = stream_context_create($this->stream_options);
	}

	/**
	 * Saves errors
	 * @method php_error_handler
	 * @param  int $errno
	 * @param  string $errstr
	 * @return void
	 */
	public function php_error_handler($errno = 0, $errstr = "")
	{
		$this->errors[] = "Unable to establish connection to host (err $errno : $errstr)";
	}

	/**
	 * Fetches cert from website - local or remote
	 *
	 *
	 * @method fetch_website_certificate
	 * @param  object $host
	 * @param  string $execution_time
	 * @param  int $tenant_id
	 * @return array|false
	 */
	public function fetch_website_certificate($host, $execution_time = NULL, $tenant_id = 0)
	{
		// validate hostname
		if ($this->validate_hostname($host->hostname) === false) {
			$this->errors[] = "Invalid hostname";
			return false;
		}
		// validate port group
		if (!@array_key_exists($host->pg_id, $this->all_ports[$tenant_id])) {
			$this->errors[] = "Invalid port group index";
			return false;
		}
		// default time
		if ($execution_time == NULL)
			$execution_time = date("Y-m-d H:i:s");

		//
		// local fetch
		//
		if ($host->agent_id == 1 || is_null($host->agent_id)) {
			// update host last check, resolve IP locally
			$this->update_host_last_check($host->host_id, $this->resolve_ip($host->hostname), $execution_time);

			// init stream
			$this->init_stream();

			// check old port first !
			if (is_numeric($host->port)) {
			// -- here
			}

			// loop through ports
			foreach ($this->all_ports[$tenant_id][$host->pg_id] as $p) {
				// stream_socket_client may create PHP WARNINGS before socket is created and $errstr is set
				set_error_handler([&$this, 'php_error_handler']);
				// conect and get result
				$client = stream_socket_client("ssl://" . $host->hostname . ":" . $p, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $this->stream);
				// process result
				$certificate = $this->process_fetch_result($errno, $errstr, $execution_time, $p, $client);
				// if not false quit, we found something
				if ($certificate !== false) {
					return $certificate;
				}
				// restore error handler
				restore_error_handler();
			}
			// error
			$this->errors[] = "Failed to connect to SSL on all ports";
			// return cert
			return false;
		}
		//
		// Remote - agent
		//
		else {
			// init agent (we only need URL which is present in $host object)
			$Agent = new Agent($host);
			// add host/port to agent
			$Agent->add_host_port($host->hostname, $this->all_ports[$tenant_id][$host->pg_id]);
			// execute
			$scan_res = $Agent->scan();

			// get cert
			$fetched_cert = $Agent->get_result();

			// result
			if (is_object($fetched_cert)) {
				// did we receive successfull result ?
				if ($fetched_cert->success === false) {
					// validate ip
					$ip = $this->validate_ip($fetched_cert->ip) ? $fetched_cert->ip : NULL;
					// update last check
					$this->update_host_last_check($host->host_id, $ip, $execution_time);
					// set error
					$this->errors[] = "API error: " . $fetched_cert->error;
					$this->errors[] = "API response code: http " . $fetched_cert->code;
					// error
					return false;
				}
				else {
					// update host last check, get IP from remote agent
					$this->update_host_last_check($host->host_id, $fetched_cert->ip, $execution_time);
					// result
					return (array)$fetched_cert;
				}
			}
			else {
				// update host last check, if IP is provided save it
				$ip_addr = isset($fetched_cert->ip) ? $fetched_cert->ip : NULL;
				$this->update_host_last_check($host->host_id, $ip_addr, $execution_time);
				// save error
				$this->errors[] = $fetched_cert;
				// return
				return false;
			}
		}
	}

	/**
	 * Fetches certificate directly from GUI
	 * @method fetch_website_certificate_single
	 * @param  string $url
	 * @return bool|array
	 */
	public function fetch_website_certificate_single($url)
	{
		// validate url
		if ($this->validate_url($url) === false) {
			$this->errors[] = "Invalid URL";
			return false;
		}

		// get hostname and port
		$url_arr = parse_url($url);
		$url_arr['port'] = $url_arr['scheme'] == "https" && !isset($url_arr['port']) ? 443 : $url_arr['port']; // add default https port
		$url_arr['port'] = $url_arr['scheme'] == "http" && !isset($url_arr['port']) ? 80 : $url_arr['port']; // add default https port

		// init stream
		$this->init_stream();

		// save host for later resolving
		$this->hostname = $url_arr['host'];

		// default time
		$execution_time = date("Y-m-d H:i:s");

		// stream_socket_client may create PHP WARNINGS before socket is created and $errstr is set
		set_error_handler([&$this, 'php_error_handler']);
		// conect and get result
		$client = stream_socket_client("ssl://" . $url_arr['host'] . ":" . $url_arr['port'], $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $this->stream);
		// process result
		$certificate = $this->process_fetch_result($errno, $errstr, $execution_time, $url_arr['port'], $client);

		// if not false quit, we found something
		if ($certificate !== false) {
			return $certificate;
		}
		// restore error handler
		restore_error_handler();

		// error
		$this->errors[] = "Failed to connect to SSL on port " . $url_arr['port'];
		// return cert
		return false;
	}

	/**
	 * Processes fetched certificate
	 *
	 * @method process_fetch_result
	 * @param  int $errno
	 * @param  string $errstr
	 * @param  string $execution_time
	 * @param  int $port
	 * @return array|false
	 */
	private function process_fetch_result($errno, $errstr, $execution_time, $port, $client)
	{
		// check stream
		if ($this->stream === false && strlen($errstr) == 0) {
			$this->errors[] = "Unable to establish socket connection";
			return false;
		}
		// check for errors, return false
		elseif (strlen($errstr) > 0) {
			//$this->errors[] = $errstr;
			return false;
		}
		// ok
		else {
			// get
			$cont = stream_context_get_params($this->stream);

			// metadata - TLS version
			$metadata = stream_get_meta_data($client);

			// get cert and export it
			$peer_cert = $cont["options"]["ssl"]["peer_certificate"];
			$peer_cert_chain = $cont["options"]["ssl"]["peer_certificate_chain"];

			if (@openssl_x509_export($peer_cert, $certinfo) === false) {
				$this->errors[] = "Could not fetch peer certificate";
				return false;
			}
			else {
				// chain
				$certinfo_chain = "";
				foreach ($peer_cert_chain as $int_cert) {
					if (@openssl_x509_export($int_cert, $output) !== false)
						$certinfo_chain .= $output;
				}
				// parse
				$peer_cert_parsed = openssl_x509_parse($peer_cert);
				$valid_to = date("Y-m-d H:i:s", $peer_cert_parsed['validTo_time_t']);
				// insert
				return [
					"serial" => $peer_cert_parsed['serialNumber'],
					"certificate" => trim($certinfo),
					"chain" => trim($certinfo_chain),
					"expires" => $valid_to,
					"created" => $execution_time,
					"port" => $port,
					"ip" => $this->resolve_ip($this->hostname),
					"tls_proto" => $metadata['crypto']['cipher_version']
				];
			}
		}
	}


	/**
	 *
	 * Certificate methods
	 *
	 */

	/**
	 * Update database certificate and returns ID
	 *
	 * @method update_db_certificate
	 * @param  array $certificate
	 * @param  int $tenant_id
	 * @param  int $zone_id
	 * @param  string $execution_time
	 * @return int|void
	 */
	public function update_db_certificate($certificate = [], $tenant_id = 0, $zone_id = 0, $execution_time)
	{

		try {
			// serve from cache ?
			if (isset($this->existing_db_certs[$tenant_id][$zone_id])) {
				if (array_key_exists($certificate['serial'], $this->existing_db_certs[$tenant_id][$zone_id])) {
					return $this->existing_db_certs[$tenant_id][$zone_id][$certificate['serial']]['id'];
				}
			}
			// try to find cert
			$db_cert = $this->Database->getObjectQuery("select * from certificates where serial = ? and z_id = ? and t_id = ?", [$certificate['serial'], $zone_id, $tenant_id]);

			// not found ?
			if ($db_cert == null) {
				// Extract AKI (issuer's SKI) from leaf cert for CA linkage
				$leaf_parsed = @openssl_x509_parse($certificate['certificate']);
				$aki = trim(str_replace('keyid:', '', $leaf_parsed['extensions']['authorityKeyIdentifier'] ?? ''));

				// insert new
				try {
					// try to insert, if we get error because of threading it means one was entered in the meantime, so recheck !
					$new_cert_id = $this->Database->insertObject("certificates", ["serial" => $certificate['serial'], "certificate" => $certificate['certificate'], "expires" => $certificate['expires'], "chain" => $certificate['chain'], "aki" => ($aki ?: null), "z_id" => $zone_id, "t_id" => $tenant_id, "created" => $execution_time]);
				}
				catch (Exception $e) {
					// do nothing
					die($e->getMessage());
				}
				// fetch now that it is created
				$db_cert = $this->Database->getObjectQuery("select * from certificates where id = ?", [$new_cert_id]);

				// Write log :: object, object_id, tenant_id, user_id, action, public, text
				if ($this->Log === false)
					$this->Log = new Log($this->Database);
				$this->Log->write("certificates", $new_cert_id, $tenant_id, null, "add", true, "New certificate imported with serial " . $certificate['serial'], NULL, json_encode($db_cert));
			}

			// add to cache
			$this->existing_db_certs[$tenant_id][$zone_id][$certificate['serial']] = (array)$db_cert;
			// return id
			return $db_cert->id;

		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
	}

	/**
	 * Extracts CA certificates from a chain PEM and upserts them into the cas table.
	 * @method upsert_chain_cas
	 * @param  string $chain_pem  Full chain PEM (leaf first, then intermediates, then root)
	 * @param  int    $tenant_id
	 */
	public function upsert_chain_cas($chain_pem, $tenant_id)
	{
		if (empty($chain_pem)) return;

		$delimiter = "-----BEGIN CERTIFICATE-----\n";
		$parts = array_values(array_filter(explode($delimiter, $chain_pem)));
		// need at least 2 entries (leaf + one CA)
		if (count($parts) < 2) return;

		// reverse so [0]=root, last=leaf; remove the leaf (already in certificates table)
		$parts = array_reverse($parts);
		array_pop($parts);

		$prev_ca_id = null;

		foreach ($parts as $raw) {
			$pem    = $delimiter . $raw;
			$parsed = @openssl_x509_parse($pem);
			if (!$parsed) continue;

			// only CA certs
			if (strpos($parsed['extensions']['basicConstraints'] ?? '', 'CA:TRUE') === false) continue;

			$ski = trim($parsed['extensions']['subjectKeyIdentifier'] ?? '');
			if (empty($ski)) continue;

			$name    = $parsed['subject']['CN'] ?? $parsed['subject']['O'] ?? 'Unknown CA';
			$subject = $this->build_subject_string($parsed['subject'] ?? []);
			$expires = date('Y-m-d H:i:s', $parsed['validTo_time_t']);

			$existing = $this->Database->getObjectQuery(
				"SELECT id FROM cas WHERE ski = ? AND t_id = ?",
				[$ski, (int)$tenant_id]
			);

			if ($existing) {
				$ca_id = (int)$existing->id;
				$this->Database->updateObject("cas", [
					"id"           => $ca_id,
					"expires"      => $expires,
					"parent_ca_id" => $prev_ca_id,
				]);
			} else {
				$ca_id = (int)$this->Database->insertObject("cas", [
					"t_id"         => (int)$tenant_id,
					"name"         => $name,
					"subject"      => $subject,
					"ski"          => $ski,
					"expires"      => $expires,
					"certificate"  => $pem,
					"source"       => "auto",
					"parent_ca_id" => $prev_ca_id,
				]);
			}

			$prev_ca_id = $ca_id;
		}
	}

	/**
	 * Builds a subject string from a parsed subject array.
	 */
	private function build_subject_string($subject)
	{
		$parts = [];
		foreach (['CN', 'O', 'OU', 'C', 'ST', 'L'] as $k) {
			if (!empty($subject[$k])) $parts[] = "$k=" . $subject[$k];
		}
		return implode(', ', $parts);
	}

	/**
	 * Certificate chain processing
	 * @method process_certificate_chain
	 * @param  string $chain
	 * @return array
	 */
	public function process_certificate_chain($chain)
	{
		// result
		$result = [];
		// certificate delimiter
		$delimiter = "-----BEGIN CERTIFICATE-----\n";

		// get chain to array
		$chain_arr = array_reverse(array_values(array_filter(explode($delimiter, $chain))));

		// has ca ?
		$has_ca = false;

		// per chain process
		foreach ($chain_arr as $index => $a) {
			// save raw and processed cert
			$result[$index]['raw'] = $a;
			$result[$index]['certificate'] = openssl_x509_parse($delimiter . $a);
			$result[$index]['errors'] = [];
		}

		// now we validate!
		for ($m = 0; $m < sizeof($result); $m++) {

			// no ca ?
			if (sizeof($result) == 1) {
				$result[$m]['errors']['authorityKeyIdentifier'] = _("Certificate chain missing");
			}

			// expired ?
			if (date("Y-m-d H:i:s", $result[$m]['certificate']['validTo_time_t']) < date("Y-m-d H:i:s")) {
				// text
				$result[$m]['errors']['validto'] = _("Certificate expired");
			}

			// check if it has childs in chain
			if (isset($result[$m + 1])) {
				// make sure current issued child certificate
				if ($result[$m]['certificate']['extensions']['subjectKeyIdentifier'] != trim(str_replace("keyid:", "", $result[$m + 1]['certificate']['extensions']['authorityKeyIdentifier']))) {
					$result[$m + 1]['errors']['authorityKeyIdentifier'] = _("Certificate not signed by parent");
				}
				// can current sign certificates ? :)
				if (strpos($result[$m]['certificate']['extensions']['basicConstraints'], "CA:TRUE") === false) {
					$result[$m]['errors']['basicConstraints'] = _("Certificate not allowed to issue certificates");
					$result[$m + 1]['errors']['basicConstraints'] = _("Parent certificate not allowed to issue certificates");
				}
				// pathlen ?
				if (strpos($result[$m]['certificate']['extensions']['basicConstraints'], "pathlen:0") !== false && strpos($result[$m + 1]['certificate']['extensions']['basicConstraints'], "CA:TRUE") !== false) {
					$result[$m]['errors']['basicConstraints'] = _("Child certificate is not allowed to issue certificates");
				}
			}
		}

		// return result
		return $result;
	}


	/**
	 *
	 * Host update methods
	 *
	 */

	/**
	 * Update last check time for host
	 *
	 * @method update_host_last_check
	 * @param  int $host_id
	 * @param  string $ip
	 * @param  string $execution_time
	 * @return void
	 */
	private function update_host_last_check($host_id = 0, $ip = NULL, $execution_time = NULL)
	{
		// IP must not be hostname !
		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			$ip = NULL;
		}
		// insert
		try {
			$this->Database->runQuery("update hosts set last_check = ?, ip = ? where id = ?", [$execution_time, $ip, $host_id]);
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
	}

	/**
	 * Assigns certificate to host
	 * @method assign_host_certificate
	 * @param  object $host
	 * @param  string $ip
	 * @param  int $port
	 * @param  object|bool $certificate
	 * @param  string $tls_version
	 * @param  string $execution_time
	 * @param  int|null $user_id
	 * @return void
	 */
	public function assign_host_certificate($host = null, $ip = null, $port = 0, $certificate = false, $tls_version = "", $execution_time = NULL, $user_id = null)
	{
		try {
			$this->Database->runQuery("update hosts set c_id_old = c_id, c_id = ?, port = ?, ip = ?, last_change = ?, tls_version = ? where id = ?", [$certificate->id, $port, $ip, $execution_time, $tls_version, $host->host_id]);
			// Write log :: object, object_id, tenant_id, user_id, action, public, text
			if ($this->Log == false) {
				$this->Log = new Log($this->Database);
			}
			$this->Log->write("hosts", $host->host_id, $host->t_id, $user_id, "refresh", true, "New certificate assigned to host " . $host->hostname. " with serial ".$certificate->serial, json_encode($host->c_id_old), json_encode($host->c_id));

		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
	}

	/**
	 * Resolves IP from hostname
	 * @method resolve_ip
	 * @param  string $hostname
	 * @return string
	 */
	public function resolve_ip($hostname = "")
	{
		$ip = gethostbyname($hostname);
		return $ip == $hostname ? $hostname : $ip;
	}

	/**
	 * Converts a PKCS#12 (PFX) binary blob to a PEM-encoded certificate string.
	 * @method pfx_to_pem
	 * @param  string $pfx_data   Raw binary content of the .pfx file
	 * @param  string $passphrase Passphrase protecting the PFX (empty string if none)
	 * @return string|false       PEM certificate string, or false on failure
	 */
	public function pfx_to_pem(string $pfx_data, string $passphrase = "")
	{
		$certs = [];
		if (!openssl_pkcs12_read($pfx_data, $certs, $passphrase)) {
			return false;
		}
		return $certs['cert'] ?? false;
	}

	// Extract cert PEM and optional private key PEM from a PKCS#12 archive.
	// Returns ['cert' => string, 'pkey' => string|null] or false on failure.
	public function pfx_extract(string $pfx_data, string $passphrase = "")
	{
		$certs = [];
		if (!openssl_pkcs12_read($pfx_data, $certs, $passphrase)) {
			return false;
		}
		return [
			'cert' => $certs['cert'] ?? null,
			'pkey' => $certs['pkey'] ?? null,
		];
	}

	/**
	 * Converts a PEM-encoded certificate (and optional private key) to PKCS#12 (PFX) binary.
	 * @method pem_to_pfx
	 * @param  string $pem_cert   PEM-encoded certificate
	 * @param  string $pem_key    PEM-encoded private key (empty string if not available)
	 * @param  string $passphrase Passphrase to protect the PFX (empty string for none)
	 * @return string|false       Raw PFX binary, or false on failure
	 */
	public function pem_to_pfx(string $pem_cert, string $pem_key = "", string $passphrase = "")
	{
		$pfx_data = "";
		if (!openssl_pkcs12_export($pem_cert, $pfx_data, $pem_key ?: null, $passphrase)) {
			return false;
		}
		return $pfx_data;
	}

	/**
	 * Extract Subject Alternative Names from a CSR PEM by parsing the ASN.1 DER directly.
	 * PHP's openssl_csr_sign does not reliably copy requested extensions, so we walk the
	 * binary structure ourselves.  Returns plain strings: DNS names and IPv4/IPv6 addresses.
	 *
	 * @param  string $csr_pem  PEM-encoded certificate signing request
	 * @return array            e.g. ['example.com', 'www.example.com', '192.168.1.1']
	 */
	public function csr_extract_sans(string $csr_pem): array
	{
		$der = base64_decode(preg_replace('/-----[^-]+-----|[\r\n\s]/', '', $csr_pem));
		if (!$der) return [];

		// subjectAltName OID 2.5.29.17 = bytes 55 1d 11
		$oid    = "\x55\x1d\x11";
		$offset = 0;

		while (($p = strpos($der, $oid, $offset)) !== false) {
			$offset = $p + 1;
			$p += 3; // skip past the three OID value bytes

			// Skip optional BOOLEAN critical flag: 01 01 ff
			if (isset($der[$p]) && ord($der[$p]) === 0x01) $p += 3;

			// Expect OCTET STRING (04) wrapping the extension value
			if (!isset($der[$p]) || ord($der[$p]) !== 0x04) continue;
			$p++;
			$this->_asn1_len($der, $p); // advance past length

			// Expect SEQUENCE (30) = GeneralNames
			if (!isset($der[$p]) || ord($der[$p]) !== 0x30) continue;
			$p++;
			$seq_len = $this->_asn1_len($der, $p);
			$seq_end = $p + $seq_len;

			$names = [];
			while ($p < $seq_end && $p < strlen($der)) {
				$tag = ord($der[$p++]);
				$len = $this->_asn1_len($der, $p);
				$val = substr($der, $p, $len);
				$p  += $len;

				if ($tag === 0x82) {       // dNSName  [2] IMPLICIT IA5String
					$names[] = $val;
				} elseif ($tag === 0x87) { // iPAddress [7] IMPLICIT OCTET STRING
					if ($len === 4) {
						$names[] = implode('.', array_map('ord', str_split($val)));
					} elseif ($len === 16) { // IPv6
						$parts = [];
						for ($i = 0; $i < 16; $i += 2) {
							$parts[] = sprintf('%04x', (ord($val[$i]) << 8) | ord($val[$i + 1]));
						}
						$names[] = implode(':', $parts);
					}
				}
			}

			if (!empty($names)) return $names;
		}

		return [];
	}

	/**
	 * Extract keyUsage and extKeyUsage from a CSR PEM by parsing the ASN.1 DER directly.
	 * Returns an array compatible with the `extensions` JSON column:
	 *   ['keyUsage' => [...], 'extKeyUsage' => [...]]
	 */
	public function csr_extract_extensions(string $csr_pem): array
	{
		$der = base64_decode(preg_replace('/-----[^-]+-----|[\r\n\s]/', '', $csr_pem));
		if (!$der) return [];

		$result = [];

		// keyUsage OID 2.5.29.15 = 55 1d 0f
		$ku_val = $this->_asn1_extension_value($der, "\x55\x1d\x0f");
		if ($ku_val !== null) {
			$ku = $this->_parse_key_usage_bitstring($ku_val);
			if (!empty($ku)) $result['keyUsage'] = $ku;
		}

		// extKeyUsage OID 2.5.29.37 = 55 1d 25
		$eku_val = $this->_asn1_extension_value($der, "\x55\x1d\x25");
		if ($eku_val !== null) {
			$eku = $this->_parse_ext_key_usage_seq($eku_val);
			if (!empty($eku)) $result['extKeyUsage'] = $eku;
		}

		return $result;
	}

	// Find an extension by its 3-byte OID value and return the raw OCTET STRING contents.
	private function _asn1_extension_value(string $der, string $oid): ?string
	{
		$p = strpos($der, $oid);
		if ($p === false) return null;
		$p += 3; // skip past OID value bytes

		// Skip optional BOOLEAN critical flag: 01 01 ff
		if (isset($der[$p]) && ord($der[$p]) === 0x01) $p += 3;

		// Expect OCTET STRING (04)
		if (!isset($der[$p]) || ord($der[$p]) !== 0x04) return null;
		$p++;
		$len = $this->_asn1_len($der, $p);
		return substr($der, $p, $len);
	}

	// Parse a keyUsage BIT STRING value into flag names.
	private function _parse_key_usage_bitstring(string $val): array
	{
		// BIT STRING: 03 <len> <unused_bits> <byte1> [<byte2>]
		if (!isset($val[0]) || ord($val[0]) !== 0x03) return [];
		$p    = 1;
		$this->_asn1_len($val, $p); // skip length
		$b1   = isset($val[$p + 1]) ? ord($val[$p + 1]) : 0;

		$bits = [
			[0x80, 'digitalSignature'],
			[0x40, 'contentCommitment'],
			[0x20, 'keyEncipherment'],
			[0x10, 'dataEncipherment'],
			[0x08, 'keyAgreement'],
			[0x04, 'keyCertSign'],
			[0x02, 'cRLSign'],
		];
		$result = [];
		foreach ($bits as [$mask, $name]) {
			if ($b1 & $mask) $result[] = $name;
		}
		return $result;
	}

	// Parse an extKeyUsage SEQUENCE of OIDs into purpose names.
	private function _parse_ext_key_usage_seq(string $val): array
	{
		// SEQUENCE (30) of OID (06) entries
		if (!isset($val[0]) || ord($val[0]) !== 0x30) return [];
		$p       = 1;
		$seq_end = $p + $this->_asn1_len($val, $p);

		$oid_map = [
			"\x2b\x06\x01\x05\x05\x07\x03\x01" => 'serverAuth',
			"\x2b\x06\x01\x05\x05\x07\x03\x02" => 'clientAuth',
			"\x2b\x06\x01\x05\x05\x07\x03\x03" => 'codeSigning',
			"\x2b\x06\x01\x05\x05\x07\x03\x04" => 'emailProtection',
			"\x2b\x06\x01\x05\x05\x07\x03\x08" => 'timeStamping',
			"\x2b\x06\x01\x05\x05\x07\x03\x09" => 'OCSPSigning',
		];

		$result = [];
		while ($p < $seq_end && isset($val[$p])) {
			if (ord($val[$p]) !== 0x06) break;
			$p++;
			$len      = $this->_asn1_len($val, $p);
			$oid_bytes = substr($val, $p, $len);
			$p        += $len;
			if (isset($oid_map[$oid_bytes])) $result[] = $oid_map[$oid_bytes];
		}
		return $result;
	}

	private function _asn1_len(string $der, int &$p): int
	{
		$b = ord($der[$p++]);
		if ($b < 0x80) return $b;
		$n = $b & 0x7f;
		$len = 0;
		for ($i = 0; $i < $n; $i++) $len = ($len << 8) | ord($der[$p++]);
		return $len;
	}

}
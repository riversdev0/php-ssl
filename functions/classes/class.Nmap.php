<?php

/**
 * Nmap network host discovery
 *
 * Manages scan requests in the nmap_scans table, executes nmap via proc_open,
 * parses XML output, and adds discovered hosts to the zone.
 */
class Nmap
{
    public string $nmap_path;

    private Database_PDO $Database;

    public function __construct(Database_PDO $db)
    {
        global $nmap_path;
        $this->Database  = $db;
        $this->nmap_path = $nmap_path ?? '/usr/bin/nmap';
    }

    /**
     * Insert a new scan request for a zone.
     */
    public function request_scan(int $tenant_id, int $zone_id, int $user_id, string $prefix, int $pg_id, bool $ptr_lookup, ?string $notify_email = null): int
    {
        $row = [
            'tenant_id'    => $tenant_id,
            'zone_id'      => $zone_id,
            'user_id'      => $user_id,
            'prefix'       => $prefix,
            'pg_id'        => $pg_id,
            'ptr_lookup'   => $ptr_lookup ? 1 : 0,
            'notify_email' => $notify_email ?: null,
            'status'       => 'Requested',
            'requested'    => date('Y-m-d H:i:s'),
        ];
        return (int) $this->Database->insertObject('nmap_scans', $row);
    }

    /**
     * Return all scans for a zone, newest first.
     */
    public function get_zone_scans(int $zone_id): array
    {
        try {
            $rows = $this->Database->getObjectsQuery(
                "SELECT ns.*, u.name AS username,
                        pg.name AS pg_name, pg.ports AS pg_ports
                   FROM nmap_scans ns
                   LEFT JOIN users u           ON u.id  = ns.user_id
                   LEFT JOIN ssl_port_groups pg ON pg.id = ns.pg_id
                  WHERE ns.zone_id = ?
                  ORDER BY ns.id DESC
                  LIMIT 20",
                [(int) $zone_id]
            );
            return is_array($rows) ? $rows : [];
        } catch (Exception $_e) {
            // pg_id column may not exist yet (migration 0029 pending) — fall back
            try {
                $rows = $this->Database->getObjectsQuery(
                    "SELECT ns.*, u.name AS username
                       FROM nmap_scans ns
                       LEFT JOIN users u ON u.id = ns.user_id
                      WHERE ns.zone_id = ?
                      ORDER BY ns.id DESC
                      LIMIT 20",
                    [(int) $zone_id]
                );
                return is_array($rows) ? $rows : [];
            } catch (Exception $_e2) {
                return [];
            }
        }
    }

    /**
     * Return only Requested/Scanning scans for a zone, oldest first.
     */
    public function get_zone_pending_scans(int $zone_id): array
    {
        try {
            $rows = $this->Database->getObjectsQuery(
                "SELECT ns.*, pg.name AS pg_name, pg.ports AS pg_ports
                   FROM nmap_scans ns
                   LEFT JOIN ssl_port_groups pg ON pg.id = ns.pg_id
                  WHERE ns.zone_id = ? AND ns.status IN ('Requested','Scanning')
                  ORDER BY ns.id ASC",
                [(int) $zone_id]
            );
            return is_array($rows) ? $rows : [];
        } catch (Exception $_e) {
            // pg_id column may not exist yet (migration 0029 pending) — fall back
            try {
                $rows = $this->Database->getObjectsQuery(
                    "SELECT * FROM nmap_scans
                      WHERE zone_id = ? AND status IN ('Requested','Scanning')
                      ORDER BY id ASC",
                    [(int) $zone_id]
                );
                return is_array($rows) ? $rows : [];
            } catch (Exception $_e2) {
                return [];
            }
        }
    }

    /**
     * Pick up all Requested scans for a tenant and execute them.
     * Called from the cron script.
     */
    public function run_pending(int $tenant_id): void
    {
        try {
            $scans = $this->Database->getObjectsQuery(
                "SELECT * FROM nmap_scans WHERE tenant_id = ? AND status = 'Requested' ORDER BY id ASC",
                [$tenant_id]
            );
        } catch (Exception $e) {
            print "nmap_scan: DB error fetching pending scans: " . $e->getMessage() . "\n";
            return;
        }

        if (!$scans) {
            return;
        }

        foreach ($scans as $scan) {
            $this->execute_scan($scan);
        }
    }

    /**
     * Run a single scan synchronously.
     */
    private function execute_scan(object $scan): void
    {
        // resolve ports from port group
        $pg = $this->Database->getObjectQuery(
            "SELECT ports FROM ssl_port_groups WHERE id = ?",
            [(int) $scan->pg_id]
        );
        if (!$pg || empty($pg->ports)) {
            $this->Database->updateObject('nmap_scans', [
                'id'        => $scan->id,
                'status'    => 'Error',
                'error_msg' => 'Port group not found or has no ports defined',
                'completed' => date('Y-m-d H:i:s'),
            ]);
            return;
        }

        // mark as Scanning
        $this->Database->updateObject('nmap_scans', ['id' => $scan->id, 'status' => 'Scanning']);

        $cmd = $this->build_command($scan->prefix, $pg->ports);
        $xml = $this->run_nmap($cmd);

        if ($xml === false) {
            $this->Database->updateObject('nmap_scans', [
                'id'        => $scan->id,
                'status'    => 'Error',
                'error_msg' => 'nmap process failed or binary not found',
                'completed' => date('Y-m-d H:i:s'),
            ]);
            return;
        }

        $hosts = $this->parse_xml($xml, (bool) $scan->ptr_lookup);

        list($found, $added) = $this->add_hosts($scan, $hosts);

        $completed = date('Y-m-d H:i:s');
        $this->Database->updateObject('nmap_scans', [
            'id'          => $scan->id,
            'status'      => 'Completed',
            'hosts_found' => $found,
            'hosts_added' => $added,
            'completed'   => $completed,
        ]);

        // reload scan row so send_completion_email has up-to-date completed timestamp
        $scan->hosts_found = $found;
        $scan->hosts_added = $added;
        $scan->completed   = $completed;
        $scan->status      = 'Completed';

        if (!empty($scan->notify_email)) {
            $this->send_completion_email($scan);
        }
    }

    /**
     * Send scan completion email to notify_email.
     */
    private function send_completion_email(object $scan): void
    {
        global $mail_sender_settings;

        if (empty($scan->notify_email)) { return; }

        $Mail = new mailer();

        // fetch tenant href and zone name for the link
        $row = $this->Database->getObjectQuery(
            "SELECT t.href AS tenant_href, z.name AS zone_name
               FROM tenants t
               JOIN zones z ON z.id = ?
              WHERE t.id = ?",
            [(int) $scan->zone_id, (int) $scan->tenant_id]
        );

        $base_url  = rtrim($mail_sender_settings->www ?? '', '/');
        $zone_link = '';
        if ($row) {
            $zone_link = $base_url . '/' . $row->tenant_href . '/zones/' . rawurlencode($row->zone_name) . '/';
        }

        $td = "border-bottom:1px solid #eee;padding:5px 8px;vertical-align:top;";

        $rows   = [];
        $rows[] = $Mail->font_title . _("Network scan completed") . "</font><br><br>";
        $rows[] = "<table cellpadding='0' cellspacing='0' border='0' style='width:100%;max-width:520px;'>";
        $pg_label = !empty($scan->pg_name) ? htmlspecialchars($scan->pg_name) . " (" . htmlspecialchars($scan->pg_ports ?? '') . ")" : "—";
        $rows[] = "<tr><td style='{$td}color:#888;width:40%'>" . $Mail->font_norm . _("Prefix")     . "</font></td><td style='{$td}'>" . $Mail->font_bold . htmlspecialchars($scan->prefix) . "</font></td></tr>";
        $rows[] = "<tr><td style='{$td}color:#888'>"           . $Mail->font_norm . _("Port group") . "</font></td><td style='{$td}'>" . $Mail->font_norm . $pg_label                       . "</font></td></tr>";
        $rows[] = "<tr><td style='{$td}color:#888'>"           . $Mail->font_norm . _("Found")     . "</font></td><td style='{$td}'>" . $Mail->font_norm . (int) $scan->hosts_found        . "</font></td></tr>";
        $rows[] = "<tr><td style='{$td}color:#888'>"           . $Mail->font_norm . _("Added")     . "</font></td><td style='{$td}'>" . $Mail->font_bold . (int) $scan->hosts_added        . "</font></td></tr>";
        $rows[] = "<tr><td style='{$td}color:#888'>"           . $Mail->font_norm . _("Requested") . "</font></td><td style='{$td}'>" . $Mail->font_norm . htmlspecialchars($scan->requested ?? '') . "</font></td></tr>";
        $rows[] = "<tr><td style='{$td}color:#888'>"           . $Mail->font_norm . _("Completed") . "</font></td><td style='{$td}'>" . $Mail->font_norm . htmlspecialchars($scan->completed ?? '') . "</font></td></tr>";
        $rows[] = "</table>";
        $rows[] = "<br>";

        if ($zone_link) {
            $rows[] = $Mail->font_norm . "<a href='{$zone_link}' style='color:#003551;'>" . _("View zone") . "</a></font><br>";
        }
        $rows[] = "<br>" . $Mail->font_norm . "Visit <a href='" . ($mail_sender_settings->www ?? '') . "' style='color:#003551;'>" . ($mail_sender_settings->www ?? '') . "</a></font>";

        $Mail->send(
            "php-ssl :: " . _("Network scan completed"),
            [$scan->notify_email],
            [],
            [],
            implode("\n", $rows),
            false
        );
    }

    /**
     * Build the nmap argument array (no shell — avoids injection).
     */
    private function build_command(string $prefix, string $ports): array
    {
        return [
            $this->nmap_path,
            '-sT',          // TCP connect scan (no root required)
            '-p', $ports,   // ports to probe
            '--open',       // only report hosts with open ports
            '-oX', '-',     // XML to stdout
            '--host-timeout', '10s',
            '-T4',
            $prefix,
        ];
    }

    /**
     * Execute nmap and return stdout XML, or false on failure.
     */
    private function run_nmap(array $cmd)
    {
        if (!file_exists($cmd[0]) || !is_executable($cmd[0])) {
            return false;
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($proc)) {
            return false;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        // nmap exits 0 on success; non-zero means error
        if ($exit !== 0 || $stdout === false || trim($stdout) === '') {
            return false;
        }

        return $stdout;
    }

    /**
     * Parse nmap XML and return an array of discovered entries.
     *
     * Each entry: ['ip' => '...', 'ptr' => '...' or null]
     */
    private function parse_xml(string $xml, bool $include_ptr): array
    {
        $hosts = [];

        // suppress warnings for malformed XML
        $prev = libxml_use_internal_errors(true);
        $doc  = simplexml_load_string($xml);
        libxml_use_internal_errors($prev);

        if ($doc === false) {
            return $hosts;
        }

        foreach ($doc->host as $host) {
            // only UP hosts
            if ((string) $host->status['state'] !== 'up') {
                continue;
            }

            $ip  = null;
            $ptr = null;

            foreach ($host->address as $addr) {
                if ((string) $addr['addrtype'] === 'ipv4') {
                    $ip = (string) $addr['addr'];
                }
            }

            if ($ip === null) {
                continue;
            }

            if ($include_ptr && isset($host->hostnames->hostname)) {
                foreach ($host->hostnames->hostname as $hn) {
                    if ((string) $hn['type'] === 'PTR') {
                        $ptr = (string) $hn['name'];
                        break;
                    }
                }
            }

            $hosts[] = ['ip' => $ip, 'ptr' => $ptr];
        }

        return $hosts;
    }

    /**
     * Add discovered hosts to the zone.
     * Returns [hosts_found, hosts_added].
     */
    private function add_hosts(object $scan, array $hosts): array
    {
        $found = count($hosts);
        $added = 0;

        if ($found === 0) {
            return [0, 0];
        }

        $pg_id = (int) $scan->pg_id;

        foreach ($hosts as $h) {
            // always add the IP
            if ($this->host_exists($scan->zone_id, $h['ip']) === false) {
                $this->insert_host($scan->zone_id, $h['ip'], $pg_id);
                $added++;
            }

            // also add the PTR name if available and different from IP
            if ($h['ptr'] !== null && $h['ptr'] !== $h['ip']) {
                if ($this->host_exists($scan->zone_id, $h['ptr']) === false) {
                    $this->insert_host($scan->zone_id, $h['ptr'], $pg_id);
                    $added++;
                }
            }
        }

        return [$found, $added];
    }

    /**
     * Check if a hostname already exists in the zone.
     */
    private function host_exists(int $zone_id, string $hostname): bool
    {
        try {
            $row = $this->Database->getObjectQuery(
                "SELECT id FROM hosts WHERE z_id = ? AND hostname = ? LIMIT 1",
                [(int) $zone_id, $hostname]
            );
            return $row !== false && $row !== null;
        } catch (Exception $_e) {
            return false;
        }
    }

    /**
     * Insert a host into the zone.
     */
    private function insert_host(int $zone_id, string $hostname, int $pg_id): void
    {
        try {
            $this->Database->insertObject('hosts', [
                'z_id'     => $zone_id,
                'pg_id'    => $pg_id,
                'hostname' => $hostname,
            ]);
        } catch (Exception $_e) {
            // skip duplicate or DB errors silently — host_exists guards most cases
        }
    }

    /**
     * Validate a CIDR prefix: must be IPv4, prefix length 16–32.
     */
    public static function validate_prefix(string $prefix): bool
    {
        if (strpos($prefix, '/') === false) {
            return false;
        }
        list($ip, $len) = explode('/', $prefix, 2);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        $len = (int) $len;
        return $len >= 16 && $len <= 32;
    }

}

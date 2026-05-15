<?php

/**
 * testSSL scan management class.
 *
 * Handles creating scan requests, querying results, running testssl.sh,
 * parsing JSON output, and export helpers.
 */
class TestSSL
{
    private Database_PDO $Database;

    public string $testssl_path;

    public function __construct(Database_PDO $Database)
    {
        $this->Database     = $Database;
        $this->testssl_path = dirname(__FILE__) . '/../../functions/testSSL/testssl.sh';
    }

    // -------------------------------------------------------------------------
    // Query helpers
    // -------------------------------------------------------------------------

    public function get_all(int $tenant_id, bool $is_admin): array
    {
        if ($is_admin) {
            $rows = $this->Database->getObjectsQuery(
                "SELECT ts.*, u.name AS user_name, t.name AS tenant_name
                   FROM testssl ts
                   LEFT JOIN users u   ON ts.user_id   = u.id
                   LEFT JOIN tenants t ON ts.tenant_id = t.id
                  ORDER BY ts.requested DESC",
                []
            );
        } else {
            $rows = $this->Database->getObjectsQuery(
                "SELECT ts.*, u.name AS user_name, t.name AS tenant_name
                   FROM testssl ts
                   LEFT JOIN users u   ON ts.user_id   = u.id
                   LEFT JOIN tenants t ON ts.tenant_id = t.id
                  WHERE ts.tenant_id = ?
                  ORDER BY ts.requested DESC",
                [$tenant_id]
            );
        }
        return $rows ?: [];
    }

    public function get_by_id(int $id, int $tenant_id, bool $is_admin): ?object
    {
        if ($is_admin) {
            return $this->Database->getObjectQuery(
                "SELECT ts.*, u.name AS user_name, t.name AS tenant_name
                   FROM testssl ts
                   LEFT JOIN users u   ON ts.user_id   = u.id
                   LEFT JOIN tenants t ON ts.tenant_id = t.id
                  WHERE ts.id = ?",
                [$id]
            ) ?: null;
        }
        return $this->Database->getObjectQuery(
            "SELECT ts.*, u.name AS user_name, t.name AS tenant_name
               FROM testssl ts
               LEFT JOIN users u   ON ts.user_id   = u.id
               LEFT JOIN tenants t ON ts.tenant_id = t.id
              WHERE ts.id = ? AND ts.tenant_id = ?",
            [$id, $tenant_id]
        ) ?: null;
    }

    public function get_by_hash(string $hash): ?object
    {
        return $this->Database->getObjectQuery(
            "SELECT ts.*, u.name AS user_name, t.name AS tenant_name
               FROM testssl ts
               LEFT JOIN users u   ON ts.user_id   = u.id
               LEFT JOIN tenants t ON ts.tenant_id = t.id
              WHERE ts.hash = ?",
            [$hash]
        ) ?: null;
    }

    /** Authenticated lookup by hash with tenant scoping. */
    public function get_by_hash_auth(string $hash, int $tenant_id, bool $is_admin): ?object
    {
        if ($is_admin) {
            return $this->Database->getObjectQuery(
                "SELECT ts.*, u.name AS user_name, t.name AS tenant_name
                   FROM testssl ts
                   LEFT JOIN users u   ON ts.user_id   = u.id
                   LEFT JOIN tenants t ON ts.tenant_id = t.id
                  WHERE ts.hash = ?",
                [$hash]
            ) ?: null;
        }
        return $this->Database->getObjectQuery(
            "SELECT ts.*, u.name AS user_name, t.name AS tenant_name
               FROM testssl ts
               LEFT JOIN users u   ON ts.user_id   = u.id
               LEFT JOIN tenants t ON ts.tenant_id = t.id
              WHERE ts.hash = ? AND ts.tenant_id = ?",
            [$hash, $tenant_id]
        ) ?: null;
    }

    /** Latest scan per hostname for the given tenant (for zone-hosts badges). */
    public function get_latest_by_hostnames(array $hostnames, int $tenant_id, bool $is_admin): array
    {
        if (empty($hostnames)) { return []; }
        $n            = count($hostnames);
        $placeholders = implode(',', array_fill(0, $n, '?'));
        $tenant_clause = $is_admin ? '' : ' AND ts.tenant_id = ?';
        $params = array_merge(
            $hostnames,
            $hostnames,
            $is_admin ? [] : [$tenant_id]
        );
        $rows = $this->Database->getObjectsQuery(
            "SELECT ts.hostname, ts.port, ts.rating, ts.status, ts.completed, ts.hash, ts.id
               FROM testssl ts
               INNER JOIN (
                   SELECT hostname, MAX(id) AS max_id
                     FROM testssl
                    WHERE hostname IN ($placeholders)
                    GROUP BY hostname
               ) latest ON ts.id = latest.max_id
              WHERE ts.hostname IN ($placeholders){$tenant_clause}",
            $params
        ) ?: [];
        $map = [];
        foreach ($rows as $r) { $map[$r->hostname] = $r; }
        return $map;
    }

    // -------------------------------------------------------------------------
    // Mutations
    // -------------------------------------------------------------------------

    public function create(string $hostname, int $port, int $tenant_id, int $user_id, ?string $notify_email = null): int
    {
        $hash = bin2hex(random_bytes(32));
        $this->Database->runQuery(
            "INSERT INTO testssl (tenant_id, user_id, hostname, port, hash, notify_email, status, requested)
             VALUES (?, ?, ?, ?, ?, ?, 'Requested', NOW())",
            [$tenant_id, $user_id, $hostname, $port, $hash, $notify_email]
        );
        return (int)$this->Database->lastInsertId();
    }

    public function cancel(int $id, int $tenant_id, bool $is_admin): void
    {
        if ($is_admin) {
            $this->Database->runQuery(
                "UPDATE testssl SET status = 'Cancelled' WHERE id = ? AND status = 'Requested'",
                [$id]
            );
        } else {
            $this->Database->runQuery(
                "UPDATE testssl SET status = 'Cancelled' WHERE id = ? AND tenant_id = ? AND status = 'Requested'",
                [$id, $tenant_id]
            );
        }
    }

    public function delete(int $id, int $tenant_id, bool $is_admin): void
    {
        if ($is_admin) {
            $this->Database->runQuery("DELETE FROM testssl WHERE id = ?", [$id]);
        } else {
            $this->Database->runQuery(
                "DELETE FROM testssl WHERE id = ? AND tenant_id = ?",
                [$id, $tenant_id]
            );
        }
    }

    // -------------------------------------------------------------------------
    // Cron execution
    // -------------------------------------------------------------------------

    /** Fetch all Requested scans for a tenant, mark Scanning, then run each. */
    public function run_pending(int $tenant_id): void
    {
        $scans = $this->Database->getObjectsQuery(
            "SELECT * FROM testssl WHERE tenant_id = ? AND status = 'Requested' ORDER BY requested ASC",
            [$tenant_id]
        );
        if (!$scans) { return; }

        foreach ($scans as $scan) {
            $this->Database->runQuery(
                "UPDATE testssl SET status = 'Scanning', started = NOW() WHERE id = ?",
                [$scan->id]
            );
            $this->run_scan($scan);
        }
    }

    private function run_scan(object $scan): void
    {
        if (!file_exists($this->testssl_path)) {
            $this->Database->runQuery(
                "UPDATE testssl SET status = 'Error', completed = NOW(), error_message = ? WHERE id = ?",
                ['testssl.sh submodule not found', $scan->id]
            );
            return;
        }

        $json_file = sys_get_temp_dir() . '/testssl_' . $scan->id . '_' . time() . '.json';

        // Build argument array — proc_open with array skips the shell entirely
        $args = [
            'bash',
            $this->testssl_path,
            '--jsonfile', $json_file,
            '--quiet',
            '--color', '0',
            '--warnings', 'off',
            $scan->hostname . ':' . (int)$scan->port,
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($args, $descriptors, $pipes);
        if (!is_resource($proc)) {
            $this->Database->runQuery(
                "UPDATE testssl SET status = 'Error', completed = NOW(), error_message = 'proc_open failed' WHERE id = ?",
                [$scan->id]
            );
            return;
        }

        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit_code = proc_close($proc);

        if ($exit_code !== 0 && !file_exists($json_file)) {
            $error = substr($stderr, -2000);
            $this->Database->runQuery(
                "UPDATE testssl SET status = 'Error', completed = NOW(), error_message = ? WHERE id = ?",
                [$error ?: 'testssl.sh exited with code ' . $exit_code, $scan->id]
            );
            return;
        }

        $json_raw = file_exists($json_file) ? file_get_contents($json_file) : null;
        @unlink($json_file);

        // detect fatal scan problems reported inside the JSON
        $decoded = $json_raw ? json_decode($json_raw, true) : null;
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                if (isset($item['id']) && $item['id'] === 'scanProblem'
                    && isset($item['severity']) && strtoupper($item['severity']) === 'FATAL') {
                    $this->Database->runQuery(
                        "UPDATE testssl SET status = 'Error', completed = NOW(), json_result = ?, error_message = ? WHERE id = ?",
                        [$json_raw, substr($item['finding'] ?? 'Fatal scan error', 0, 2000), $scan->id]
                    );
                    return;
                }
            }
        }

        $rating = $this->extract_rating($decoded);

        $this->Database->runQuery(
            "UPDATE testssl SET status = 'Completed', completed = NOW(), json_result = ?, rating = ? WHERE id = ?",
            [$json_raw, $rating, $scan->id]
        );

        if (!empty($scan->notify_email)) {
            $scan->rating    = $rating;
            $scan->status    = 'Completed';
            $scan->completed = date('Y-m-d H:i:s');
            $this->send_completion_email($scan);
        }
    }

    private function send_completion_email(object $scan): void
    {
        global $mail_sender_settings;

        if (empty($scan->notify_email)) { return; }

        $Mail = new mailer();

        // Fetch tenant href for building the authenticated link
        $tenant = $this->Database->getObjectQuery(
            "SELECT href FROM tenants WHERE id = ?",
            [$scan->tenant_id]
        );
        $tenant_href = $tenant ? $tenant->href : '';

        $base_url   = rtrim($mail_sender_settings->www ?? '', '/');
        $auth_link  = $base_url . '/' . $tenant_href . '/testssl/' . (int)$scan->id . '/';
        $pub_link   = $base_url . '/report/' . $scan->hash . '/';

        $rating_label = $scan->rating ?: '—';
        $completed    = $scan->completed ? date('Y-m-d H:i:s', strtotime($scan->completed)) : '—';
        $started      = isset($scan->started) && $scan->started ? date('Y-m-d H:i:s', strtotime($scan->started)) : '—';
        $requested    = isset($scan->requested) && $scan->requested ? date('Y-m-d H:i:s', strtotime($scan->requested)) : '—';

        $td = "border-bottom:1px solid #eee;padding:5px 8px;vertical-align:top;";

        $rows   = [];
        $rows[] = $Mail->font_title . _("testSSL scan completed") . "</font><br><br>";
        $rows[] = "<table cellpadding='0' cellspacing='0' border='0' style='width:100%;max-width:520px;'>";
        $rows[] = "<tr><td style='{$td}color:#888;width:40%'>" . $Mail->font_norm . _("Hostname") . "</font></td><td style='{$td}'>" . $Mail->font_bold . htmlspecialchars($scan->hostname) . "</font></td></tr>";
        $rows[] = "<tr><td style='{$td}color:#888'>" . $Mail->font_norm . _("Port") . "</font></td><td style='{$td}'>" . $Mail->font_norm . (int)$scan->port . "</font></td></tr>";
        $rows[] = "<tr><td style='{$td}color:#888'>" . $Mail->font_norm . _("Rating") . "</font></td><td style='{$td}'>" . $Mail->font_bold . htmlspecialchars($rating_label) . "</font></td></tr>";
        $rows[] = "<tr><td style='{$td}color:#888'>" . $Mail->font_norm . _("Requested") . "</font></td><td style='{$td}'>" . $Mail->font_norm . $requested . "</font></td></tr>";
        $rows[] = "<tr><td style='{$td}color:#888'>" . $Mail->font_norm . _("Started") . "</font></td><td style='{$td}'>" . $Mail->font_norm . $started . "</font></td></tr>";
        $rows[] = "<tr><td style='{$td}color:#888'>" . $Mail->font_norm . _("Completed") . "</font></td><td style='{$td}'>" . $Mail->font_norm . $completed . "</font></td></tr>";
        $rows[] = "</table>";
        $rows[] = "<br>";

        if ($tenant_href) {
            $rows[] = $Mail->font_norm . "<a href='" . $auth_link . "' style='color:#003551;'>" . _("View report") . "</a></font><br>";
        }
        $rows[] = $Mail->font_norm . "<a href='" . $pub_link . "' style='color:#003551;'>" . _("Public link") . "</a></font><br>";
        $rows[] = "<br>" . $Mail->font_norm . "Visit <a href='" . ($mail_sender_settings->www ?? '') . "' style='color:#003551;'>" . ($mail_sender_settings->www ?? '') . "</a></font>";

        $Mail->send(
            "Telemach php-ssl :: testSSL scan completed",
            [$scan->notify_email],
            [],
            [],
            implode("\n", $rows),
            false
        );
    }

    // -------------------------------------------------------------------------
    // JSON parsing helpers
    // -------------------------------------------------------------------------

    /** Extract the overall rating (e.g. "A+") from testssl JSON array. */
    public function extract_rating(?array $data): ?string
    {
        if (!$data) { return null; }
        foreach ($data as $item) {
            if (isset($item['id']) && $item['id'] === 'overall_grade') {
                return $item['finding'] ?? null;
            }
        }
        return null;
    }

    /**
     * Parse flat testssl JSON array into grouped sections for display.
     * Returns assoc array: section_key => ['title' => string, 'items' => [...]]
     */
    public function parse_result(?string $json_raw): array
    {
        if (!$json_raw) { return []; }
        $data = json_decode($json_raw, true);
        if (!is_array($data)) { return []; }

        $sections = [
            'general'   => ['title' => _('General'),                    'items' => []],
            'protocols' => ['title' => _('Protocols via sockets'),       'items' => []],
            'ciphers'   => ['title' => _('Cipher categories'),           'items' => []],
            'pfs'       => ['title' => _('Robust forward secrecy (FS)'), 'items' => []],
            'server'    => ['title' => _('Server defaults'),             'items' => []],
            'header'    => ['title' => _('HTTP security headers'),       'items' => []],
            'vulns'     => ['title' => _('Vulnerabilities'),             'items' => []],
            'rating'    => ['title' => _('Rating'),                      'items' => []],
            'other'     => ['title' => _('Other'),                       'items' => []],
        ];

        // id prefix → section
        $prefix_map = [
            'service'         => 'general',  'cert'           => 'general',
            'issuer'          => 'general',  'cn'             => 'general',
            'san'             => 'general',  'key'            => 'general',
            'fingerprint'     => 'general',  'trust'          => 'general',
            'chain'           => 'general',  'expiration'     => 'general',
            'protocol_'       => 'protocols','SSLv2'          => 'protocols',
            'SSLv3'           => 'protocols','TLS1'           => 'protocols',
            'cipher_'         => 'ciphers',  'cipherlist_'    => 'ciphers',
            'fs_'             => 'pfs',      'pfs'            => 'pfs',
            'FS'              => 'pfs',
            'server_defaults' => 'server',   'session'        => 'server',
            'renegotiation'   => 'server',   'compression'    => 'server',
            'HSTS'            => 'header',   'HPKP'           => 'header',
            'banner'          => 'header',   'cookie'         => 'header',
            'security_header' => 'header',
            'heartbleed'      => 'vulns',    'CCS'            => 'vulns',
            'ticketbleed'     => 'vulns',    'ROBOT'          => 'vulns',
            'BEAST'           => 'vulns',    'LUCKY13'        => 'vulns',
            'RC4'             => 'vulns',    'POODLE'         => 'vulns',
            'SWEET32'         => 'vulns',    'FREAK'          => 'vulns',
            'DROWN'           => 'vulns',    'LOGJAM'         => 'vulns',
            'CRIME'           => 'vulns',    'BREACH'         => 'vulns',
            'GOLDENDOODLE'    => 'vulns',    'ZOMBIE'         => 'vulns',
            'vuln'            => 'vulns',
            'overall_grade'   => 'rating',   'grade'          => 'rating',
        ];

        foreach ($data as $item) {
            $id      = $item['id']       ?? '';
            $finding = $item['finding']  ?? '';
            $sev     = $item['severity'] ?? '';

            $section = 'other';
            foreach ($prefix_map as $prefix => $sec) {
                if ($id === $prefix || strncmp($id, $prefix, strlen($prefix)) === 0) {
                    $section = $sec;
                    break;
                }
            }

            $sections[$section]['items'][] = [
                'id'       => $id,
                'label'    => $this->id_to_label($id),
                'finding'  => $finding,
                'severity' => $sev,
            ];
        }

        return array_filter($sections, fn($s) => !empty($s['items']));
    }

    private function id_to_label(string $id): string
    {
        return ucwords(strtolower(str_replace(['_', '-'], ' ', $id)));
    }

    /** Severity → Bootstrap colour class. */
    public function severity_class(string $sev): string
    {
        switch (strtolower($sev)) {
            case 'ok': case 'info': return 'success';
            case 'low':             return 'info';
            case 'medium': case 'warn': return 'warning';
            case 'high': case 'critical': return 'danger';
            default: return 'secondary';
        }
    }

    /** Rating → Bootstrap colour class. */
    public function rating_class(?string $rating): string
    {
        if (!$rating) { return 'secondary'; }
        if ($rating[0] === 'A') { return 'success'; }
        if ($rating[0] === 'B') { return 'info'; }
        if ($rating[0] === 'C') { return 'warning'; }
        return 'danger';
    }

    // -------------------------------------------------------------------------
    // Export
    // -------------------------------------------------------------------------

    public function export_json(object $scan): void
    {
        $filename = 'testssl_' . $scan->hostname . '_' . $scan->port
                  . '_' . date('Ymd', strtotime($scan->completed ?? $scan->requested))
                  . '.json';
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        print $scan->json_result ?? '[]';
        exit;
    }

    public function export_csv(object $scan): void
    {
        $filename = 'testssl_' . $scan->hostname . '_' . $scan->port
                  . '_' . date('Ymd', strtotime($scan->completed ?? $scan->requested))
                  . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $data = json_decode($scan->json_result ?? '[]', true);
        $out  = fopen('php://output', 'w');
        fputcsv($out, ['id', 'finding', 'severity', 'cve', 'cwe']);
        if (is_array($data)) {
            foreach ($data as $item) {
                fputcsv($out, [
                    $item['id']       ?? '',
                    $item['finding']  ?? '',
                    $item['severity'] ?? '',
                    $item['cve']      ?? '',
                    $item['cwe']      ?? '',
                ]);
            }
        }
        fclose($out);
        exit;
    }
}

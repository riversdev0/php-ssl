<?php

// Submodule presence checks — shown to all logged-in users
$submodules = [
    'Net_DNS2'  => ['path' => __DIR__ . '/../../functions/assets/Net_DNS2/Net/DNS2.php',     'url' => 'https://github.com/mikepultz/netdns2'],
    'PHPMailer' => ['path' => __DIR__ . '/../../functions/assets/PHPMailer/src/PHPMailer.php','url' => 'https://github.com/PHPMailer/PHPMailer'],
    'testssl.sh' => ['path' => __DIR__ . '/../../functions/testSSL/testssl.sh',              'url' => 'https://github.com/testssl/testssl.sh'],
];
$missing_submodules = [];
foreach ($submodules as $name => $info) {
    if (!file_exists($info['path'])) {
        $missing_submodules[$name] = $info['url'];
    }
}
if (!empty($missing_submodules)) {
    print "<div class='alert alert-danger' role='alert' style='margin-top:10px;'>";
    print "<div class='container-fluid d-flex align-items-start gap-2'>";
    print "<svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon text-danger flex-shrink-0 mt-1'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 9v4'/><path d='M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z'/><path d='M12 16h.01'/></svg>";
    print "<div><strong>" . _("Missing git submodule(s):") . "</strong> ";
    $links = [];
    foreach ($missing_submodules as $name => $url) {
        $links[] = "<a href='" . htmlspecialchars($url) . "' target='_blank' rel='noreferrer'>" . htmlspecialchars($name) . "</a>";
    }
    print implode(', ', $links);
    print "<br><small class='text-muted'><code style='background: var(--tblr-bg-surface-dark);color:var(--tblr-light);padding:5px 4px'>git submodule update --init --recursive</code></small></div>";
    print "</div></div>";
}

if ($user->admin === "1") {
    $migration_dir = __DIR__ . '/../../db/migrations/';
    $fs_migrations = [];
    if (is_dir($migration_dir)) {
        foreach (glob($migration_dir . '*.sql') as $f) {
            $fs_migrations[] = basename($f);
        }
        sort($fs_migrations);
    }
    if (!empty($fs_migrations)) {
        try {
            $applied = $Database->getObjectsQuery("SELECT filename FROM migrations ORDER BY filename ASC", []);
            $applied_names = array_map(fn($r) => $r->filename, $applied ?: []);
            $pending = array_values(array_diff($fs_migrations, $applied_names));
        } catch (Exception $e) {
            $pending = [];
        }
        if (!empty($pending)) {
            $count = count($pending);
            $list  = implode(', ', $pending);
            print "<div class='alert alert-warning' role='alert' style='margin-top:10px;'>";
            print "<div class='container-fluid d-flex align-items-center gap-2'>";
            print "<svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon text-warning  flex-shrink-0'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 9v4'/><path d='M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z'/><path d='M12 16h.01'/></svg>";
            print "<span><strong>" . sprintf(_("%d unapplied database migration(s)"), $count) . ":</strong> <span class='text-muted'>" . htmlspecialchars($list) . "</span></span>";
            print "</div></div>";
        }
    }
}
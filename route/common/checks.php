<?php

// PHP version check — shown to all logged-in users
if (PHP_MAJOR_VERSION < 8) {
    print "<div class='alert alert-danger' role='alert' style='margin-top:10px;'>";
    print "<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon text-danger flex-shrink-0'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 9v4'/><path d='M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z'/><path d='M12 16h.01'/></svg>";
    print "<strong>" . _("Unsupported PHP version:") . "</strong> ";
    print sprintf(_("PHP %s detected — php-ssl requires PHP 8.0 or later. Please upgrade your PHP installation."), PHP_VERSION);
    print "</div>";
}

// Submodule checks — try to load each PHP module and verify its class is defined
$php_submodules = [
    'Net_DNS2' => [
        'file'  => __DIR__ . '/../../functions/assets/Net_DNS2/src/NetDNS2/Client.php',
        'class' => 'NetDNS2\\Client',
        'url'   => 'https://github.com/mikepultz/netdns2',
    ],
    'PHPMailer' => [
        'file'  => __DIR__ . '/../../functions/assets/PHPMailer/src/PHPMailer.php',
        'class' => 'PHPMailer\\PHPMailer\\PHPMailer',
        'url'   => 'https://github.com/PHPMailer/PHPMailer',
    ],
    'GoogleAuthenticator' => [
        'file'  => __DIR__ . '/../../functions/assets/GoogleAuthenticator/GoogleAuthenticator.php',
        'class' => 'PHPGangsta_GoogleAuthenticator',
        'url'   => 'https://github.com/PHPGangsta/GoogleAuthenticator',
    ],
];
$missing_submodules = [];
foreach ($php_submodules as $name => $info) {
    if (class_exists($info['class'])) {
        continue; // already loaded elsewhere in this request
    }
    if (!is_readable($info['file'])) {
        $missing_submodules[$name] = $info['url'];
        continue;
    }
    require_once $info['file'];
    if (!class_exists($info['class'])) {
        $missing_submodules[$name] = $info['url'];
    }
}
// testssl.sh is a shell script — verify it exists and is executable
if (!is_executable(__DIR__ . '/../../functions/testSSL/testssl.sh')) {
    $missing_submodules['testssl.sh'] = 'https://github.com/testssl/testssl.sh';
}
if (!empty($missing_submodules)) {
    print "<div class='alert alert-danger' role='alert' style='margin-top:10px;'>";
    print "<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon text-danger flex-shrink-0 '><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 9v4'/><path d='M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z'/><path d='M12 16h.01'/></svg>";
    print "<strong>" . _("Missing git submodule(s):") . "</strong> ";
    $links = [];
    foreach ($missing_submodules as $name => $url) {
        $links[] = "<a href='" . htmlspecialchars($url) . "' target='_blank' rel='noreferrer'>" . htmlspecialchars($name) . "</a>";
    }
    print implode(', ', $links);
    print " <small class='text-muted'><code style='background: var(--tblr-bg-surface-dark);color:var(--tblr-light);padding:5px 4px'>git submodule update --init --recursive</code></small></div>";
}

$Common->print_system_warnings();
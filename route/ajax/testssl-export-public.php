<?php
require('../../functions/autoload.php');

$hash   = $_GET['hash'] ?? '';
$format = in_array($_GET['format'] ?? '', ['json', 'csv'], true) ? $_GET['format'] : 'json';

if (!preg_match('/^[a-f0-9]{64}$/', $hash)) {
    http_response_code(400);
    exit('Invalid request');
}

$TestSSL = new TestSSL($Database);
$scan    = $TestSSL->get_by_hash($hash);

if (!$scan || $scan->status !== 'Completed') {
    http_response_code(404);
    exit('Not found');
}

if ($format === 'csv') {
    $TestSSL->export_csv($scan);
} else {
    $TestSSL->export_json($scan);
}

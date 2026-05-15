<?php
require('../../functions/autoload.php');
$User->validate_session(false, true, false);

$id     = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$format = in_array($_GET['format'] ?? '', ['json', 'csv'], true) ? $_GET['format'] : 'json';

if (!$id) {
    http_response_code(400);
    exit('Invalid request');
}

$TestSSL = new TestSSL($Database);
$scan    = $TestSSL->get_by_id($id, (int)$user->t_id, $user->admin === "1");

if (!$scan || $scan->status !== 'Completed') {
    http_response_code(404);
    exit('Not found');
}

if ($format === 'csv') {
    $TestSSL->export_csv($scan);
} else {
    $TestSSL->export_json($scan);
}

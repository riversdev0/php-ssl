<?php

/**
 * Apply pending database migrations.
 * Admin-only, AJAX (XMLHttpRequest) POST only.
 */

require('../../functions/autoload.php');

header('Content-Type: application/json');

// Must be a POST from XHR
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
	http_response_code(405);
	print json_encode(['success' => false, 'error' => 'Invalid request']);
	exit;
}

// Admin only
$User->validate_session(false, true, false);
if ($user->admin !== '1') {
	http_response_code(403);
	print json_encode(['success' => false, 'error' => 'Administrator access required']);
	exit;
}

// Apply
$results = $Migration->apply_all();

$all_ok = !in_array(false, array_column($results, 'success'), true);

print json_encode([
	'success' => $all_ok,
	'results' => $results,
]);

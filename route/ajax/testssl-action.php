<?php
require('../../functions/autoload.php');
$User->validate_session(false, true, false);
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$id     = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$id || !in_array($action, ['cancel', 'delete'], true)) {
    print json_encode(['status' => 'error', 'message' => _('Invalid request')]);
    exit;
}

$TestSSL  = new TestSSL($Database);
$is_admin = $user->admin === "1";

try {
    if ($action === 'cancel') {
        $TestSSL->cancel($id, (int)$user->t_id, $is_admin);
    } else {
        $TestSSL->delete($id, (int)$user->t_id, $is_admin);
    }
    print json_encode(['status' => 'ok']);
} catch (Exception $e) {
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

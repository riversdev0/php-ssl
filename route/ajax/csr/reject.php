<?php

ob_start();
require('../../../functions/autoload.php');
ob_clean();
header('Content-Type: application/json');

$User->validate_session(false, false, false);
# validate permissions
$User->validate_user_permissions (3, true);

$body   = json_decode(file_get_contents('php://input'), true);
$csr_id = (int)($body['csr_id'] ?? 0);

if ($csr_id <= 0) {
    print json_encode(['status' => 'error', 'message' => _("Invalid request.")]);
    exit;
}

if ($user->admin == "1") {
    $csr = $Database->getObjectQuery("SELECT id, t_id, cn, status FROM csrs WHERE id = ?", [$csr_id]);
} else {
    $csr = $Database->getObjectQuery("SELECT id, t_id, cn, status FROM csrs WHERE id = ? AND t_id = ?", [$csr_id, (int)$user->t_id]);
}

if (!$csr) {
    print json_encode(['status' => 'error', 'message' => _("CSR not found.")]);
    exit;
}

if ($csr->status === 'signed') {
    print json_encode(['status' => 'error', 'message' => _("Cannot reject an already signed CSR.")]);
    exit;
}

$Database->runQuery("UPDATE csrs SET status = 'rejected' WHERE id = ?", [$csr_id]);
$Log->write("csrs", $csr_id, (int)$csr->t_id, $user->id, "edit", false, "CSR rejected: {$csr->cn}");

print json_encode(['status' => 'ok']);

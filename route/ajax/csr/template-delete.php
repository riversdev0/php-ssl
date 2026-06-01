<?php

/**
 * Delete a CSR template.
 *
 * POST JSON: { template_id: int }
 */

ob_start();
require('../../../functions/autoload.php');
ob_clean();
header('Content-Type: application/json');

$User->validate_session(false, false, false);
# validate permissions
$User->validate_user_permissions (3, true);

$body        = json_decode(file_get_contents('php://input'), true);
$template_id = (int)($body['template_id'] ?? 0);

if ($template_id <= 0) {
    http_response_code(400);
    print json_encode(['status' => 'error', 'message' => _("Invalid request.")]);
    exit;
}

if ($user->admin == "1") {
    $tpl = $Database->getObjectQuery("SELECT * FROM csr_templates WHERE id = ?", [$template_id]);
} else {
    $tpl = $Database->getObjectQuery("SELECT * FROM csr_templates WHERE id = ? AND t_id = ?", [$template_id, $user->t_id]);
}

if (!$tpl) {
    http_response_code(404);
    print json_encode(['status' => 'error', 'message' => _("Template not found.")]);
    exit;
}

try {
    $Database->runQuery("DELETE FROM csr_templates WHERE id = ?", [$template_id]);
    $Log->write("csr_templates", $template_id, $tpl->t_id, $user->id, "delete", false, "CSR template deleted: {$tpl->name}");
    print json_encode(['status' => 'ok', 'message' => _("Template deleted.")]);
} catch (Exception $e) {
    http_response_code(500);
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

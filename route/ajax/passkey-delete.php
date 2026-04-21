<?php

/**
 * Delete a passkey belonging to the current user.
 *
 * POST JSON body: { id }  (passkey row id)
 */

require('../../functions/autoload.php');
header('Content-Type: application/json');

$User->validate_session(false, false, false);

$body = json_decode(file_get_contents('php://input'), true);
$pk_id      = (int) ($body['id'] ?? 0);
$target_uid = isset($body['user_id']) ? (int)$body['user_id'] : (int)$user->id;

// Only admins may delete another user's passkeys
if ($target_uid !== (int)$user->id && $user->admin !== "1") {
    http_response_code(403);
    print json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

if ($pk_id <= 0) {
    http_response_code(400);
    print json_encode(['status' => 'error', 'message' => 'Invalid id']);
    exit;
}

try {
    $pk = $Database->getObjectQuery(
        "SELECT * FROM passkeys WHERE id = ? AND user_id = ?",
        [$pk_id, $target_uid]
    );
    if (!$pk) {
        throw new RuntimeException('Passkey not found');
    }

    $Database->runQuery("DELETE FROM passkeys WHERE id = ?", [$pk_id]);

    // Clear force_passkey if the user has no passkeys left
    $remaining = $Database->getObjectQuery("SELECT COUNT(*) AS cnt FROM passkeys WHERE user_id = ?", [$target_uid]);
    if ($remaining && (int)$remaining->cnt === 0) {
        $Database->updateObject("users", ['id' => $target_uid, 'force_passkey' => 0]);
    }

    $target_user = ($target_uid === (int)$user->id) ? $user : $Database->getObject("users", $target_uid);
    $Log->write("users", $target_uid, $target_user->t_id ?? $user->t_id, $user->id, "passkey_delete", false,
        "Passkey \"" . $pk->name . "\" deleted" . ($target_uid !== (int)$user->id ? " (by admin)" : ""));

    print json_encode(['status' => 'ok', 'message' => 'Passkey deleted']);
}
catch (Exception $e) {
    http_response_code(400);
    print json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

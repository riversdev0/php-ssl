<?php
require('../../../functions/autoload.php');
$User->validate_session(false, true, false);

$hostname = $User->strip_input_tags($_GET['hostname'] ?? '');
$port     = isset($_GET['port']) && is_numeric($_GET['port']) ? (int)$_GET['port'] : 443;
$tid      = isset($_GET['t_id']) && is_numeric($_GET['t_id']) ? (int)$_GET['t_id'] : (int)$user->t_id;

// Non-admins scoped to own tenant
if ($user->admin !== "1") { $tid = (int)$user->t_id; }

$content   = [];
$content[] = "<form id='modal-form'>";
$content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";
$content[] = "<input type='hidden' name='hostname'  value='" . htmlspecialchars($hostname, ENT_QUOTES) . "'>";
$content[] = "<input type='hidden' name='port'      value='" . $port . "'>";
$content[] = "<input type='hidden' name='tenant_id' value='" . $tid . "'>";
$content[] = "<p>" . sprintf(_("Request testSSL scan for <strong>%s</strong> on port <strong>%d</strong>?"), htmlspecialchars($hostname), $port) . "</p>";
$content[] = "<div class='mb-3'>";
$content[] = "  <label class='form-label'>" . _("Notify email") . "</label>";
$content[] = "  <input type='email' class='form-control' name='notify_email' placeholder='" . _("email@example.com (optional)") . "'>";
$content[] = "  <small class='text-muted'>" . _("Send an email when the scan completes. Leave blank to disable.") . "</small>";
$content[] = "</div>";
$content[] = "</form>";

$Modal->modal_print(
    _("Request testSSL scan"),
    implode("\n", $content),
    _("Request scan"),
    "/route/modals/testssl/request-submit.php",
    false,
    "success"
);

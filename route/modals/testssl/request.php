<?php
require('../../../functions/autoload.php');
$User->validate_session(false, true, false);

$is_admin    = $user->admin === "1";
$all_tenants = $is_admin ? $Tenants->get_all() : [];

$content = [];
$content[] = "<form id='modal-form'>";
$content[] = "<input type='hidden' name='csrf_token' value='" . $User->create_csrf_token() . "'>";

$content[] = "<div class='mb-3'>";
$content[] = "  <label class='form-label'>" . _("Hostname") . " <span class='text-red'>*</span></label>";
$content[] = "  <input type='text' class='form-control' name='hostname' placeholder='example.com' required>";
$content[] = "  <small class='text-muted'>" . _("Domain name or IP address to scan.") . "</small>";
$content[] = "</div>";

$content[] = "<div class='mb-3'>";
$content[] = "  <label class='form-label'>" . _("Port") . "</label>";
$content[] = "  <input type='number' class='form-control' name='port' value='443' min='1' max='65535'>";
$content[] = "</div>";

$content[] = "<div class='mb-3'>";
$content[] = "  <label class='form-label'>" . _("Notify email") . "</label>";
$content[] = "  <input type='email' class='form-control' name='notify_email' placeholder='" . _("email@example.com (optional)") . "'>";
$content[] = "  <small class='text-muted'>" . _("Send an email when the scan completes. Leave blank to disable.") . "</small>";
$content[] = "</div>";

if ($is_admin && !empty($all_tenants)) {
    $content[] = "<div class='mb-3'>";
    $content[] = "  <label class='form-label'>" . _("Tenant") . "</label>";
    $content[] = "  <select class='form-select' name='tenant_id'>";
    foreach ($all_tenants as $t) {
        $content[] = "    <option value='" . (int)$t->id . "'>" . htmlspecialchars($t->name) . "</option>";
    }
    $content[] = "  </select>";
    $content[] = "</div>";
} else {
    $content[] = "<input type='hidden' name='tenant_id' value='" . (int)$user->t_id . "'>";
}

$content[] = "</form>";

$Modal->modal_print(
    _("Request testSSL scan"),
    implode("\n", $content),
    _("Request scan"),
    "/route/modals/testssl/request-submit.php",
    false,
    "success"
);

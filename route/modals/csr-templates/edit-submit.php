<?php

/**
 * Save (add or update) a CSR template.
 */

require('../../../functions/autoload.php');
$User->validate_session(false, true, true);
$User->validate_csrf_token();

$_POST_safe = $User->strip_input_tags($_POST);
$tpl_id     = (int)($_POST_safe['id'] ?? 0);
$name       = trim($_POST_safe['name'] ?? '');
$key_algo   = ($_POST_safe['key_algo'] ?? 'RSA') === 'EC' ? 'EC' : 'RSA';
$key_size   = $key_algo === 'EC'
    ? (in_array((int)($_POST['key_size_ec'] ?? 256), [256, 384]) ? (int)$_POST['key_size_ec'] : 256)
    : (in_array((int)($_POST['key_size']    ?? 2048), [2048, 4096]) ? (int)$_POST['key_size'] : 2048);

$country  = strtoupper(substr(trim($_POST_safe['country']  ?? ''), 0, 2));
$state    = trim($_POST_safe['state']    ?? '');
$locality = trim($_POST_safe['locality'] ?? '');
$org      = trim($_POST_safe['org']      ?? '');
$ou       = trim($_POST_safe['ou']       ?? '');
$email    = trim($_POST_safe['email']    ?? '');

$allowed_ku  = ['digitalSignature', 'contentCommitment', 'keyEncipherment', 'dataEncipherment', 'keyAgreement', 'keyCertSign', 'cRLSign'];
$allowed_eku = ['serverAuth', 'clientAuth', 'codeSigning', 'emailProtection', 'timeStamping', 'OCSPSigning'];
$key_usage     = array_values(array_intersect((array)($_POST['key_usage']     ?? []), $allowed_ku));
$ext_key_usage = array_values(array_intersect((array)($_POST['ext_key_usage'] ?? []), $allowed_eku));

if (empty($name)) {
    $Result->show("danger", _("Template name is required."), true, false, false, false);
}

if ($user->admin == "1" && !empty($_POST_safe['t_id'])) {
    $t_id = (int)$_POST_safe['t_id'];
    if (!$Database->getObject("tenants", $t_id)) {
        $Result->show("danger", _("Invalid tenant."), true, false, false, false);
    }
} else {
    $t_id = (int)$user->t_id;
}

$fields = [
    'name'          => $name,
    'key_algo'      => $key_algo,
    'key_size'      => $key_size,
    'key_usage'     => !empty($key_usage)     ? json_encode($key_usage)     : null,
    'ext_key_usage' => !empty($ext_key_usage) ? json_encode($ext_key_usage) : null,
    'country'       => $country  ?: null,
    'state'         => $state    ?: null,
    'locality'      => $locality ?: null,
    'org'           => $org      ?: null,
    'ou'            => $ou       ?: null,
    'email'         => $email    ?: null,
];

try {
    if ($tpl_id > 0) {
        // Update — verify ownership
        if ($user->admin == "1") {
            $existing = $Database->getObjectQuery("SELECT id FROM csr_templates WHERE id = ?", [$tpl_id]);
        } else {
            $existing = $Database->getObjectQuery("SELECT id FROM csr_templates WHERE id = ? AND t_id = ?", [$tpl_id, $t_id]);
        }
        if (!$existing) {
            $Result->show("danger", _("Template not found."), true, false, false, false);
        }
        $sets = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($fields)));
        $vals = array_values($fields);
        $vals[] = $tpl_id;
        $fields['t_id'] = $t_id;
        $sets = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($fields)));
        $vals = array_values($fields);
        $vals[] = $tpl_id;
        $Database->runQuery("UPDATE csr_templates SET {$sets} WHERE id = ?", $vals);
        $Log->write("csr_templates", $tpl_id, $t_id, $user->id, "edit", false, "CSR template updated: {$name}");
    } else {
        $fields['t_id'] = $t_id;
        $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($fields)));
        $plac = implode(', ', array_fill(0, count($fields), '?'));
        $Database->runQuery("INSERT INTO csr_templates ({$cols}) VALUES ({$plac})", array_values($fields));
        $new_id = $Database->lastInsertId();
        $Log->write("csr_templates", $new_id, $t_id, $user->id, "add", false, "CSR template created: {$name}");
    }
} catch (Exception $e) {
    $Result->show("danger", $e->getMessage(), true, false, false, false);
}

$Result->show("success", _("Template saved."), false, false, false, true);

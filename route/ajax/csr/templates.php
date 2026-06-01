<?php

/**
 * AJAX data for CSR templates list (Bootstrap Table server-side).
 */

require('../../../functions/autoload.php');
$User->validate_session(false, true, false);

try {
    $vars = [];

    $select  = "SELECT * FROM csr_templates WHERE 1=1";
    $count_q = "SELECT COUNT(*) AS cnt FROM csr_templates WHERE 1=1";

    if ($user->admin != "1") {
        $select  .= " AND t_id = :t_id";
        $count_q .= " AND t_id = :t_id";
        $vars['t_id'] = $user->t_id;
    }

    if (!empty($_POST['search'])) {
        $select  .= " AND (name LIKE :search OR org LIKE :search OR country LIKE :search)";
        $count_q .= " AND (name LIKE :search OR org LIKE :search OR country LIKE :search)";
        $vars['search'] = "%" . $_POST['search'] . "%";
    }

    $select .= " ORDER BY name ASC";

    if (is_numeric($_POST['limit'] ?? '')) {
        $select .= " LIMIT " . (int)$_POST['limit'];
    }
    if (is_numeric($_POST['offset'] ?? '')) {
        $select .= " OFFSET " . (int)$_POST['offset'];
    }

    $tpls     = $Database->getObjectsQuery($select, $vars);
    $tpls_all = $Database->getObjectQuery($count_q, $vars);

    $all_tenants = $user->admin == "1" ? $Tenants->get_all() : [];

    $edit_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>';
    $del_icon  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';

    $rows = [];
    foreach ($tpls as $t) {
        $key_html = $t->key_algo === 'EC'
            ? "<span class='badge bg-azure-lt'>EC " . ($t->key_size == 256 ? 'P-256' : 'P-384') . "</span>"
            : "<span class='badge bg-blue-lt'>RSA {$t->key_size}</span>";

        $tpl_id   = (int)$t->id;
        $name_esc = htmlspecialchars($t->name, ENT_QUOTES);

        $actions  = "<a class='btn btn-sm bg-info-lt text-info me-1' href='/route/modals/csr-templates/edit.php?id={$tpl_id}' data-bs-toggle='modal' data-bs-target='#modal1'>{$edit_icon} " . _("Edit") . "</a>";
        $actions .= "<button type='button' class='btn btn-sm bg-danger-lt text-danger btn-tpl-delete' data-tpl-id='{$tpl_id}' data-name='{$name_esc}'>{$del_icon} " . _("Delete") . "</button>";

        $row = [
            'name'    => htmlspecialchars($t->name),
            'key'     => $key_html,
            'org'     => htmlspecialchars($t->org ?? ''),
            'country' => htmlspecialchars($t->country ?? ''),
            'actions' => $actions,
        ];

        if ($user->admin == "1") {
            $row['tid'] = isset($all_tenants[$t->t_id]) ? htmlspecialchars($all_tenants[$t->t_id]->name) : $t->t_id;
        }

        $rows[] = $row;
    }

    print json_encode([
        'total'            => (int)($tpls_all->cnt ?? 0),
        'totalNotFiltered' => count($rows),
        'rows'             => $rows,
    ]);

} catch (Exception $e) {
    print json_encode(['total' => 0, 'totalNotFiltered' => 0, 'rows' => [], 'error' => $e->getMessage()]);
}

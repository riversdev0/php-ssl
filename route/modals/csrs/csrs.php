<?php

/**
 * AJAX data for CSR list (Bootstrap Table server-side).
 */

require('../../functions/autoload.php');
$User->validate_session(false, true, false);

try {
    $vars = [];

    $select = "SELECT c.id, c.cn, c.sans, c.csr_pem, c.key_algo, c.key_size, c.status, c.created, c.t_id, c.cert_id, c.renewed_by,
               cert.expires AS cert_expires,
               (pk.id IS NOT NULL AND pk.private_key_enc IS NOT NULL AND pk.private_key_enc != '') AS has_pkey
               FROM csrs c
               LEFT JOIN pkey pk ON c.pkey_id = pk.id
               LEFT JOIN certificates cert ON c.cert_id = cert.id
               WHERE 1=1";

    $count_q = "SELECT COUNT(*) AS cnt FROM csrs c WHERE 1=1";

    if ($user->admin != "1") {
        $select  .= " AND c.t_id = :t_id";
        $count_q .= " AND c.t_id = :t_id";
        $vars['t_id'] = $user->t_id;
    }

    if (!empty($_POST['search'])) {
        $select  .= " AND (c.cn LIKE :search OR c.sans LIKE :search OR c.org LIKE :search)";
        $count_q .= " AND (c.cn LIKE :search OR c.sans LIKE :search OR c.org LIKE :search)";
        $vars['search'] = "%" . $_POST['search'] . "%";
    }

    $valid_sorts = ['cn', 'status', 'created'];
    if (!empty($_POST['sort']) && in_array($_POST['sort'], $valid_sorts)) {
        $select .= " ORDER BY c." . $_POST['sort'] . " " . ($_POST['order'] === 'asc' ? 'ASC' : 'DESC');
    } else {
        $select .= " ORDER BY c.created DESC";
    }

    if (is_numeric($_POST['limit'] ?? '')) {
        $select .= " LIMIT " . (int)$_POST['limit'];
    }
    if (is_numeric($_POST['offset'] ?? '')) {
        $select .= " OFFSET " . (int)$_POST['offset'];
    }

    $csrs     = $Database->getObjectsQuery($select, $vars);
    $csrs_all = $Database->getObjectQuery($count_q, $vars);

    // Tenants (for admin view)
    $all_tenants = $user->admin == "1" ? $Tenants->get_all() : [];

    $renew_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>';
    $key_icon  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" /><circle cx="15" cy="9" r="1" fill="currentColor" stroke="none" /></svg>';
    $dl_icon   = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>';
    $del_icon  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';
    $cert_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 15m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5" /><path d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -1 1.73" /><path d="M6 9l12 0" /><path d="M6 12l3 0" /><path d="M6 15l2 0" /></svg>';
    $ul_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-upload"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 9l5 -5l5 5" /><path d="M12 4l0 12" /></svg>';

    $rows = [];
    foreach ($csrs as $c) {
        $status_map = [
            'pending'   => ['label' => _("Pending"),   'cls' => 'secondary'],
            'submitted' => ['label' => _("Submitted"), 'cls' => 'info'],
            'signed'    => ['label' => _("Signed"),    'cls' => 'success'],
        ];
        $sm = $status_map[$c->status] ?? ['label' => $c->status, 'cls' => 'secondary'];
        $status_html = "<span class='badge bg-{$sm['cls']}-lt text-{$sm['cls']}'>{$sm['label']}</span>";
        if (!empty($c->renewed_by)) {
            $status_html .= " <span class='badge bg-muted-lt text-muted'>" . _("Renewed") . "</span>";
        }

        $key_html = $c->key_algo === 'EC'
            ? "<span class='badge bg-azure-lt'>EC " . ($c->key_size == 256 ? 'P-256' : 'P-384') . "</span>"
            : "<span class='badge bg-blue-lt'>RSA {$c->key_size}</span>";

        if ($c->has_pkey) {
            $key_html .= " <span class='badge bg-green-lt' data-tippy-content='" . _("Private key stored") . "'>{$key_icon}</span>";
        } else {
            $key_html .= " <span class='badge bg-danger-lt text-danger' data-tippy-content='" . _("Private key not stored") . "'>{$key_icon}</span>";
        }

        $sans_display = '';
        $sans_arr = [];
        if (!empty($c->sans)) {
            $sans_arr = array_values(array_filter(array_map('trim', explode("\n", $c->sans))));
        } elseif (!empty($c->csr_pem)) {
            $sans_arr = array_values(array_filter($SSL->csr_extract_sans($c->csr_pem), fn($s) => $s !== $c->cn));
        }
        if (!empty($sans_arr)) {
            $first = htmlspecialchars(reset($sans_arr));
            $extra = count($sans_arr) - 1;
            $sans_display = $first . ($extra > 0 ? " <span class='text-muted small'>+{$extra}</span>" : "");
        }

        $csr_id = (int)$c->id;
        $cn_esc = htmlspecialchars($c->cn, ENT_QUOTES);

        if ($c->has_pkey) {
            $actions = "<a class='btn btn-sm bg-azure-lt text-azure me-1'"
                     . " href='/route/modals/csrs/create.php?csr_id={$csr_id}'"
                     . " data-bs-toggle='modal' data-bs-target='#modal2'>"
                     . "{$renew_icon} " . _("Renew") . "</a>";
        } else {
            $actions = "<button type='button' class='btn btn-sm bg-red-lt text-red me-1 disabled'"
                     . " data-bs-toggle='tooltip' title='" . _("Private key not stored — cannot renew") . "' disabled>"
                     . "{$renew_icon} " . _("Renew") . "</button>";
        }
        $actions .= "<a class='btn btn-sm bg-info-lt text-info me-1' href='/route/ajax/csr/download.php?csr_id={$csr_id}&type=csr'>{$dl_icon} .csr</a>";

        if ($c->has_pkey) {
            $actions .= "<a class='btn btn-sm bg-info-lt text-info me-1' href='/route/ajax/csr/download.php?csr_id={$csr_id}&type=pkey'>{$dl_icon} .key</a>";
        } else {
            $actions .= "<a class='btn btn-sm bg-red-lt text-red me-1 disabled' data-bs-toggle='tooltip' title='"._("Private key is missing")."' href='/route/ajax/csr/download.php?csr_id={$csr_id}&type=pkey'>{$dl_icon} .key</a>";
        }

        if (!empty($c->cert_id)) {
            $actions .= "<a class='btn btn-sm bg-info-lt text-info me-1' href='/route/ajax/cert-download.php?cert_id={$c->cert_id}'>{$dl_icon} .crt</a>";
        }

        if ($c->status !== 'signed') {
            $actions .= "<a class='btn btn-sm bg-success-lt text-success me-1'"
                      . " href='/route/modals/csrs/upload-cert.php?csr_id={$csr_id}'"
                      . " data-bs-toggle='modal' data-bs-target='#modal1'>"
                      . "{$ul_icon} " . _(".crt") . "</a>";
        }

        $actions .= "<button type='button' class='btn btn-sm bg-danger-lt text-danger btn-csr-delete' data-csr-id='{$csr_id}' data-cn='{$cn_esc}'>{$del_icon} " ."". "</button>";

        $cn_link = "<a href='/route/modals/csrs/details.php?csr_id={$csr_id}'"
                 . " data-bs-toggle='modal' data-bs-target='#modal1'"
                 . " style='text-decoration:none;color:inherit;'>"
                 . "<b>" . htmlspecialchars($c->cn) . "</b></a>";

        // Certificate validity status
        if (empty($c->cert_expires)) {
            $cert_status_html = "<span class='text-muted'>—</span>";
        } else {
            $now      = time();
            $exp_ts   = strtotime($c->cert_expires);
            $days     = (int)(($exp_ts - $now) / 86400);
            $exp_fmt  = date('Y-m-d', $exp_ts);
            if ($exp_ts < $now) {
                $cert_status_html = "<span class='badge bg-danger-lt text-danger'>" . _("Expired") . "</span>"
                                  . " <span class='text-muted small'>{$exp_fmt}</span>";
            } elseif ($days <= 30) {
                $cert_status_html = "<span class='badge bg-warning-lt text-warning'>" . _("Expiring") . "</span>"
                                  . " <span class='text-muted small'>{$exp_fmt}</span>";
            } else {
                $cert_status_html = "<span class='badge bg-success-lt text-success'>" . _("Valid") . "</span>"
                                  . " <span class='text-muted small'>{$exp_fmt}</span>";
            }
        }

        $row = [
            'cn'          => $cn_link,
            'key'         => $key_html,
            'sans'        => $sans_display,
            'status'      => $status_html,
            'cert_status' => $cert_status_html,
            'created'     => "<span class='text-secondary'>" . date('Y-m-d', strtotime($c->created)) . "</span>",
            'actions'     => $actions,
        ];

        if ($user->admin == "1") {
            $row['tid'] = isset($all_tenants[$c->t_id]) ? htmlspecialchars($all_tenants[$c->t_id]->name) : $c->t_id;
        }

        $rows[] = $row;
    }

    print json_encode([
        'total'            => (int)($csrs_all->cnt ?? 0),
        'totalNotFiltered' => count($rows),
        'rows'             => $rows,
    ]);

} catch (Exception $e) {
    print json_encode(['total' => 0, 'totalNotFiltered' => 0, 'rows' => [], 'error' => $e->getMessage()]);
}

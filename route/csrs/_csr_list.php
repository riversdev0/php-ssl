<?php
$User->validate_session();

$all_tenants = $Tenants->get_all();

$source_filter = $is_local ? " AND c.source = 'internal'" : " AND c.source = 'external'";

$tenant_filter = $user->admin != "1" ? " AND c.t_id = " . (int)$user->t_id : "";

$select = "SELECT c.id, c.cn, c.sans, c.csr_pem, c.key_algo, c.key_size, c.status, c.created, c.t_id, c.cert_id, c.renewed_by,
           cert.expires AS cert_expires,
           (pk.id IS NOT NULL AND pk.private_key_enc IS NOT NULL AND pk.private_key_enc != '') AS has_pkey
           FROM csrs c
           LEFT JOIN pkey pk ON c.pkey_id = pk.id
           LEFT JOIN certificates cert ON c.cert_id = cert.id
           WHERE 1=1{$tenant_filter}{$source_filter}
           ORDER BY c.created DESC";

$all_csrs = $Database->getObjectsQuery($select, []);

$groups = [];
if ($user->admin == "1") {
    foreach ($all_tenants as $t) { $groups[$t->id] = []; }
}
foreach ($all_csrs as $c) { $groups[$c->t_id][] = $c; }

$page_title   = $is_local ? _("Internal CSR Requests") : _("External CSR Requests");
$page_desc    = $is_local
    ? _("CSRs generated locally with a stored private key.")
    : _("CSRs submitted externally — private key is not stored.");
?>

<div class="page-header">
	<h2 class="page-title"><?php print $url_items['csrs']['icon'] . " " . $page_title; ?></h2>
	<hr>
</div>

<p class='text-secondary'><?php print $page_desc; ?></p>

<div style="margin-bottom:10px">
<?php if ($is_local): ?>
	<a href="/route/modals/csrs/create.php"
	   class="btn btn-sm bg-azure-lt text-green"
	   data-bs-toggle="modal" data-bs-target="#modal2">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
		<?php print _("New CSR"); ?>
	</a>
<?php endif; ?>
	<a href="/route/modals/csrs/import.php<?php print $is_local ? '' : '?external=1'; ?>"
	   class="btn btn-sm bg-azure-lt text-azure"
	   data-bs-toggle="modal" data-bs-target="#modal2">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 9l5 -5l5 5" /><path d="M12 4l0 12" /></svg>
		<?php print _("Import CSR"); ?>
	</a>
</div>

<div class="card">
<div class="card-body p-0">
<div class="table-responsive">
<table
	class="table table-hover align-top table-md"
	data-classes='table table-hover table-sm'
	id="csr-table"
	data-toggle="table"
	data-search="true"
	data-pagination="true"
	data-page-size="50"
	data-page-list="[10, 25, 50, 100, All]"
>
<thead>
	<tr>
		<th data-field="cn"><?php print _("Common name"); ?></th>
		<th data-field="key" class="d-none d-lg-table-cell"><?php print _("Key"); ?></th>
		<th data-field="sans" class="d-none d-lg-table-cell"><?php print _("Alt. names"); ?></th>
		<th data-field="status"><?php print _("Status"); ?></th>
		<th data-field="cert_status" class="d-none d-lg-table-cell"><?php print _("Certificate"); ?></th>
		<th data-field="created" class="d-none d-lg-table-cell"><?php print _("Created"); ?></th>
		<th class="text-end"><?php print _("Actions"); ?></th>
	</tr>
</thead>
<tbody>
<?php

$sign_icon  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 12l2 2l4 -4" /><path d="M11 3a8 8 0 1 0 0 16a8 8 0 0 0 0 -16" /><path d="M21 21l-1.5 -1.5" /></svg>';
$renew_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>';
$key_icon   = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" /><circle cx="15" cy="9" r="1" fill="currentColor" stroke="none" /></svg>';
$dl_icon    = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>';
$del_icon   = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';
$ul_icon    = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 9l5 -5l5 5" /><path d="M12 4l0 12" /></svg>';

if (empty($groups)) {
    print "<tr><td colspan='7' class='text-muted'>" . _("No CSRs found.") . "</td></tr>";
} else {
    foreach ($groups as $tenant_id => $csrs) {
        if ($user->admin == "1") {
            $tenant_name = isset($all_tenants[$tenant_id]) ? htmlspecialchars($all_tenants[$tenant_id]->name) : $tenant_id;
            print "<tr class='header'>";
            print "  <td colspan='7' style='padding-top:20px'>" . $url_items['tenants']['icon'] . " " . _("Tenant") . " <span style='color:var(--tblr-info);'>" . $tenant_name . "</span></td>";
            print "</tr>";
        }

        if (empty($csrs)) {
            $empty_msg = $is_local ? _("No internal CSRs for this tenant.") : _("No external CSRs for this tenant.");
            print "<tr><td colspan='7'><div class='alert alert-info py-2 mb-0'>{$empty_msg}</div></td></tr>";
            continue;
        }

        foreach ($csrs as $c) {
            $csr_id  = (int)$c->id;
            $cn_esc  = htmlspecialchars($c->cn, ENT_QUOTES);

            // Key badge
            $key_html = $c->key_algo === 'EC'
                ? "<span class='badge bg-azure-lt'>EC " . ($c->key_size == 256 ? 'P-256' : 'P-384') . "</span>"
                : "<span class='badge bg-azure-lt'>RSA {$c->key_size}</span>";
            if ($is_local) {
                $key_html .= " <span class='badge bg-green-lt' data-tippy-content='" . _("Private key stored") . "'>{$key_icon}</span>";
            }

            // SANs
            $sans_arr = [];
            if (!empty($c->sans)) {
                $sans_arr = array_values(array_filter(array_map('trim', explode("\n", $c->sans))));
            } elseif (!empty($c->csr_pem)) {
                $sans_arr = array_values(array_filter($SSL->csr_extract_sans($c->csr_pem), function($s) use ($c) { return $s !== $c->cn; }));
            }
            $sans_display = '';
            if (!empty($sans_arr)) {
                $first = htmlspecialchars(reset($sans_arr));
                $extra = count($sans_arr) - 1;
                $sans_display = $first . ($extra > 0 ? " <span class='text-muted small'>+{$extra}</span>" : "");
            }

            // Status
            $status_map = [
                'pending'   => ['label' => _("Pending"),   'cls' => 'secondary'],
                'submitted' => ['label' => _("Submitted"), 'cls' => 'info'],
                'signed'    => ['label' => _("Signed"),    'cls' => 'success'],
                'rejected'  => ['label' => _("Rejected"),  'cls' => 'danger'],
            ];
            if (!empty($c->renewed_by)) {
                $status_html = "<span class='badge bg-muted-lt text-muted'>" . _("Renewed") . "</span>";
            } else {
                $sm = $status_map[$c->status] ?? ['label' => $c->status, 'cls' => 'secondary'];
                $status_html = "<span class='badge bg-{$sm['cls']}-lt text-{$sm['cls']}'>{$sm['label']}</span>";
            }

            // Certificate status
            if (empty($c->cert_expires)) {
                $cert_status_html = "<span class='text-muted'>—</span>";
            } else {
                $now     = time();
                $exp_ts  = strtotime($c->cert_expires);
                $days_l  = (int)(($exp_ts - $now) / 86400);
                $exp_fmt = date('Y-m-d', $exp_ts);
                if ($exp_ts < $now) {
                    $cert_status_html = "<span class='badge bg-danger-lt text-danger'>" . _("Expired") . "</span> <span class='text-muted small'>{$exp_fmt}</span>";
                } elseif ($days_l <= 30) {
                    $cert_status_html = "<span class='badge bg-warning-lt text-warning'>" . _("Expiring") . "</span> <span class='text-muted small'>{$exp_fmt}</span>";
                } else {
                    $cert_status_html = "<span class='badge bg-success-lt text-success'>" . _("Valid") . "</span> <span class='text-muted small'>{$exp_fmt}</span>";
                }
            }

            // Actions
            $actions = '';

            if ($c->status !== 'signed' && $c->status !== 'rejected' && empty($c->renewed_by)) {
                $actions .= "<a class='btn btn-sm bg-purple-lt text-purple me-1'"
                          . " href='/route/modals/csrs/sign.php?csr_id={$csr_id}'"
                          . " data-bs-toggle='modal' data-bs-target='#modal1'>"
                          . "{$sign_icon} " . _("Sign") . "</a>";
            }

            if ($is_local) {
                $actions .= "<a class='btn btn-sm bg-azure-lt text-azure me-1'"
                         . " href='/route/modals/csrs/create.php?csr_id={$csr_id}'"
                         . " data-bs-toggle='modal' data-bs-target='#modal2'>"
                         . "{$renew_icon} " . _("Renew") . "</a>";
                if (!empty($c->has_pkey)) {
                    if ($user->admin == "1" || (int)$user->permission >= 3) {
                        $actions .= "<a class='btn btn-sm bg-info-lt text-info me-1' href='/route/ajax/csr/download.php?csr_id={$csr_id}&type=pkey'>{$dl_icon} .key</a>";
                    } else {
                        $actions .= "<a class='btn btn-sm bg-danger-lt text-danger me-1 disabled' tabindex='-1' title='" . _("Insufficient permissions") . "'>{$dl_icon} .key</a>";
                    }
                }
            }

            $actions .= "<a class='btn btn-sm bg-info-lt text-info me-1' href='/route/ajax/csr/download.php?csr_id={$csr_id}&type=csr'>{$dl_icon} .csr</a>";

            if (!empty($c->cert_id)) {
                $actions .= "<a class='btn btn-sm bg-info-lt text-info me-1' href='/route/ajax/cert-download.php?cert_id={$c->cert_id}'>{$dl_icon} .crt</a>";
            }

            if ($c->status !== 'signed' && $c->status !== 'rejected') {
                $actions .= "<a class='btn btn-sm bg-success-lt text-success me-1'"
                          . " href='/route/modals/csrs/upload-cert.php?csr_id={$csr_id}'"
                          . " data-bs-toggle='modal' data-bs-target='#modal1'>"
                          . "{$ul_icon} " . _(".crt") . "</a>";
            }

            $actions .= "<button type='button' class='btn btn-sm bg-danger-lt text-danger btn-csr-delete' data-csr-id='{$csr_id}' data-cn='{$cn_esc}'>{$del_icon}</button>";

            $cn_link = "<a href='/route/modals/csrs/details.php?csr_id={$csr_id}'"
                     . " data-bs-toggle='modal' data-bs-target='#modal1'"
                     . " style='text-decoration:none;color:inherit;'>"
                     . "<b>" . htmlspecialchars($c->cn) . "</b></a>";

            print "<tr>";
            print "  <td style='padding-left:15px'><span class='text-muted'>" . $url_items['csrs']['icon'] . "</span> {$cn_link}</td>";
            print "  <td class='d-none d-lg-table-cell'>{$key_html}</td>";
            print "  <td class='d-none d-lg-table-cell text-muted'>{$sans_display}</td>";
            print "  <td>{$status_html}</td>";
            print "  <td class='d-none d-lg-table-cell'>{$cert_status_html}</td>";
            print "  <td class='d-none d-lg-table-cell text-secondary'>" . date('Y-m-d', strtotime($c->created)) . "</td>";
            print "  <td class='text-end'>{$actions}</td>";
            print "</tr>";
        }
    }
}
?>
</tbody>
</table>
</div>
</div>
</div>

<script>
window.refreshCsrTable = function() { location.reload(); };

function initCsrTippy() {
    tippy('#csr-table [data-tippy-content]', { duration: 0, arrow: false, followCursor: false, offset: [0, 10] });
}
$(document).ready(initCsrTippy);
$('#csr-table').on('post-body.bs.table', initCsrTippy);

$(document).on('click', '.btn-csr-delete', function() {
	var id = $(this).data('csr-id');
	var cn = $(this).data('cn');
	if (!confirm(<?php print json_encode(_("Delete CSR for") . ' "'); ?> + cn + '"?')) return;
	$.ajax({
		type: 'POST', url: '/route/ajax/csr/delete.php',
		contentType: 'application/json',
		data: JSON.stringify({ csr_id: id }),
		dataType: 'json',
		success: function(d) {
			if (d.status === 'ok') { location.reload(); }
			else { alert(d.message || <?php print json_encode(_("Error")); ?>); }
		},
		error: function() { alert(<?php print json_encode(_("Error")); ?>); }
	});
});

$('#modal1, #modal2').on('hidden.bs.modal', function () { location.reload(); });
</script>

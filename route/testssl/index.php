<?php
$User->validate_session();

// Dispatch to report detail when a hash is in the URL
if (!empty($_params['app']) && preg_match('/^[a-f0-9]{64}$/', $_params['app'])) {
    include(dirname(__FILE__) . '/report.php');
    return;
}

$TestSSL    = new TestSSL($Database);
$is_admin   = $user->admin === "1";
$all_tenants = $Tenants->get_all();

$all_scans = $TestSSL->get_all((int)$user->t_id, $is_admin);

// Group by tenant
$groups = [];
if ($is_admin) {
    foreach ($all_tenants as $t) { $groups[$t->id] = []; }
}
foreach ($all_scans as $s) { $groups[$s->tenant_id][] = $s; }

$flask_icon = $url_items['testssl']['icon'];
?>

<div class="page-header">
    <h2 class="page-title">
        <?php print $flask_icon; ?>
        <?php print _("testSSL"); ?>
    </h2>
    <hr>
</div>

<?php if (!$testssl_available): ?>
<div class="alert alert-warning">
    <h4><?php print _("testssl.sh submodule not found"); ?></h4>
    <p><?php print _("The testssl.sh submodule is missing. Run the following commands to pull it:"); ?></p>
    <pre>git submodule update --init --recursive
# or if adding for the first time:
git submodule add https://github.com/testssl/testssl.sh.git functions/testSSL</pre>
    <p><?php print _("Scans will fail until the submodule is available on the server."); ?></p>
</div>
<?php endif; ?>

<div style="margin-bottom:10px" class="d-flex align-items-center">
    <a href="/route/modals/testssl/request.php"
       class="btn btn-sm bg-green-lt text-green"
       data-bs-toggle="modal" data-bs-target="#modal1">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
        <?php print _("Request new scan"); ?>
    </a>
    <a href="https://github.com/testssl/testssl.sh" class="btn btn-sm ms-auto" target="_blank" rel="noreferrer">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2">
            <path d="M9 19c-4.3 1.4 -4.3 -2.5 -6 -3m12 5v-3.5c0 -1 .1 -1.4 -.5 -2c2.8 -.3 5.5 -1.4 5.5 -6a4.6 4.6 0 0 0 -1.3 -3.2a4.2 4.2 0 0 0 -.1 -3.2s-1.1 -.3 -3.5 1.3a12.3 12.3 0 0 0 -6.2 0c-2.4 -1.6 -3.5 -1.3 -3.5 -1.3a4.2 4.2 0 0 0 -.1 3.2a4.6 4.6 0 0 0 -1.3 3.2c0 4.6 2.7 5.7 5.5 6c-.6 .6 -.6 1.2 -.5 2v3.5"></path>
        </svg>
        <?php print _("testSSL source code"); ?>
    </a>
</div>

<div class="card">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle table-md" id="testssl-table"
    data-toggle="table"
    data-search="true"
    data-pagination="true"
    data-page-size="25" data-page-list="[10,25,50,All]"
    data-classes='table table-hover table-sm'>
<thead>
<tr>
    <th><?php print _("Hostname"); ?></th>
    <th><?php print _("Port"); ?></th>
    <th><?php print _("Rating"); ?></th>
    <th><?php print _("Status"); ?></th>
    <th class="d-none d-md-table-cell"><?php print _("Requested"); ?></th>
    <th class="d-none d-md-table-cell"><?php print _("Completed"); ?></th>
    <th class="d-none d-md-table-cell"><?php print _("Requested by"); ?></th>
    <th class="text-end"><?php print _("Actions"); ?></th>
</tr>
</thead>
<tbody>
<?php
if (empty($groups)) {
    print "<tr><td colspan='8' class='text-muted'>" . _("No scans found.") . "</td></tr>";
} else {
    foreach ($groups as $tenant_id => $scans) {
        if ($is_admin) {
            $tname = isset($all_tenants[$tenant_id]) ? htmlspecialchars($all_tenants[$tenant_id]->name) : $tenant_id;
            print "<tr class='header'><td colspan='8' style='padding-top:20px'>"
                . $url_items['tenants']['icon'] . " " . _("Tenant")
                . " <span style='color:var(--tblr-info);'>$tname</span></td></tr>";
        }

        if (empty($scans)) {
            print "<tr><td colspan='8'><div class='alert alert-info py-2 mb-0'>" . _("No scans for this tenant.") . "</div></td></tr>";
            continue;
        }

        foreach ($scans as $s) {
            $rating_class = $TestSSL->rating_class($s->rating);
            $rating_html  = $s->rating
                ? "<span class='badge badge-outline text-{$rating_class}'>" . htmlspecialchars($s->rating) . "</span>"
                : "<span class='text-muted'>—</span>";

            $status_map = [
                'Requested' => 'secondary',
                'Scanning'  => 'info',
                'Completed' => 'success',
                'Cancelled' => 'warning',
                'Error'     => 'danger',
            ];
            $sc = $status_map[$s->status] ?? 'secondary';
            $status_html = "<span class='badge bg-{$sc}-lt text-{$sc}'>" . htmlspecialchars(_($s->status)) . "</span>";

            $completed_html = $s->completed
                ? "<span class='text-muted small'>" . date('Y-m-d H:i', strtotime($s->completed)) . "</span>"
                : "<span class='text-muted'>—</span>";

            $requested_html = "<span class='text-muted small'>" . date('Y-m-d H:i', strtotime($s->requested)) . "</span>";

            $tenant_href = $all_tenants[$s->tenant_id]->href ?? $_params['tenant'];

            // Actions
            $actions = '';
            if ($s->status === 'Completed') {
                $actions .= "<a class='btn btn-sm bg-blue-lt text-blue me-1' href='/{$tenant_href}/testssl/{$s->hash}/'>" . _("Report") . "</a>";
            }
            if ($s->status === 'Error') {
                $actions .= "<a class='btn btn-sm bg-info-lt text-danger me-1' href='/{$tenant_href}/testssl/{$s->hash}/'>" . _("Details") . "</a>";
            }
            if ($s->status === 'Requested') {
                $actions .= "<button class='btn btn-sm bg-warning-lt text-warning me-1 btn-testssl-cancel' data-id='{$s->id}'>" . _("Cancel") . "</button>";
            }
            $actions .= "<button class='btn btn-sm bg-info-lt text-danger btn-testssl-delete' data-id='{$s->id}'>" . _("Delete") . "</button>";

            print "<tr>";
            print "<td>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-flask text-muted"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M15 2a1 1 0 0 1 0 2v4.826l3.932 10.814l.034 .077a1.7 1.7 0 0 1 -.002 1.193l-.07 .162a1.7 1.7 0 0 1 -1.213 .911l-.181 .017h-11l-.181 -.017a1.7 1.7 0 0 1 -1.285 -2.266l.039 -.09l3.927 -10.804v-4.823a1 1 0 1 1 0 -2h6zm-2 2h-2v4h2v-4z" /></svg> '."<strong>" . htmlspecialchars($s->hostname) . "</strong></td>";
            print "<td>" . (int)$s->port . "</td>";
            print "<td>{$rating_html}</td>";
            print "<td>{$status_html}</td>";
            print "<td class='d-none d-md-table-cell'>{$requested_html}</td>";
            print "<td class='d-none d-md-table-cell'>{$completed_html}</td>";
            print "<td class='d-none d-md-table-cell text-muted small'>" . htmlspecialchars($s->user_name ?? '—') . "</td>";
            print "<td class='text-end'>{$actions}</td>";
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
$(document).on('click', '.btn-testssl-cancel', function() {
    var id = $(this).data('id');
    if (!confirm(<?php print json_encode(_("Cancel this scan?")); ?>)) return;
    $.post('/route/ajax/testssl-action.php', { action: 'cancel', id: id }, function(d) {
        if (d.status === 'ok') location.reload();
        else alert(d.message || <?php print json_encode(_("Error")); ?>);
    }, 'json');
});
$(document).on('click', '.btn-testssl-delete', function() {
    var id = $(this).data('id');
    if (!confirm(<?php print json_encode(_("Delete this scan record?")); ?>)) return;
    $.post('/route/ajax/testssl-action.php', { action: 'delete', id: id }, function(d) {
        if (d.status === 'ok') location.reload();
        else alert(d.message || <?php print json_encode(_("Error")); ?>);
    }, 'json');
});
$('#modal1').on('hidden.bs.modal', function () { location.reload(); });
</script>

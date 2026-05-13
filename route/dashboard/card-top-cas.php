<?php

if ($user->admin === "1") {
    $top_cas = $Database->getObjectsQuery(
        "SELECT ca.id, ca.name, ca.ski, ca.t_id, t.name AS tenant_name, t.href AS tenant_href,
                COUNT(c.id) AS cert_count
         FROM cas ca
         LEFT JOIN tenants t ON t.id = ca.t_id
         LEFT JOIN certificates c ON c.aki = ca.ski AND c.t_id = ca.t_id AND ca.ski IS NOT NULL AND ca.ski != ''
         GROUP BY ca.id
         ORDER BY cert_count DESC, ca.name ASC
         LIMIT 10",
        []
    );
} else {
    $top_cas = $Database->getObjectsQuery(
        "SELECT ca.id, ca.name, ca.ski, ca.t_id,
                COUNT(c.id) AS cert_count
         FROM cas ca
         LEFT JOIN certificates c ON c.aki = ca.ski AND c.t_id = ca.t_id AND ca.ski IS NOT NULL AND ca.ski != ''
         WHERE ca.t_id = ?
         GROUP BY ca.id
         ORDER BY cert_count DESC, ca.name ASC
         LIMIT 10",
        [(int)$user->t_id]
    );
}

$max_count = !empty($top_cas) ? (int)$top_cas[0]->cert_count : 0;
?>

<div class="card-header">
    <h3 class="card-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-rosette-discount-check me-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 7.2a2.2 2.2 0 0 1 2.2 -2.2h1a2.2 2.2 0 0 0 1.55 -.64l.7 -.7a2.2 2.2 0 0 1 3.12 0l.7 .7c.412 .41 .97 .64 1.55 .64h1a2.2 2.2 0 0 1 2.2 2.2v1c0 .58 .23 1.138 .64 1.55l.7 .7a2.2 2.2 0 0 1 0 3.12l-.7 .7a2.2 2.2 0 0 0 -.64 1.55v1a2.2 2.2 0 0 1 -2.2 2.2h-1a2.2 2.2 0 0 0 -1.55 .64l-.7 .7a2.2 2.2 0 0 1 -3.12 0l-.7 -.7a2.2 2.2 0 0 0 -1.55 -.64h-1a2.2 2.2 0 0 1 -2.2 -2.2v-1a2.2 2.2 0 0 0 -.64 -1.55l-.7 -.7a2.2 2.2 0 0 1 0 -3.12l.7 -.7a2.2 2.2 0 0 0 .64 -1.55v-1" /><path d="M9 12l2 2l4 -4" /></svg>
        <?php print _("Top Certificate Authorities"); ?>
    </h3>
</div>

<?php if (empty($top_cas) || $max_count === 0): ?>
<div class="card-body">
    <div class="text-muted"><?php print _("No certificate authority data found."); ?></div>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-vcenter card-table table-sm">
        <thead>
            <tr>
                <th class="w-1 text-muted">#</th>
                <th><?php print _("Certificate Authority"); ?></th>
                <?php if ($user->admin === "1"): ?>
                <th class="d-none d-md-table-cell"><?php print _("Tenant"); ?></th>
                <?php endif; ?>
                <th class="text-end" style="width:80px"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($top_cas as $i => $ca):
                $cert_count = (int)$ca->cert_count;
                $pct = $max_count > 0 ? round($cert_count / $max_count * 100) : 0;
                $ca_href = $user->admin === "1" ? ($ca->tenant_href ?? $user->href) : $user->href;
            ?>
            <tr>
                <td class="text-muted"><?php print $i + 1; ?></td>
                <td>
                    <div class='progressbg'>
                    <div class="progress progress-3 progressbg-progress" style='height:auto'>
                        <div class="progress-bar avatar bg-azure-lt text-azure-lt-fg" style="width:<?php print $pct; ?>%" role="progressbar" aria-valuenow="<?php print $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="progressbg-text">
                    <a href="/<?php print htmlspecialchars($ca_href); ?>/ca-certificates/">
                        <?php print htmlspecialchars($ca->name); ?>
                    </a>
                    </div>
                    </div>
                </td>
                <?php if ($user->admin === "1"): ?>
                <td class="text-muted d-none d-md-table-cell"><?php print htmlspecialchars($ca->tenant_name ?? ''); ?></td>
                <?php endif; ?>
                <td class="text-end text-muted"><span class='badge'><?php print number_format($cert_count); ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

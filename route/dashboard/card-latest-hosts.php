<?php

if ($user->admin === "1") {
    $latest_hosts = $Database->getObjectsQuery(
        "SELECT h.id, h.hostname, h.port, h.ip, h.last_check, h.last_change, h.ignore,
                z.name AS zone_name, z.id AS z_id,
                t.href AS tenant_href, t.name AS tenant_name
         FROM hosts h
         INNER JOIN zones z ON z.id = h.z_id
         INNER JOIN tenants t ON t.id = z.t_id
         ORDER BY h.last_change DESC
         LIMIT 10",
        []
    );
} else {
    $latest_hosts = $Database->getObjectsQuery(
        "SELECT h.id, h.hostname, h.port, h.ip, h.last_check, h.last_change, h.ignore,
                z.name AS zone_name, z.id AS z_id,
                ? AS tenant_href
         FROM hosts h
         INNER JOIN zones z ON z.id = h.z_id
         WHERE z.t_id = ?
         ORDER BY h.last_change DESC
         LIMIT 10",
        [$user->href, (int)$user->t_id]
    );
}
?>

<div class="card-header">
    <h3 class="card-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-server me-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 4m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M3 12m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M7 8l0 .01" /><path d="M7 16l0 .01" /></svg>
        <?php print _("Latest hosts"); ?>
    </h3>
</div>

<?php if (empty($latest_hosts)): ?>
<div class="card-body">
    <div class="text-muted"><?php print _("No hosts found."); ?></div>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-vcenter card-table table-sm">
        <thead>
            <tr>
                <th><?php print _("Host"); ?></th>
                <th class="d-none d-md-table-cell"><?php print _("Zone"); ?></th>
                <?php if ($user->admin === "1"): ?>
                <th class="d-none d-lg-table-cell"><?php print _("Tenant"); ?></th>
                <?php endif; ?>
                <th class="text-end d-none d-md-table-cell" style="width:130px"><?php print _("Last check"); ?></th>
                <th class="text-end" style="width:130px"><?php print _("Last change"); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($latest_hosts as $host):
                $href        = htmlspecialchars($host->tenant_href ?? $user->href);
                $zone        = htmlspecialchars($host->zone_name ?? '');
                $hostname    = htmlspecialchars($host->hostname);
                $last_check  = $host->last_check  ? date('d. M Y H:i', strtotime($host->last_check))  : '—';
                $last_change = $host->last_change ? date('d. M Y H:i', strtotime($host->last_change)) : '—';
                $port_label  = ($host->port && $host->port != 443) ? '<span class="text-muted ms-1">:' . (int)$host->port . '</span>' : '';
                $ignored     = $host->ignore ? ' <span class="badge bg-secondary-lt text-muted ms-1">' . _("ignored") . '</span>' : '';
            ?>
            <tr>
                <td>
                    <a href="/<?php print $href; ?>/zones/<?php print $zone; ?>/<?php print $hostname; ?>/" style="color:var(--tblr-body-color)">
                        <?php print $hostname; ?>
                    </a><?php print $port_label . $ignored; ?>
                </td>
                <td class="text-muted d-none d-md-table-cell">
                    <a href="/<?php print $href; ?>/zones/<?php print $zone; ?>/" style="color:var(--tblr-muted)">
                        <?php print $zone; ?>
                    </a>
                </td>
                <?php if ($user->admin === "1"): ?>
                <td class="text-muted d-none d-lg-table-cell"><?php print htmlspecialchars($host->tenant_name ?? ''); ?></td>
                <?php endif; ?>
                <td class="text-end text-muted d-none d-md-table-cell" style="white-space:nowrap"><?php print $last_check; ?></td>
                <td class="text-end text-muted" style="white-space:nowrap"><?php print $last_change; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

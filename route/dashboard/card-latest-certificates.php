<?php

if ($user->admin === "1") {
    $latest_certs = $Database->getObjectsQuery(
        "SELECT c.id, c.serial, c.certificate, c.expires, c.created, c.t_id,
                z.name AS zone_name, z.id AS z_id,
                t.href AS tenant_href, t.name AS tenant_name
         FROM certificates c
         INNER JOIN zones z ON z.id = c.z_id
         INNER JOIN tenants t ON t.id = c.t_id
         ORDER BY c.created DESC
         LIMIT 10",
        []
    );
} else {
    $latest_certs = $Database->getObjectsQuery(
        "SELECT c.id, c.serial, c.certificate, c.expires, c.created,
                z.name AS zone_name, z.id AS z_id,
                ? AS tenant_href
         FROM certificates c
         INNER JOIN zones z ON z.id = c.z_id
         WHERE c.t_id = ?
         ORDER BY c.created DESC
         LIMIT 10",
        [$user->href, (int)$user->t_id]
    );
}
?>

<div class="card-header">
    <h3 class="card-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-certificate me-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 15a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5" /><path d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -1 1.73" /><path d="M6 9l12 0" /><path d="M6 12l3 0" /><path d="M6 15l2 0" /></svg>
        <?php print _("Latest certificates"); ?>
    </h3>
</div>

<?php if (empty($latest_certs)): ?>
<div class="card-body">
    <div class="text-muted"><?php print _("No certificates found."); ?></div>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-vcenter card-table table-sm">
        <thead>
            <tr>
                <th><?php print _("Certificate"); ?></th>
                <th class="d-none d-md-table-cell"><?php print _("Zone"); ?></th>
                <?php if ($user->admin === "1"): ?>
                <th class="d-none d-lg-table-cell"><?php print _("Tenant"); ?></th>
                <?php endif; ?>
                <th class="text-end" style="width:110px"><?php print _("Expires"); ?></th>
                <th class="text-end d-none d-md-table-cell" style="width:130px"><?php print _("Added"); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($latest_certs as $cert):
                $parsed     = @openssl_x509_parse($cert->certificate);
                $cn         = htmlspecialchars($parsed['subject']['CN'] ?? $cert->serial);
                $serial_dec = $parsed['serialNumber'] ?? '';
                $href       = htmlspecialchars($cert->tenant_href ?? $user->href);
                $zone       = htmlspecialchars($cert->zone_name ?? '');
                $expires    = $cert->expires ? date('d. M Y', strtotime($cert->expires)) : '—';
                $added      = $cert->created ? date('d. M Y H:i', strtotime($cert->created)) : '—';

                $now_ts     = time();
                $exp_ts     = $cert->expires ? strtotime($cert->expires) : 0;
                $days_left  = $exp_ts ? (int)(($exp_ts - $now_ts) / 86400) : null;
                if ($days_left === null)          { $exp_class = 'text-muted'; }
                elseif ($days_left < 0)           { $exp_class = 'text-danger'; }
                elseif ($days_left <= ($user->days ?? 20)) { $exp_class = 'text-warning'; }
                else                              { $exp_class = 'text-muted'; }
            ?>
            <tr>
                <td>
                    <a href="/<?php print $href; ?>/certificates/<?php print $zone; ?>/<?php print htmlspecialchars($serial_dec); ?>/" style="color:var(--tblr-body-color)">
                        <?php print $cn; ?>
                    </a>
                </td>
                <td class="text-muted d-none d-md-table-cell">
                    <a href="/<?php print $href; ?>/zones/<?php print $zone; ?>/" style="color:var(--tblr-muted)">
                        <?php print $zone; ?>
                    </a>
                </td>
                <?php if ($user->admin === "1"): ?>
                <td class="text-muted d-none d-lg-table-cell"><?php print htmlspecialchars($cert->tenant_name ?? ''); ?></td>
                <?php endif; ?>
                <td class="text-end <?php print $exp_class; ?>" style="white-space:nowrap"><?php print $expires; ?></td>
                <td class="text-end text-muted d-none d-md-table-cell" style="white-space:nowrap"><?php print $added; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

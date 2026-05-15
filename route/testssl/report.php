<?php
// Included from index.php when $_params['app'] is a scan hash.

$scan_hash = $_params['app'];
$TestSSL   = new TestSSL($Database);
$scan      = $TestSSL->get_by_hash_auth($scan_hash, (int)$user->t_id, $user->admin === "1");

if (!$scan) {
    $Result->show('danger', _("Scan not found or access denied."), false);
    return;
}

$scan_id     = (int)$scan->id;
$tenant_href = $_params['tenant'];
$sections    = $scan->status === 'Completed' ? $TestSSL->parse_result($scan->json_result) : [];
$rating_class = $TestSSL->rating_class($scan->rating);

$flask_icon = $url_items['testssl']['icon'];
?>

<div class="page-header">
    <h2 class="page-title">
        <?php print $flask_icon; ?> <?php print _("testSSL Report"); ?>
    </h2>
    <hr>
</div>

<div style="margin-bottom:10px">
    <a href="/<?php print $tenant_href; ?>/testssl/" class="btn btn-sm btn-outline-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg>
        <?php print _("Back to list"); ?>
    </a>
    <?php if ($scan->status === 'Completed'): ?>
    <a href="/route/ajax/testssl-export.php?id=<?php print $scan_id; ?>&format=json" class="btn btn-sm bg-secondary-lt ms-1"><?php print _("Download JSON"); ?></a>
    <a href="/route/ajax/testssl-export.php?id=<?php print $scan_id; ?>&format=csv"  class="btn btn-sm bg-secondary-lt ms-1"><?php print _("Download CSV"); ?></a>
    <a href="/report/<?php print htmlspecialchars($scan->hash); ?>/" target="_blank" class="btn btn-sm bg-info-lt text-info ms-1"><?php print _("Public link"); ?></a>
    <?php endif; ?>
</div>

<?php include(dirname(__FILE__) . '/report-body.php'); ?>

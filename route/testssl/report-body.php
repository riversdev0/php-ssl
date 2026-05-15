<?php
/**
 * Shared testSSL report body.
 * Expects: $scan (object), $TestSSL (TestSSL), $sections (array), $rating_class (string)
 */
$status_map = [
    'Requested' => 'secondary',
    'Scanning'  => 'info',
    'Completed' => 'success',
    'Cancelled' => 'warning',
    'Error'     => 'danger',
];
$sc = $status_map[$scan->status] ?? 'secondary';
?>

<!-- General info card -->
<div class="row mb-4">
<div class="col-12 col-md-6">
<div class="card">
<div class="card-header"><h3 class="card-title"><?php print _("Scan details"); ?></h3></div>
<div class="card-body">
<table class="table table-sm table-borderless mb-0">
<tr>
    <td class="text-muted" style="width:40%"><?php print _("Hostname"); ?></td>
    <td><strong><?php print htmlspecialchars($scan->hostname); ?></strong></td>
</tr>
<tr>
    <td class="text-muted"><?php print _("Port"); ?></td>
    <td><?php print (int)$scan->port; ?></td>
</tr>
<tr>
    <td class="text-muted"><?php print _("Status"); ?></td>
    <td><span class="badge bg-<?php print $sc; ?>-lt text-<?php print $sc; ?>"><?php print _($scan->status); ?></span></td>
</tr>
<tr>
    <td class="text-muted"><?php print _("Rating"); ?></td>
    <td>
    <?php if ($scan->rating): ?>
        <span class="badge badge-outline text-<?php print $rating_class; ?> fs-4 px-3"><?php print htmlspecialchars($scan->rating); ?></span>
    <?php else: ?>
        <span class="text-muted">—</span>
    <?php endif; ?>
    </td>
</tr>
<tr>
    <td class="text-muted"><?php print _("Requested"); ?></td>
    <td class="text-muted"><?php print $scan->requested ? date('Y-m-d H:i:s', strtotime($scan->requested)) : '—'; ?></td>
</tr>
<tr>
    <td class="text-muted"><?php print _("Started"); ?></td>
    <td class="text-muted"><?php print $scan->started ? date('Y-m-d H:i:s', strtotime($scan->started)) : '—'; ?></td>
</tr>
<tr>
    <td class="text-muted"><?php print _("Completed"); ?></td>
    <td class="text-muted"><?php print $scan->completed ? date('Y-m-d H:i:s', strtotime($scan->completed)) : '—'; ?></td>
</tr>
<?php if (isset($scan->user_name)): ?>
<tr>
    <td class="text-muted"><?php print _("Requested by"); ?></td>
    <td><?php print htmlspecialchars($scan->user_name); ?></td>
</tr>
<?php endif; ?>
</table>
</div>
</div>
</div>
</div>

<?php if ($scan->status === 'Error'): ?>
<div class="alert alert-danger">
    <strong><?php print _("Scan error"); ?>:</strong>
    <pre class="mb-0 mt-2"><?php print htmlspecialchars($scan->error_message ?? ''); ?></pre>
</div>
<?php elseif ($scan->status === 'Scanning'): ?>
<div class="alert alert-info">
    <?php print _("Scan is currently running. Reload the page to check progress."); ?>
</div>
<?php elseif ($scan->status === 'Requested'): ?>
<div class="alert alert-secondary">
    <?php print _("Scan is queued and will start on the next cron run."); ?>
</div>
<?php elseif ($scan->status === 'Completed' && !empty($sections)): ?>

<div class="row g-3">
<?php foreach ($sections as $sec_key => $section): ?>
<div class="col-12">
<div class="card h-100">
<div class="card-header">
    <h3 class="card-title"><?php print htmlspecialchars($section['title']); ?></h3>
</div>
<div class="card-body p-0">
<table class="table table-sm table-hover mb-0">
<thead>
<tr>
    <th style="width:40%"><?php print _("Check"); ?></th>
    <th><?php print _("Finding"); ?></th>
    <th style="width:90px"><?php print _("Severity"); ?></th>
</tr>
</thead>
<tbody>
<?php foreach ($section['items'] as $item):
    $sev_class = $TestSSL->severity_class($item['severity']);
    $sev_label = $item['severity'] ?: '—';
?>
<tr>
    <td class="text-muted small" title="<?php print htmlspecialchars($item['id']); ?>"><?php print htmlspecialchars($item['label']); ?></td>
    <td class="small" style="word-break:break-word"><?php print htmlspecialchars($item['finding']); ?></td>
    <td><span class="badge bg-<?php print $sev_class; ?>-lt text-<?php print $sev_class; ?>"><?php print htmlspecialchars($sev_label); ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
<?php endforeach; ?>
</div>

<?php elseif ($scan->status === 'Completed'): ?>
<div class="alert alert-warning"><?php print _("Scan completed but no result data was stored."); ?></div>
<?php endif; ?>

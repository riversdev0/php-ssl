<?php
$User->validate_session();

$can_manage = $user->admin == "1" || (int)$user->permission >= 3;

$all_tpls    = $Database->getObjectsQuery("SELECT * FROM csr_templates" . ($user->admin != "1" ? " WHERE t_id = " . (int)$user->t_id : "") . " ORDER BY name ASC", []);
$all_tenants = $Tenants->get_all();

// Group by tenant
$groups = [];
if ($user->admin == "1") {
    foreach ($all_tenants as $t) { $groups[$t->id] = []; }
}
foreach ($all_tpls as $tpl) { $groups[$tpl->t_id][] = $tpl; }
?>

<div class="page-header">
	<h2 class="page-title"><?php print $url_items['csrs']['icon']." "._("CSR Templates"); ?></h2>
	<hr>
</div>

<p class='text-secondary'><?php print _("Saved organisation and key settings for quick CSR generation."); ?></p>

<div style="margin-bottom:10px">
	<?php if ($can_manage): ?>
	<a href="/route/modals/csr-templates/edit.php"
	   class="btn btn-sm bg-info-lt text-green"
	   data-bs-toggle="modal" data-bs-target="#modal1">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
		<?php print _("Add template"); ?>
	</a>
	<?php else: ?>
	<button type="button" class="btn btn-sm bg-danger-lt text-danger disabled" tabindex="-1" title="<?php print _("Insufficient permissions"); ?>">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
		<?php print _("Add template"); ?>
	</button>
	<?php endif; ?>
</div>

<div class="card">
<div class="card-body p-0">
<div class="table-responsive">
<table
	class="table table-hover align-top table-md"
	data-classes='table table-hover table-sm'
	data-toggle="table"
	data-search="true"
	data-pagination="true"
	data-page-size="50"
	data-page-list="[25, 50, 100, All]"
>
<thead>
	<tr>
		<th data-field="name"><?php print _("Name"); ?></th>
		<th data-field="key"><?php print _("Key"); ?></th>
		<th data-field="org" class="d-none d-lg-table-cell"><?php print _("Organization"); ?></th>
		<th data-field="country" class="d-none d-lg-table-cell"><?php print _("Country"); ?></th>
		<th class="text-end"><?php print _("Actions"); ?></th>
	</tr>
</thead>
<tbody>
<?php

$edit_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>';
$del_icon  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';

if (empty($groups)) {
    print "<tr><td colspan='5' class='text-muted'>" . _("No templates found.") . "</td></tr>";
} else {
    foreach ($groups as $tenant_id => $tpls) {
        if ($user->admin == "1") {
            $tenant_name = isset($all_tenants[$tenant_id]) ? htmlspecialchars($all_tenants[$tenant_id]->name) : $tenant_id;
            print "<tr class='header'>";
            print "  <td colspan='5' style='padding-top:20px'>" . $url_items['tenants']['icon'] . " " . _("Tenant") . " <span style='color:var(--tblr-info);'>" . $tenant_name . "</span></td>";
            print "</tr>";
        }

        if (empty($tpls)) {
            print "<tr><td colspan='5'><div class='alert alert-info py-2 mb-0'>" . _("No templates for this tenant.") . "</div></td></tr>";
            continue;
        }

        foreach ($tpls as $t) {
            $key_html = $t->key_algo === 'EC'
                ? "<span class='badge bg-azure-lt'>EC " . ($t->key_size == 256 ? 'P-256' : 'P-384') . "</span>"
                : "<span class='badge bg-blue-lt'>RSA {$t->key_size}</span>";

            $tpl_id   = (int)$t->id;
            $name_esc = htmlspecialchars($t->name, ENT_QUOTES);
            if ($can_manage) {
                $actions  = "<a class='btn btn-sm bg-info-lt text-info me-1' href='/route/modals/csr-templates/edit.php?id={$tpl_id}' data-bs-toggle='modal' data-bs-target='#modal1'>{$edit_icon} " . _("Edit") . "</a>";
                $actions .= "<button type='button' class='btn btn-sm bg-danger-lt text-danger btn-tpl-delete' data-tpl-id='{$tpl_id}' data-name='{$name_esc}'>{$del_icon} " . _("Delete") . "</button>";
            } else {
                $actions  = "<button type='button' class='btn btn-sm bg-danger-lt text-danger disabled me-1' tabindex='-1' title='" . _("Insufficient permissions") . "'>{$edit_icon} " . _("Edit") . "</button>";
                $actions .= "<button type='button' class='btn btn-sm bg-danger-lt text-danger disabled' tabindex='-1' title='" . _("Insufficient permissions") . "'>{$del_icon} " . _("Delete") . "</button>";
            }
            $tpl_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-template text-muted"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M4 5a1 1 0 0 1 1 -1h14a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-14a1 1 0 0 1 -1 -1l0 -2" /><path d="M4 13a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -6" /><path d="M14 12l6 0" /><path d="M14 16l6 0" /><path d="M14 20l6 0" /></svg>';

            print "<tr>";
            print "  <td style='padding-left:15px'>" .$tpl_icon. htmlspecialchars($t->name) . "</td>";
            print "  <td>" . $key_html . "</td>";
            print "  <td class='d-none d-lg-table-cell text-muted'>" . htmlspecialchars($t->org ?? '') . "</td>";
            print "  <td class='d-none d-lg-table-cell text-muted'>" . htmlspecialchars($t->country ?? '') . "</td>";
            print "  <td class='text-end'>" . $actions . "</td>";
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
$(document).on('click', '.btn-tpl-delete', function() {
	var id   = $(this).data('tpl-id');
	var name = $(this).data('name');
	if (!confirm(<?php print json_encode(_("Delete template") . ' "'); ?> + name + '"?')) return;
	$.ajax({
		type: 'POST', url: '/route/ajax/csr/template-delete.php',
		contentType: 'application/json',
		data: JSON.stringify({ template_id: id }),
		dataType: 'json',
		success: function(d) {
			if (d.status === 'ok') { location.reload(); }
			else { alert(d.message || <?php print json_encode(_("Error")); ?>); }
		},
		error: function() { alert(<?php print json_encode(_("Error")); ?>); }
	});
});

$('#modal1').on('hidden.bs.modal', function () { location.reload(); });
</script>

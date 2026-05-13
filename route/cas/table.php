<?php
/**
 * Shared CA table rendering.
 * Expects: $groups (array keyed by tenant_id), $all_tenants, $user, $url_items
 * Optional: $show_manage_actions (bool, default true) — show delete + key download buttons
 */
if (!isset($show_manage_actions)) { $show_manage_actions = true; }

/**
 * Sort a flat array of CA objects into parent-before-child DFS order.
 * Returns array of [$ca, $depth] pairs; siblings ordered as received (caller sorts by name).
 * CAs whose parent_ca_id is not present in the set are treated as roots.
 */
function ca_tree_sort(array $cas): array {
    $by_id    = [];
    $children = [];
    $roots    = [];
    foreach ($cas as $ca) { $by_id[(int)$ca->id] = $ca; }
    foreach ($cas as $ca) {
        $pid = (int)($ca->parent_ca_id ?? 0);
        if ($pid && isset($by_id[$pid])) {
            $children[$pid][] = $ca;
        } else {
            $roots[] = $ca;
        }
    }
    $result = [];
    $visit  = function($ca, $depth) use (&$visit, &$children, &$result) {
        $result[] = [$ca, $depth];
        foreach ($children[(int)$ca->id] ?? [] as $child) {
            $visit($child, $depth + 1);
        }
    };
    foreach ($roots as $root) { $visit($root, 0); }
    return $result;
}

$key_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" /><circle cx="15" cy="9" r="1" fill="currentColor" stroke="none" /></svg>';
$del_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';
$dl_icon  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>';
$ca_icon  = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-rosette-discount-check text-muted"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M12.01 2.011a3.2 3.2 0 0 1 2.113 .797l.154 .145l.698 .698a1.2 1.2 0 0 0 .71 .341l.135 .008h1a3.2 3.2 0 0 1 3.195 3.018l.005 .182v1c0 .27 .092 .533 .258 .743l.09 .1l.697 .698a3.2 3.2 0 0 1 .147 4.382l-.145 .154l-.698 .698a1.2 1.2 0 0 0 -.341 .71l-.008 .135v1a3.2 3.2 0 0 1 -3.018 3.195l-.182 .005h-1a1.2 1.2 0 0 0 -.743 .258l-.1 .09l-.698 .697a3.2 3.2 0 0 1 -4.382 .147l-.154 -.145l-.698 -.698a1.2 1.2 0 0 0 -.71 -.341l-.135 -.008h-1a3.2 3.2 0 0 1 -3.195 -3.018l-.005 -.182v-1a1.2 1.2 0 0 0 -.258 -.743l-.09 -.1l-.697 -.698a3.2 3.2 0 0 1 -.147 -4.382l.145 -.154l.698 -.698a1.2 1.2 0 0 0 .341 -.71l.008 -.135v-1l.005 -.182a3.2 3.2 0 0 1 3.013 -3.013l.182 -.005h1a1.2 1.2 0 0 0 .743 -.258l.1 -.09l.698 -.697a3.2 3.2 0 0 1 2.269 -.944zm3.697 7.282a1 1 0 0 0 -1.414 0l-3.293 3.292l-1.293 -1.292l-.094 -.083a1 1 0 0 0 -1.32 1.497l2 2l.094 .083a1 1 0 0 0 1.32 -.083l4 -4l.083 -.094a1 1 0 0 0 -.083 -1.32z" /></svg>';
$angle_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-down-left text-secondary"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M8 8v8h8" /></svg>';

$can_manage = $user->admin === "1" || (int)$user->permission >= 3;
?>
<div class="card">
<div class="card-body p-0">
<div class="table-responsive">
<table
	class="table table-hover align-top table-md"
	data-classes='table table-hover table-sm'
	id="ca-table"
	data-toggle="table"
	data-search="true"
	data-pagination="true"
	data-page-size="25"
	data-page-list="[10, 25, 50, All]"
>
<thead>
	<tr>
		<th><?php print _("Name"); ?></th>
		<th class="d-none d-lg-table-cell"><?php print _("Subject"); ?></th>
		<th class="d-none d-md-table-cell"><?php print _("Parent CA"); ?></th>
		<th><?php print _("Expires"); ?></th>
		<th><?php print _("Key"); ?></th>
		<th class="text-center d-none d-md-table-cell"><?php print _("Certificates"); ?></th>
		<th class="text-center d-none d-md-table-cell"><?php print _("Ign. updates"); ?></th>
		<th class="text-center d-none d-md-table-cell"><?php print _("Ign. expiry"); ?></th>
		<th class="text-end"><?php print _("Actions"); ?></th>
	</tr>
</thead>
<tbody>
<?php
if (empty($groups)) {
	print "<tr><td colspan='9' class='text-muted'>" . _("No certificate authorities found.") . "</td></tr>";
} else {
	foreach ($groups as $tenant_id => $cas) {
		if ($user->admin === "1") {
			$tenant_name = isset($all_tenants[$tenant_id]) ? htmlspecialchars($all_tenants[$tenant_id]->name) : $tenant_id;
			print "<tr class='header'>";
			print "  <td colspan='9' style='padding-top:20px'>" . $url_items['tenants']['icon'] . " " . _("Tenant") . " <span style='color:var(--tblr-info);'>" . $tenant_name . "</span></td>";
			print "</tr>";
		}

		if (empty($cas)) {
			print "<tr><td colspan='9'><div class='alert alert-info py-2 mb-0'>" . _("No CAs for this tenant.") . "</div></td></tr>";
			continue;
		}

		foreach (ca_tree_sort($cas) as [$ca, $depth]) {
			$ca_id    = (int)$ca->id;
			$name_esc = htmlspecialchars($ca->name, ENT_QUOTES);

			// Expiry badge
			$now    = time();
			$exp_ts = strtotime($ca->expires ?? '');
			if (!$exp_ts) {
				$exp_html = "<span class='text-muted'>—</span>";
			} elseif ($exp_ts < $now) {
				$exp_html = "<span class='badge bg-danger-lt text-danger'>" . _("Expired") . "</span> <span class='text-muted small'>" . date('Y-m-d', $exp_ts) . "</span>";
			} elseif (($exp_ts - $now) < 30 * 86400) {
				$exp_html = "<span class='badge bg-warning-lt text-warning'>" . _("Expiring") . "</span> <span class='text-muted small'>" . date('Y-m-d', $exp_ts) . "</span>";
			} else {
				$exp_html = "<span class='text-muted small'>" . date('Y-m-d', $exp_ts) . "</span>";
			}

			// Key badge
			if ($ca->has_pkey) {
				$key_html = "<span class='badge bg-green-lt' data-tippy-content='" . _("Private key stored — can sign") . "'>{$key_icon}</span>";
			} else {
				$key_html = "<span class='badge bg-secondary-lt text-muted' data-tippy-content='" . _("No private key") . "'>{$key_icon}</span>";
			}

			// Actions
			$actions = "<a class='btn btn-sm bg-info-lt text-info me-1' href='/route/ajax/ca/download.php?ca_id={$ca_id}&type=crt'>{$dl_icon} .crt</a>";
			if ($ca->has_pkey && $show_manage_actions) {
				if ($can_manage) {
					$actions .= "<a class='btn btn-sm bg-info-lt text-info me-1' href='/route/ajax/ca/download.php?ca_id={$ca_id}&type=pkey'>{$dl_icon} .key</a>";
				} else {
					$actions .= "<a class='btn btn-sm bg-danger-lt text-danger me-1 disabled' tabindex='-1' title='" . _("Insufficient permissions") . "'>{$dl_icon} .key</a>";
				}
			}
			if ($show_manage_actions && $can_manage) {
				$actions .= "<button type='button' class='btn btn-sm bg-danger-lt text-danger btn-ca-delete' data-ca-id='{$ca_id}' data-name='{$name_esc}'>{$del_icon} " . _("Delete") . "</button>";
			}

			// Indent: one icon-width (20px) per depth level
			$indent = $depth > 0 ? "<span style='display:inline-block;width:" . ($depth * 20) . "px'></span>" : "";
			$angle_icon_show = $depth > 0 ? $angle_icon : "";

			// Ignore flag badges
			$ign_u = (int)($ca->ignore_updates ?? 0);
			$ign_e = (int)($ca->ignore_expiry  ?? 0);
			if ($can_manage) {
				$badge_u = "<span class='badge ca-flag-toggle " . ($ign_u ? "bg-green-lt text-green" : "bg-secondary-lt text-muted") . "' style='cursor:pointer' data-ca-id='{$ca_id}' data-flag='ignore_updates' data-value='{$ign_u}' title='" . _("Click to toggle") . "'>" . ($ign_u ? _("Yes") : _("No")) . "</span>";
				$badge_e = "<span class='badge ca-flag-toggle " . ($ign_e ? "bg-green-lt text-green" : "bg-secondary-lt text-muted") . "' style='cursor:pointer' data-ca-id='{$ca_id}' data-flag='ignore_expiry'  data-value='{$ign_e}' title='" . _("Click to toggle") . "'>" . ($ign_e ? _("Yes") : _("No")) . "</span>";
			} else {
				$badge_u = "<span class='badge " . ($ign_u ? "bg-green-lt text-green" : "bg-secondary-lt text-muted") . "'>" . ($ign_u ? _("Yes") : _("No")) . "</span>";
				$badge_e = "<span class='badge " . ($ign_e ? "bg-green-lt text-green" : "bg-secondary-lt text-muted") . "'>" . ($ign_e ? _("Yes") : _("No")) . "</span>";
			}

			print "<tr data-ca-id='{$ca_id}' data-ignore-updates='{$ign_u}' data-ignore-expiry='{$ign_e}'>";
			print "  <td>{$indent}{$angle_icon_show}{$ca_icon} <a href='/route/modals/cas/view.php?ca_id={$ca_id}' class='text-body' data-bs-toggle='modal' data-bs-target='#modal1'>" . htmlspecialchars($ca->name) . "</a></td>";
			print "  <td class='d-none d-lg-table-cell text-muted small'>" . htmlspecialchars($ca->subject ?? '') . "</td>";
			$parent_html = $ca->parent_ca_name ? "<span class='text-muted small'>" . htmlspecialchars($ca->parent_ca_name) . "</span>" : "<span class='text-muted'>—</span>";
			print "  <td class='d-none d-md-table-cell'>{$parent_html}</td>";
			print "  <td>{$exp_html}</td>";
			print "  <td>{$key_html}</td>";
			$cert_count = (int)($ca->cert_count ?? 0);
			$count_html = $cert_count > 0
				? "<span class='badge bg-blue-lt text-blue'>{$cert_count}</span>"
				: "<span class='text-muted'>—</span>";
			print "  <td class='text-center d-none d-md-table-cell'>{$count_html}</td>";
			print "  <td class='text-center d-none d-md-table-cell'>{$badge_u}</td>";
			print "  <td class='text-center d-none d-md-table-cell'>{$badge_e}</td>";
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
function initCaTippy() {
	tippy('#ca-table [data-tippy-content]', { duration: 0, arrow: false, offset: [0, 10] });
}
$(document).ready(initCaTippy);
$('#ca-table').on('post-body.bs.table', initCaTippy);

$(document).on('click', '.btn-ca-delete', function() {
	var id   = $(this).data('ca-id');
	var name = $(this).data('name');
	if (!confirm(<?php print json_encode(_("Delete CA") . ' "'); ?> + name + '"?')) return;
	$.ajax({
		type: 'POST', url: '/route/ajax/ca/delete.php',
		contentType: 'application/json',
		data: JSON.stringify({ ca_id: id }),
		dataType: 'json',
		success: function(d) {
			if (d.status === 'ok') { location.reload(); }
			else { alert(d.message || <?php print json_encode(_("Error")); ?>); }
		},
		error: function() { alert(<?php print json_encode(_("Error")); ?>); }
	});
});

$('#modal1').on('hidden.bs.modal', function () { location.reload(); });

$(document).on('click', '.ca-flag-toggle', function() {
	var $badge = $(this);
	var ca_id  = $badge.data('ca-id');
	var flag   = $badge.data('flag');
	var newVal = $badge.data('value') == 1 ? 0 : 1;
	var $row   = $('[data-ca-id="' + ca_id + '"]').filter('tr');

	// read sibling flag value from row data attributes
	var ign_u = flag === 'ignore_updates' ? newVal : parseInt($row.data('ignore-updates'));
	var ign_e = flag === 'ignore_expiry'  ? newVal : parseInt($row.data('ignore-expiry'));

	$badge.css('opacity', '0.4');
	$.post('/route/ajax/ca/update-flags.php', { ca_id: ca_id, ignore_updates: ign_u, ignore_expiry: ign_e }, function(d) {
		if (d.success) {
			$row.data('ignore-updates', ign_u).data('ignore-expiry', ign_e);
			$badge.data('value', newVal);
			if (newVal) {
				$badge.removeClass('bg-secondary-lt text-muted').addClass('bg-green-lt text-green').text(<?php print json_encode(_("Yes")); ?>);
			} else {
				$badge.removeClass('bg-green-lt text-green').addClass('bg-secondary-lt text-muted').text(<?php print json_encode(_("No")); ?>);
			}
		} else {
			alert(d.message || <?php print json_encode(_("Error")); ?>);
		}
		$badge.css('opacity', '1');
	}, 'json').fail(function() {
		$badge.css('opacity', '1');
		alert(<?php print json_encode(_("Request failed.")); ?>);
	});
});
</script>

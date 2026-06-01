<?php
# validate user session - requires admin
$User->validate_session (false);

# sub-route: user detail page
if(isset($_params['app'])) {
	include('user/index.php');
	return;
}
?>

<div class="page-header">
	<h2 class="page-title"><?php print $url_items["users"]['icon']; ?> <?php print _("Users"); ?></h2>
	<hr>
</div>

<p class='text-secondary'><?php print _('List of all available users'); ?>.</p>

<div style="margin-bottom:10px">
<?php if ($user->admin == "1"): ?>
	<a href="/route/modals/users/edit.php?action=add&tenant=<?php print urlencode($user->href); ?>" data-bs-toggle="modal" data-bs-target="#modal1" class="btn btn-sm bg-green-lt text-green <?php print $user->actions_disabled; ?>">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
		<?php print _("Create user"); ?>
	</a>
<?php endif; ?>
</div>

<?php

# fetch users
$users = $User->get_all();

# fetch which users have passkeys
$_passkey_user_ids = [];
try {
	$_pk_rows = $Database->getObjectsQuery("SELECT user_id FROM passkeys GROUP BY user_id");
	foreach ($_pk_rows ?: [] as $_pkr) {
		$_passkey_user_ids[(int)$_pkr->user_id] = true;
	}
} catch (Exception $e) {}

# tenants
$tenants = $Tenants->get_all();

# group by tenant
$groups = [];
if ($user->admin == "1") {
	foreach ($tenants as $t) { $groups[$t->id] = []; }
}
foreach ($users as $u) { $groups[$u->t_id][] = $u; }

$edit_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>';
$del_icon  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';
$imp_icon  = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17h5l1.67 -2.386m3.66 -5.227l1.67 -2.387h6" /><path d="M18 4l3 3l-3 3" /><path d="M3 7h5l7 10h6" /><path d="M18 20l3 -3l-3 -3" /></svg>';

?>

<div class="card">
<div class="card-body p-0">
<div class="table-responsive">
<table
	class="table table-hover align-middle table-md"
	data-classes="table table-hover table-sm"
	data-toggle="table"
	data-search="true"
	data-pagination="true"
	data-page-size="25"
	data-page-list="[10, 25, 50, All]"
>
<thead>
	<tr>
		<th><?php print _("Name"); ?></th>
		<th class="d-none d-md-table-cell"><?php print _("Email"); ?></th>
		<th><?php print _("Permission"); ?></th>
		<th class="d-none d-md-table-cell"><?php print _("Login"); ?></th>
		<th class="d-none d-lg-table-cell"><?php print _("Created"); ?></th>
		<th class="d-none d-lg-table-cell"><?php print _("Last active"); ?></th>
		<th class="text-end"><?php print _("Actions"); ?></th>
	</tr>
</thead>
<tbody>
<?php

if (empty($groups)) {
	print "<tr><td colspan='7' class='text-muted'>" . _("No users found.") . "</td></tr>";
} else {
	foreach ($groups as $tenant_id => $group) {

		if ($user->admin == "1") {
			$tenant_href = htmlspecialchars(isset($tenants[$tenant_id]) ? $tenants[$tenant_id]->href : '', ENT_QUOTES, 'UTF-8');
			$tenant_name = htmlspecialchars(isset($tenants[$tenant_id]) ? $tenants[$tenant_id]->name : $tenant_id);
			print "<tr class='header'>";
			print "  <td colspan='7' style='padding-top:20px'>";
			print    $url_items['tenants']['icon'] . " " . _("Tenant") . " <span style='color:var(--tblr-info);'>" . $tenant_name . "</span>";
			print    " <a href='/route/modals/users/edit.php?action=add&tenant=" . $tenant_href . "' data-bs-toggle='modal' data-bs-target='#modal1' class='btn btn-sm bg-green-lt text-green ms-2 float-end " . $user->actions_disabled . "'>";
			print    "<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M12 5l0 14'/><path d='M5 12l14 0'/></svg> " . _("Add user") . "</a>";
			print "  </td>";
			print "</tr>";
		}

		if (empty($group)) {
			print "<tr><td colspan='7'><div class='alert alert-info py-2 mb-0'>" . '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-info-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>'. _("No users for this tenant.") . "</div></td></tr>";
			continue;
		}

		foreach ($group as $u) {
			$u_id          = (int)$u->id;
			$u_tenant_href = htmlspecialchars(isset($tenants[$u->t_id]) ? $tenants[$u->t_id]->href : $user->href, ENT_QUOTES, 'UTF-8');
			$u_email_enc   = htmlspecialchars($u->email, ENT_QUOTES, 'UTF-8');

			// Name badges
			$badges = '';
			if (!empty($u->disabled)) {
				$badges .= " <span class='badge bg-danger-lt text-danger'>" . _("Disabled") . "</span>";
			}

			// Login method cell
			$has_passkey  = !empty($_passkey_user_ids[$u_id]);
			$has_password = !empty($u->password);
			$force_pk     = !empty($u->force_passkey);
			if ($force_pk) {
				$login_html = "<span class='badge bg-purple-lt' data-bs-toggle='tooltip' title='" . _("Password login disabled") . "'>" . _("Passkey only") . "</span>";
			} elseif ($has_passkey && $has_password) {
				$login_html = "<span class='badge bg-azure-lt me-1'>" . _("Passkey") . "</span><span class='badge bg-blue-lt'>" . _("Password") . "</span>";
			} elseif ($has_passkey) {
				$login_html = "<span class='badge bg-azure-lt'>" . _("Passkey") . "</span>";
			} elseif ($has_password) {
				$login_html = "<span class='badge bg-blue-lt'>" . _("Password") . "</span>";
			} else {
				$login_html = "<span class='text-muted'>—</span>";
			}
			if (!empty($u->totp_enabled) && !$force_pk) {
				$login_html .= " <span class='badge bg-green-lt text-green' data-bs-toggle='tooltip' title='" . _("2FA enabled") . "'>2FA</span>";
			}

			// Actions
			$actions = "";

			if ($user->admin == "1" && !isset($_SESSION['impersonate_original']) && $u->email !== $user->email) {
				$actions .= "<a class='btn btn-sm bg-info-lt text-warning' href='/{$user->href}/user/impersonate/{$u_id}/'>{$imp_icon} </a> ";
			}
			$actions .= "<a class='btn btn-sm bg-info-lt text-info me-1 {$user->actions_disabled}' href='/route/modals/users/edit.php?action=edit&tenant={$u_tenant_href}&id={$u_id}' data-bs-toggle='modal' data-bs-target='#modal1'>{$edit_icon} " . _("Edit") . "</a>";
			$actions .= "<a class='btn btn-sm bg-danger-lt text-danger me-1 {$user->actions_disabled}' href='/route/modals/users/edit.php?action=delete&tenant={$u_tenant_href}&id={$u_id}' data-bs-toggle='modal' data-bs-target='#modal1'>{$del_icon} " . _("Delete") . "</a>";

			print "<tr>";
			print "  <td style='padding-left:15px'>".'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user text-secondary"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>'." <a href='/{$u_tenant_href}/users/{$u_email_enc}/' class='text-body fw-medium'>" . htmlspecialchars($u->name, ENT_QUOTES, 'UTF-8') . "</a>{$badges}</td>";
			print "  <td class='d-none d-md-table-cell text-muted small'>{$u_email_enc}</td>";
			print "  <td><span class='badge badge-outline '>" . _($User->get_permissions_nice($u->permission)) . "</span></td>";
			print "  <td class='d-none d-md-table-cell'>{$login_html}</td>";
			print "  <td class='d-none d-lg-table-cell text-secondary small'>" . (!empty($u->create_date) ? htmlspecialchars($u->create_date) : '—') . "</td>";
			print "  <td class='d-none d-lg-table-cell text-secondary small'>" . (!empty($u->last_active) ? htmlspecialchars($u->last_active) : '—') . "</td>";
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

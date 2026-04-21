<?php

# validate user session
$User->validate_session();

# fetch user
$view_user = $Database->getObjectQuery("select * from users where email = ?", [$_params['app']]);

if ($view_user === null) {
	print '<div class="page-header"><h2 class="page-title">'._("Invalid user").'</h2><hr></div>';
	$Result->show("danger", _("User does not exist."), false);
	return;
}

# access check: non-admins can only view users in their own tenant
if ($user->admin != "1" && $view_user->t_id != $user->t_id) {
	print '<div class="page-header"><h2 class="page-title">'._("Access denied").'</h2><hr></div>';
	$Result->show("danger", _("Access denied."), false);
	return;
}

# fetch tenant of the viewed user
$all_tenants_map = $Tenants->get_all();
$view_tenant = isset($all_tenants_map[$view_user->t_id]) ? $all_tenants_map[$view_user->t_id] : null;

# icon
$icon_user = $url_items["users"]['icon'];

?>

<div class='page-header'>
	<h2 class='page-title'><?php print $icon_user; ?> <?php print _("User details"); ?> [<?php print htmlspecialchars($view_user->name); ?>]</h2>
	<hr>
</div>

<div style="margin-bottom:16px;">
	<a href="/<?php print $_params['tenant']; ?>/users/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>
</div>

<div class="card">
	<div class="row g-0">

		<!-- Left: nav pills -->
		<div class="col-12 col-md-3 border-end">
			<div class="card-body">
				<h3 class="card-title"><?php print _("Settings"); ?></h3>
				<div class="list-group list-group-transparent mb-0 border-top">

					<a href="#ud-account" data-bs-toggle="pill"
					   class="list-group-item list-group-item-action d-flex align-items-center gap-2 active">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
						<?php print _("Account"); ?>
					</a>

					<a href="#ud-passkeys" data-bs-toggle="pill"
					   class="list-group-item list-group-item-action d-flex align-items-center gap-2">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" /><circle cx="15" cy="9" r="1" fill="currentColor" stroke="none" /></svg>
						<?php print _("Passkeys"); ?>
					</a>

					<a href="#ud-notifications" data-bs-toggle="pill"
					   class="list-group-item list-group-item-action d-flex align-items-center gap-2">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" /><path d="M9 17v1a3 3 0 0 0 6 0v-1" /></svg>
						<?php print _("Notifications"); ?>
					</a>

					<a href="#ud-logs" data-bs-toggle="pill"
					   class="list-group-item list-group-item-action d-flex align-items-center gap-2">
						<?php print $url_items["logs"]['icon']; ?>
						<?php print _("Activity"); ?>
					</a>

				</div>
			</div>
		</div>

		<!-- Right: tab content -->
		<div class="col-12 col-md-9">
			<div class="tab-content">

				<div class="tab-pane active show" id="ud-account">
					<?php include("user-details.php"); ?>
				</div>

				<div class="tab-pane" id="ud-passkeys">
					<?php include("user-passkeys.php"); ?>
				</div>

				<div class="tab-pane" id="ud-notifications">
					<div class="card-body border-bottom pb-3">
						<h3 class="card-title">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" /><path d="M9 17v1a3 3 0 0 0 6 0v-1" /></svg>
							<?php print _("Notifications"); ?>
						</h3>
					</div>
					<div class="card-body">
						<?php include("user-notifications.php"); ?>
					</div>
				</div>

				<div class="tab-pane" id="ud-logs">
					<div class="card-body border-bottom pb-3">
						<h3 class="card-title">
							<?php print $url_items["logs"]['icon']; ?>
							<?php print _("Activity"); ?>
						</h3>
					</div>
					<div class="card-body">
						<?php include("user-logs.php"); ?>
					</div>
				</div>

			</div>
		</div>

	</div>
</div>

<script>
// Refresh Bootstrap Table when the logs tab is shown (table renders hidden initially)
(function () {
    var logTab = document.querySelector('a[href="#ud-logs"]');
    if (logTab) {
        logTab.addEventListener('shown.bs.tab', function () {
            if (typeof $ !== 'undefined' && $('#table-user-logs').length) {
                $('#table-user-logs').bootstrapTable('refresh');
            }
        });
    }
})();
</script>

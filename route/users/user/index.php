<?php

# validate user session
$User->validate_session();

# user id from URL
$view_user_id = isset($_params['app']) ? (int)$_params['app'] : 0;

if ($view_user_id < 1) {
	print '<div class="page-header"><h2 class="page-title">'._("Invalid user").'</h2><hr></div>';
	$Result->show("danger", _("Invalid user ID."), false);
	return;
}

# fetch user
$view_user = $Database->getObject("users", $view_user_id);

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
	<h2 class='page-title'><?php print $icon_user." "._("User details"); ?> [<?php print htmlspecialchars($view_user->name); ?>]</h2>
	<hr>
</div>

<div>
	<a href="/<?php print $_params['tenant']; ?>/users/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>
</div><br><br>

<div class='row'>
	<div class='col-xs-12 col-sm-12 col-md-6' style='margin-top:10px;'>
		<?php include("user-details.php"); ?>
	</div>
	<div class='col-xs-12 col-sm-12 col-md-6' style='margin-top:10px;'>
		<?php include("user-notifications.php"); ?>
	</div>
</div>

<?php include("user-logs.php"); ?>

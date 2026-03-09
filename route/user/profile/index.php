<?php

# validate session
$User->validate_session();

# the viewed user is always the currently logged-in user
$view_user       = $Database->getObject("users", $user->id);
$all_tenants_map = $Tenants->get_all();
$view_tenant     = isset($all_tenants_map[$view_user->t_id]) ? $all_tenants_map[$view_user->t_id] : null;
$icon_user       = $url_items["users"]['icon'];

?>

<div class='page-header'>
	<h2 class='page-title'><?php print $icon_user." "._("My profile"); ?> [<?php print htmlspecialchars($view_user->name); ?>]</h2>
	<hr>
</div>

<div>
	<a href="/" onClick="history.go(-1); return false;" class="btn btn-sm btn-outline-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg> <?php print _("Back"); ?></a>
</div><br><br>

<div class='row'>
	<div class='col-xs-12 col-sm-12 col-md-6' style='margin-top:10px;'>
		<?php include("profile-details.php"); ?>
	</div>
	<div class='col-xs-12 col-sm-12 col-md-6' style='margin-top:10px;'>
		<?php include(dirname(__FILE__)."/../../users/user/user-notifications.php");  ?>
	</div>
</div>

<?php include(dirname(__FILE__)."/../../users/user/user-logs.php"); ?>
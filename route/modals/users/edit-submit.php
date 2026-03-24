<?php

#
# Edit user - submit
#

# functions
require('../../../functions/autoload.php');
# validate user session
$User->validate_session (false, true, true);
$User->validate_csrf_token ();
# validate permissions
$User->validate_user_permissions (3, true);

# strip tags
$_POST = $User->strip_input_tags ($_POST);

# validate action
if(!$Common->validate_action($_POST['action']))
$Result->show("danger", _("Invalid action").".", true, false, false, false);

# fetch tenant
$tenant = $Tenants->get_tenant_by_href ($_POST['tenant']);
if($tenant===null)
$Result->show("danger", _("Invalid tenant").".", true, false, false, false);

# tenant access - non-admins can only manage their own tenant
if($user->admin !== "1" && $user->t_id !== $tenant->id)
$Result->show("danger", _("Access denied").".", true, false, false, false);

# fetch user to edit/delete
if($_POST['action']!=="add") {
	if(!$Common->validate_int($_POST['id']))
	$Result->show("danger", _("Invalid user ID").".", true, false, false, false);

	$edit_user = $Database->getObject("users", $_POST['id']);
	if($edit_user===null)
	$Result->show("danger", _("Invalid user").".", true, false, false, false);

	# IDOR: verify user belongs to tenant
	if($edit_user->t_id !== $tenant->id)
	$Result->show("danger", _("Access denied").".", true, false, false, false);
}

# validate fields (not on delete)
if($_POST['action']!=="delete") {
	# name
	if($Common->validate_alphanumeric($_POST['name'])===false || strlen(trim($_POST['name']))==0)
	$Result->show("danger", _("Invalid name").".", true, false, false, false);

	# email
	if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
	$Result->show("danger", _("Invalid email address").".", true, false, false, false);

	# password required on add, optional on edit
	if($_POST['action']==="add" && strlen($_POST['password'])==0)
	$Result->show("danger", _("Password is required").".", true, false, false, false);

	# password policy (when password is provided)
	if(strlen($_POST['password']) > 0 && !$Common->validate_password($_POST['password']))
	$Result->show("danger", _("Password must be at least 10 characters and contain an uppercase letter, a lowercase letter, and a number").".", true, false, false, false);

	# permission
	if(!in_array((int)$_POST['permission'], [1, 2, 3]))
	$Result->show("danger", _("Invalid permission level").".", true, false, false, false);

	# days
	if(!is_numeric($_POST['days']) || $_POST['days'] < 1 || $_POST['days'] > 365)
	$Result->show("danger", _("Invalid days value").".", true, false, false, false);

	# days_expired
	if(!is_numeric($_POST['days_expired']) || $_POST['days_expired'] < 1 || $_POST['days_expired'] > 365)
	$Result->show("danger", _("Invalid days expired value").".", true, false, false, false);

	# email uniqueness (on add, or on edit if email changed)
	if($_POST['action']==="add" || $_POST['email'] !== $edit_user->email) {
		$existing = $Database->getObjectQuery("select id from users where email = ?", [$_POST['email']]);
		if($existing!==null)
		$Result->show("danger", _("Email address already in use").".", true, false, false, false);
	}
}

# self-deletion only permitted for permission=3 (admin-level) users
if($_POST['action']==="delete" && $edit_user->id === $user->id && $user->permission != 3)
$Result->show("danger", _("You cannot delete your own account").".", true, false, false, false);

# build update array
$update = [];
if($_POST['action']!=="delete") {
	$update['name']         = trim($_POST['name']);
	$update['email']        = $_POST['email'];
	$update['permission']   = (int)$_POST['permission'];
	$update['days']         = (int)$_POST['days'];
	$update['days_expired'] = (int)$_POST['days_expired'];
	# password - only update if provided
	if(strlen($_POST['password']) > 0) {
		$plain_password      = $_POST['password'];
		$update['password']  = hash('sha512', $plain_password);
	}
	# changePass checkbox (edit only; providing a new password always clears it)
	if($_POST['action'] === "edit") {
		$update['changePass'] = (!empty($_POST['changePass']) && strlen($_POST['password']) === 0) ? 1 : 0;
		$update['disabled']   = !empty($_POST['disabled']) ? 1 : 0;
	}
}

# add - set t_id and force password change on first login
if($_POST['action']==="add") {
	$update['t_id']       = $tenant->id;
	$update['changePass'] = 1;
}

# edit/delete - set id
if($_POST['action']!=="add") {
	$update['id'] = $edit_user->id;
}

# edit: check for actual changes
if($_POST['action']==="edit") {
	$is_change = false;
	foreach(['name', 'email', 'permission', 'days', 'days_expired', 'changePass', 'disabled'] as $k) {
		if(isset($update[$k]) && $edit_user->$k != $update[$k]) {
			$is_change = true;
			break;
		}
	}
	if(isset($update['password'])) { $is_change = true; }

	if($is_change===false)
	$Result->show("info", _("No change").".", true, false, false, false);
}


# ok, validations passed
try {
	# add
	if($_POST['action']==="add") {
		$new_user_id = $Database->insertObject("users", $update);
		# ok
		$Result->show("success", _("User created").".", false, false, false, false);
		# log (omit password)
		$log_data = $update; unset($log_data['password']);
		$Log->write("users", $new_user_id, $tenant->id, $user->id, $_POST['action'], false, "User ".$update['email']." created", null, json_encode(["users"=>["0"=>$log_data]]));

		# send welcome email with login details
		global $mail_sender_settings;
		try {
			$Mail    = new mailer();
			$td_style = "border-left:1px solid #ddd;vertical-align:top;padding:4px 10px;";
			$td_label = "vertical-align:top;padding:4px 10px;white-space:nowrap;color:#666;";
			$login_url = "https://" . $mail_sender_settings->url;

			$rows = [
				$Mail->font_title . _("Your php-ssl account has been created") . "</font><br><br>",
				"<table border='0' cellpadding='3' cellspacing='0'>",
				"<tr><td style='$td_label'>" . $Mail->font_norm . _("Name")     . "</font></td><td style='$td_style'>" . $Mail->font_bold . htmlspecialchars($update['name'],          ENT_QUOTES, 'UTF-8') . "</font></td></tr>",
				"<tr><td style='$td_label'>" . $Mail->font_norm . _("Login")    . "</font></td><td style='$td_style'>" . $Mail->font_bold . htmlspecialchars($update['email'],         ENT_QUOTES, 'UTF-8') . "</font></td></tr>",
				"<tr><td style='$td_label'>" . $Mail->font_norm . _("Password") . "</font></td><td style='$td_style'>" . $Mail->font_bold . htmlspecialchars($plain_password,         ENT_QUOTES, 'UTF-8') . "</font></td></tr>",
				"<tr><td style='$td_label'>" . $Mail->font_norm . _("Tenant")   . "</font></td><td style='$td_style'>" . $Mail->font_norm . htmlspecialchars($tenant->name,           ENT_QUOTES, 'UTF-8') . "</font></td></tr>",
				"<tr><td style='$td_label'>" . $Mail->font_norm . _("URL")      . "</font></td><td style='$td_style'>" . $Mail->font_norm . "<a href='" . $login_url . "' style='color:#003551;'>" . $login_url . "</a></font></td></tr>",
				"</table>",
				"<br>" . $Mail->font_norm . _("You will be asked to change your password on first login.") . "</font>",
				"<br><br>" . $Mail->font_norm . "Visit <a href='" . $mail_sender_settings->www . "' style='color:#003551;'>" . $mail_sender_settings->www . "</a></font>",
			];

			# CC: tenant admins (permission=3), excluding the new user itself
		$tenant_admins = $Database->getObjectsQuery(
			"SELECT email FROM users WHERE t_id = ? AND permission = 3 AND email != ?",
			[$tenant->id, $update['email']]
		);
		$cc = array_column(array_map('get_object_vars', $tenant_admins ?: []), 'email');

		$Mail->send("Telemach php-ssl :: " . _("new user account"), [$update['email']], $cc, [], implode("\n", $rows), false);
		} catch (Exception $e) {
			// mail failure is non-fatal — user is already created
		}
	}
	# update
	elseif($_POST['action']==="edit") {
		# snapshot before (omit password)
		$before = clone $edit_user; unset($before->password);
		$Database->updateObject("users", $update);
		# ok
		$Result->show("success", _("User updated").".", false, false, false, false);
		# fetch updated state (omit password)
		$after = $Database->getObject("users", $edit_user->id); unset($after->password);
		# log
		$Log->write("users", $edit_user->id, $tenant->id, $user->id, $_POST['action'], true, "User ".$edit_user->email." updated", json_encode(["users"=>["0"=>$before]]), json_encode(["users"=>["0"=>$after]]), true);
	}
	elseif($_POST['action']==="delete") {
		$Database->deleteObject("users", $update['id']);
		# ok
		$Result->show("success", _("User deleted").".", false, false, false, false);
		# log
		$Log->write("users", $edit_user->id, $tenant->id, $user->id, $_POST['action'], true, "User ".$edit_user->email." deleted", json_encode(["users"=>["0"=>$edit_user]]), null, true);
	}
	else {
		throw new exception("Invalid action");
	}
} catch (Exception $e) {
	$Result->show("danger", $e->getMessage(), true, false, false, false);
}

<?php

# validate session
$User->validate_session();

$error   = null;
$success = false;

# handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	$User->validate_csrf_token();

	$current_pass  = $_POST['current_pass']  ?? '';
	$new_pass      = $_POST['new_pass']      ?? '';
	$confirm_pass  = $_POST['confirm_pass']  ?? '';

	# verify current password
	if (hash('sha512', $current_pass) !== $user->password) {
		$error = _('Current password is incorrect.');
	}
	# new and confirm must match
	elseif ($new_pass !== $confirm_pass) {
		$error = _('New passwords do not match.');
	}
	# new password cannot be the same as current
	elseif (hash('sha512', $new_pass) === $user->password) {
		$error = _('New password must be different from the current password.');
	}
	# password policy
	elseif (!$Common->validate_password($new_pass)) {
		$error = _('Password must be at least 10 characters and contain an uppercase letter, a lowercase letter, and a number.');
	}
	else {
		try {
			$Database->updateObject('users', [
				'id'         => $user->id,
				'password'   => hash('sha512', $new_pass),
				'changePass' => 0,
			]);
			$Log->write('users', $user->id, $user->t_id, $user->id, 'changepass', false, 'User changed their password');
			$success = true;
		} catch (Exception $e) {
			$error = _('Failed to update password: ') . $e->getMessage();
		}
	}
}

?>

<div class="page page-center" style="margin-top:40px">
  <div class="container container-tight py-4" style="max-width:480px">

    <div class="text-center mb-4">
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-warning" style="width:48px;height:48px">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z" />
        <path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
        <path d="M8 11v-4a4 4 0 1 1 8 0v4" />
      </svg>
      <h2 class="mt-2"><?php print _('Password change required'); ?></h2>
      <p class="text-secondary"><?php print _('You must set a new password before continuing.'); ?></p>
    </div>

    <div class="card card-md">
      <div class="card-body">

        <?php if ($success) { ?>

        <div class="alert alert-success mb-4" style="display:block">
          <strong><?php print _('Password changed successfully.'); ?></strong>
        </div>
        <div class="my-4">
          <a href="/<?php print htmlspecialchars($user->href, ENT_QUOTES); ?>/dashboard/" class="btn btn-primary w-100">
            <?php print _('Continue to application'); ?>
          </a>
        </div>

        <?php } else { ?>

        <?php if ($error !== null) { ?>
        <div class="alert alert-danger mb-3" style="display:block">
          <?php print htmlspecialchars($error, ENT_QUOTES); ?>
        </div>
        <?php } ?>

        <form action="" method="post">
          <input type="hidden" name="csrf_token" value="<?php print $User->create_csrf_token(); ?>">

          <div class="mb-3">
            <label class="form-label required"><?php print _('Current password'); ?></label>
            <input type="password" name="current_pass" class="form-control" autocomplete="current-password" required>
          </div>

          <div class="mb-3">
            <label class="form-label required"><?php print _('New password'); ?></label>
            <input type="password" name="new_pass" class="form-control" autocomplete="new-password" required>
            <small class="text-muted"><?php print _('Min 10 characters, must include uppercase, lowercase and a number.'); ?></small>
          </div>

          <div class="mb-4">
            <label class="form-label required"><?php print _('Confirm new password'); ?></label>
            <input type="password" name="confirm_pass" class="form-control" autocomplete="new-password" required>
          </div>

          <div class="my-4">
            <button type="submit" class="btn btn-primary w-100">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
              <?php print _('Change password'); ?>
            </button>
          </div>
        </form>

        <?php } ?>

      </div>
    </div>

  </div>
</div>

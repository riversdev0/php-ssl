<?php


// change theme ?
if (@$_params['app']=="theme") {
	if($_params['id1']=="light" || $_params['id1']=="dark") {
		$_SESSION['theme'] = $_params['id1'];

		if(isset($_SESSION['url'])) {
			header('Location: '.$_SESSION['url']);
			die();
		}
		else {
			header('Location: /');
			die();
		}
	}
	else {
		$Common->save_error("Invalid theme");
		require (dirname(__FILE__)."/../error/500.php");
		die();
	}
}

// force password change
elseif (@$_params['app']=="changepass") {
	include("changepass/index.php");
}

// profile
elseif (@$_params['app']=="profile" || !isset($_params['app'])) {
	include("profile/index.php");
}

// impersonate
elseif (@$_params['app']=="impersonate") {

	// stop impersonation
	if ($_params['id1'] === "stop") {
		if (isset($_SESSION['impersonate_original'])) {
			$original = $_SESSION['impersonate_original'];
			unset($_SESSION['impersonate_original']);
			$_SESSION['username'] = $original;
			$Log->write("users", 0, 0, 0, "impersonate_stop", false, "Impersonation ended, restored to ".$original);
		}
		header('Location: /');
		die();
	}

	// start impersonation — only original admins can impersonate
	else {
		// block if already impersonating (can't chain)
		if (isset($_SESSION['impersonate_original'])) {
			$Common->save_error("Already impersonating a user. Stop current impersonation first.");
			require (dirname(__FILE__)."/../error/500.php");
			die();
		}
		// must be admin
		if ($user->admin !== "1") {
			$Common->save_error("Administrative privileges required");
			require (dirname(__FILE__)."/../error/500.php");
			die();
		}
		// validate target user id
		$target_id = (int)$_params['id1'];
		if ($target_id < 1) {
			$Common->save_error("Invalid user ID");
			require (dirname(__FILE__)."/../error/500.php");
			die();
		}
		$target = $Database->getObject("users", $target_id);
		if ($target === null) {
			$Common->save_error("User not found");
			require (dirname(__FILE__)."/../error/500.php");
			die();
		}
		// cannot impersonate yourself
		if ($target->email === $_SESSION['username']) {
			$Common->save_error("Cannot impersonate yourself");
			require (dirname(__FILE__)."/../error/500.php");
			die();
		}
		// store original and switch
		$_SESSION['impersonate_original'] = $_SESSION['username'];
		$_SESSION['username'] = $target->email;
		$Log->write("users", $target->id, $target->t_id, $user->id, "impersonate_start", false, "Admin ".$_SESSION['impersonate_original']." impersonating ".$target->email);
		header('Location: /');
		die();
	}
}

else {
	$Common->save_error("Invalid user item");
	require (dirname(__FILE__)."/../error/404.php");
	die();
}
<?php


// change theme ?
if (@$_params['app']=="theme") {
	if($_params['id1']=="light" || $_params['id1']=="dark") {
		$_SESSION['theme'] = $_params['id1'];

		if(isset($_SESSION['url'])) {
			header('Location: '.$_SESSION['url']);
		}
		else {
			header('Location: /');
		}
	}
	else {
		$Common->save_error("Invalid theme");
		require (dirname(__FILE__)."/../error/500.php");
		die();
	}
}
else {
	$Common->save_error("Invalid user item");
	require (dirname(__FILE__)."/../error/404.php");
	die();
}
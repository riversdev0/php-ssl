<?php

/**
 *
 * Load appropriate content
 *
 */

# validate user session
$User->validate_session ();

# check
if(!array_key_exists($_params['route'], $url_items)) {
	$Common->save_error("Invalid route");
	include ("error/500.php");
	die();
}

# include route
if(file_exists(dirname(__FILE__)."/".$_params['route']."/index.php")) {
	include ($_params['route']."/index.php");
	// set url
	$_SESSION['url'] = "/".$_params['tenant']."/".$_params['route']."/";
	// app ?
	if(isset($_params['app']))
	$_SESSION['url'] .= $_params['app']."/";
	// app ?
	if(isset($_params['id1']))
	$_SESSION['url'] .= $_params['id1']."/";
}
else {
	$Common->save_error("Invalid route");
	include ("error/404.php");
	die();
}
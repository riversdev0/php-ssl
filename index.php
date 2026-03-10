<?php

# session
ob_start();

# check for config file
if (!file_exists(dirname(__FILE__) . "/config.php") && $_SERVER['REQUEST_URI']!="/install/")
{
	// html
	$title   = "Config file missing";
	$url     = isset($_SERVER['HTTPS']) ? "https://" : "http://" .$_SERVER['SERVER_NAME'];
	// error page
	$error_title = "Configuration file is missing.";
	$error_text = "To know how to connect to database config.php file needs to be present.<br>Please copy config.dist.php to config.php and change it accordingly.";

	$_params = ['tenant'=>'error', "route"=>"generic"];
}
# install
elseif ($_SERVER['REQUEST_URI']=="/install/")
{
	// html
	$title   = "php-ssl installation";
	$url     = isset($_SERVER['HTTPS']) ? "https://" : "http://" .$_SERVER['SERVER_NAME'];

	$_params = ['tenant'=>'install'];
}
else
{
	# autoload classes
	require ("functions/autoload.php");
}

# no cache headers
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache");                         //HTTP 1.0
header("Expires: Sat, 26 Jul 2016 05:00:00 GMT");   //Date in the past

// theme ?
if(!isset($_SESSION['theme'])) { $_SESSION['theme'] = "dark"; }
?>

<!doctype html>
<html lang="en" data-bs-theme-base="gray" data-theme="<?php print $_SESSION['theme']; ?>" data-bs-theme="<?php print $_SESSION['theme']; ?>" style="color-scheme: gray;">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">

	<meta name="Description" content="">
	<meta name="title" content="TEST">
	<meta name="robots" content="noindex, nofollow">
	<meta http-equiv="X-UA-Compatible" content="IE=9" >

	<meta name="viewport" content="width=device-width, initial-scale=0.8, maximum-scale=0.8, user-scalable=no">

	<!-- chrome frame support -->
	<meta http-equiv="X-UA-Compatible" content="chrome=1">

	<!-- title -->
	<title><?php print $title; ?></title>

	<!-- favicon -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico">
	<link rel="icon" type="image/svg+xml" href="/favicon.svg">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

	<!-- css -->
	<link href="/css/tabler.1.4.0.min.css" rel="stylesheet">
	<link href="/css/bootstrap-table.1.26.0.min.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="/css/style.css">

	<!-- js -->
    <script src="/js/jquery-3.6.0.min.js"></script>
    <script src="/js/popper.2.11.8.js"></script>
    <script src="/js/tippy.js"></script>
	<script src="/js/tabler.1.4.0.min.js"></script>
	<script src="/js/bootstrap-table.1.26.0.min.js"></script>
	<script src="/js/magic.js"></script>

</head>

<body>
	<?php
	// login and logout
	if($_params['tenant']=="login" || $_params['tenant']=="logout") {
		include ("route/login/index.php");
	}
	// generic errors
	elseif ($_params['tenant']=="error" && $_params['route']=="generic") {
		include ("route/error/generic.php");
	}
	// installation
	elseif ($_params['tenant']=="install") {
		include ("route/install/index.php");
	}
	// default
	else {
		// header
		include ("route/common/header.php");
	?>

	<!-- content -->
	<div class="container-fluid">
	  <div class="row">
	  	<aside class="navbar navbar-vertical navbar-expand-lg">
	    	<?php include ("route/common/left-menu.php"); ?>
		</aside>
	    <div class='page-wrapper'>
	    	<?php include ("route/content.php"); ?>
	    </main>
	  </div>
	</div>

	<!-- modal -->
	<div class="modal fade" id="modal1" tabindex="-1" aria-labelledby="modal1" aria-hidden="true">
	  <div class="modal-dialog">
	    <div class="modal-content">
	    </div>
	  </div>
	</div>

	<div class="modal fade" id="modal2" tabindex="-2" aria-labelledby="modal2" aria-hidden="true">
	  <div class="modal-dialog modal-xl">
	    <div class="modal-content">
	    </div>
	  </div>
	</div>

	<?php } ?>


	<!-- loader -->
	<div class="loading progress progress-sm">
        <div class="progress-bar progress-bar-indeterminate"></div>
    </div>

	<iframe class="download" style="display:none;"></iframe>
</body>
</html>
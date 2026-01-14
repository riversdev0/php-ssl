<?php

# session
ob_start();

# autoload classes
require ("functions/autoload.php");

# check for config
if (!$Common->config_exists()) { die(_("Config file missing")); }

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
	<base href="<?php print $url.BASE; ?>">

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

	<!-- css -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
	<link href="https://unpkg.com/bootstrap-table@1.19.1/dist/bootstrap-table.min.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="/css/style.css?v=<?php print md5(time()); ?>>">

	<!-- js -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

    <!-- js -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>

	<script type="text/javascript" src="/js/magic.js?v=<?php print md5(time()); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
	<script src="https://unpkg.com/bootstrap-table@1.19.1/dist/bootstrap-table.min.js"></script>

</head>

<body>
	<?php
	if($_params['tenant']=="login" || $_params['tenant']=="logout") {
		include ("route/login/index.php");
	}
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
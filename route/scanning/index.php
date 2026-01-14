<?php

# include route
if(file_exists(dirname(__FILE__)."/".$_params['app']."/index.php")) {
	include ($_params['app']."/index.php");
}
else {
	$Common->save_error("Invalid route");
	include (dirname(__FILE__)."/../error/404.php");
	die();
}
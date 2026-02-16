<?php

/**
 *
 * Script to verify userentered input and verify it against database
 *
 * If successfull write values to session and go to main page!
 *
 */


/* functions */
require( dirname(__FILE__) . '/../../functions/autoload.php');

# strip input tags
$_POST = $User->strip_input_tags ($_POST);

# authenticate
if( !empty($_POST['username']) && !empty($_POST['password']) )  {
	# all good, try to authentucate user
	$User->authenticate ($_POST['username'], $_POST['password'], $_POST['domain']);
}
# Username / pass not provided
else {
	$Result->show("danger", _('Please enter your username and password'), true);
}
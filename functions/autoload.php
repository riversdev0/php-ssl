<?php

/**
 *
 * Class, config etc autoloading
 *
 */

# config
include (dirname(__FILE__)."/../config.php");

# version
include (dirname(__FILE__)."/../version.php");

# include classes
include ("classes/class.PDO.php");
include ("classes/class.Result.php");
include ("classes/class.Validate.php");
include ("classes/class.Common.php");
include ("classes/class.URL.php");
include ("classes/class.Config.php");
include ("classes/class.SSL.php");
include ("classes/class.Cron.php");
include ("classes/class.Tenants.php");
include ("classes/class.Zones.php");
include ("classes/class.Certificates.php");
include ("classes/class.User.php");
include ("classes/class.Modal.php");
include ("classes/class.Mail.php");
include ("classes/class.Thread.php");
include ("classes/class.AXFR.php");
include ("classes/class.Agent.php");
include ("classes/class.Log.php");
include ("classes/class.ADsync.php");
include ("classes/class.Migration.php");

# required extensions
$required_extensions = ['curl', 'openssl', 'pcntl', 'posix', 'pdo', 'pdo_mysql', 'session', 'hash'];
$missing_extensions = php_feature_missing_all ($required_extensions);

if (sizeof($missing_extensions)>0)
{
	// html
	$title   = "php-ssl requirements error";
	$url     = isset($_SERVER['HTTPS']) ? "https://" : "http://" .$_SERVER['SERVER_NAME'];

	// errors
	$error_title = "Required php extensions are missing";
	$error_text  = "php-ssl requires some php extensions to be loaded to work properly, some are missing:";

	// table
	$error_text .= "<table class='table table-sm table-borderless table-extensions'>";
	foreach ($required_extensions as $req) {
		$error_text .= '<tr>';
		$error_text .= '<td>';
		$error_text .= !in_array($req, $missing_extensions) ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-check text-green"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-x text-red"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>';
		$error_text .= '</td>';
		$error_text .= '<td>'.$req.'</td>';
		$error_text .= '<td class="text-secondary">'.php_extension_purpose ($req).'</td>';
		$error_text .= '</tr>';

	}
	$error_text .= "</table>";

	// load content - error
	$_params = ['tenant'=>'error', "route"=>"generic"];
}
else
{
	# load classes
	try {
		$Result       = new Result ();
		$Common       = new Common ();
		$URL          = new URL ();
		$Database     = new Database_PDO ();
		$Modal 		  = new Modal ();
		$Config       = new Config ($Database);
		$User         = new User ($Database);
		$SSL          = new SSL ($Database);
		$Cron         = new Cron ($Database, $User->get_current_user());
		$Tenants      = new Tenants ($Database);
		$Zones        = new Zones ($Database, $User->get_current_user());
		$Certificates = new Certificates ($Database, $User->get_current_user());
		$Log 		  = new Log ($Database);
		$Migration    = new Migrations ($Database);

		# save user to local var
		$user = $User->get_current_user();

		# validate requested path
		$URL->validate_path ($user);

		# set params from GET and args
		$_params = $URL->get_params ();

		# menu
		include ("config.menu.php");
	}
	catch (Exception $e) {
		// SQL error ?
		if(strpos($e->getMessage(), "SQLSTATE")!==false) {
			$error_title = "Failed to connect to SQL database";
			$error_text = str_replace("Stack trace", "<br><br>Stack trace", $e->getMessage());
			$error_text = str_replace("\n", "<br>", $error_text);

			// do we need to install product ?
			if (@$installed!==true) {

				if (strpos($_SERVER['REQUEST_URI'], "/install")!==0) {
					header('Location: /install/');
					die();
				}

				// html
				$title   = "php-ssl installation";
				$url     = isset($_SERVER['HTTPS']) ? "https://" : "http://" .$_SERVER['SERVER_NAME'];
				// load content - error
				$_params = ['tenant'=>'install'];
			}
			else {
				// load content - error
				$_params = ['tenant'=>'error', "route"=>"generic"];
			}
		}
		// generic
		else {
			$error_title = "Error";
			$error_text = str_replace("\n", "<br>", $e->getMessage());

			// load content - error
			$_params = ['tenant'=>'error', "route"=>"generic"];
		}
	}
}
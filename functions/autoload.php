<?php

/**
 *
 * Class, config etc autoloading
 *
 */

# config
include (dirname(__FILE__)."/../config.php");

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

# load classes
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

# save user to local var
$user = $User->get_current_user();

# validate requested path
$URL->validate_path ($user);

# set params from GET and args
$_params = $URL->get_params ();

# menu
include ("config.menu.php");
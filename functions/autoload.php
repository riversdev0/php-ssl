<?php

/**
 *
 * Class, config etc autoloading
 *
 */

/**
 * Safe ucwords translation helper.
 * Wraps _(ucwords($s)) but returns '' for empty strings to avoid
 * gettext returning the PO file header when passed an empty msgid.
 */
function _u (string $s): string {
	return $s !== '' ? _(ucwords($s)) : '';
}

/**
 * Initialise PHP gettext locale for this request.
 *
 * Resolution order (highest priority first):
 *  1. $_SESSION['lang_id']   – in-session override (user clicked language switch)
 *  2. users.lang_id          – per-user preference
 *  3. tenants.lang_id        – tenant default
 *  4. English (no gettext)   – built-in fallback, _() returns msgid as-is
 *
 * @param object|null $user     Current user object from $User->get_current_user()
 * @param object      $Database Database_PDO instance
 */
function init_locale ($user, $Database)
{
	$lang_id = null;

	// 1. In-session override
	if (isset($_SESSION['lang_id']) && is_numeric($_SESSION['lang_id'])) {
		$lang_id = (int) $_SESSION['lang_id'];
	}
	// 2. User preference (query directly to avoid JOIN column ambiguity)
	elseif ($user !== null && !empty($user->id)) {
		try {
			$row = $Database->getObjectQuery ("SELECT lang_id FROM users WHERE id = ?", [$user->id]);
			if ($row && !empty($row->lang_id)) {
				$lang_id = (int) $row->lang_id;
			}
		} catch (Exception $e) { /* non-fatal */ }
	}
	// 3. Tenant default
	if ($lang_id === null && $user !== null && !empty($user->t_id)) {
		try {
			$row = $Database->getObjectQuery ("SELECT lang_id FROM tenants WHERE id = ?", [$user->t_id]);
			if ($row && !empty($row->lang_id)) {
				$lang_id = (int) $row->lang_id;
			}
		} catch (Exception $e) { /* non-fatal */ }
	}

	// 4. No preference, or English (id=1): explicitly reset the textdomain.
	// Without this, a PHP-FPM worker that previously served a non-English
	// user still has e.g. textdomain("messages_2") active, so English users
	// in the same worker process would receive translated (wrong) strings.
	// Setting a domain with no .mo file causes gettext to return the original
	// msgid strings, which are in English — the correct behaviour.
	if ($lang_id === null || $lang_id === 1) {
		bindtextdomain ("messages_1", dirname(__FILE__) . "/../functions/locale");
		textdomain ("messages_1");
		return;
	}

	// Fetch locale_code from DB
	try {
		$tr = $Database->getObjectQuery ("SELECT locale_code FROM translations WHERE id = ? AND enabled = 1", [$lang_id]);
	} catch (Exception $e) { return; }
	if (!$tr || empty($tr->locale_code)) { return; }

	$locale = $tr->locale_code;
	// Skip English locale — no .mo file needed
	if (strpos($locale, 'en_') === 0) { return; }

	// Initialise gettext
	putenv ("LANG={$locale}");
	putenv ("LANGUAGE={$locale}");
	// Try multiple locale variants for cross-platform compatibility
	setlocale (LC_ALL, [$locale, str_replace('.UTF-8', '.utf8', $locale), str_replace('.UTF-8', '', $locale)]);
	// Use a locale-specific domain name to bypass PHP-FPM's per-process
	// gettext .mo cache — the C library caches by domain name, so reusing
	// "messages" across locales in the same worker process serves stale
	// translations.  Each locale gets its own domain → its own cache slot.
	$domain = "messages_{$lang_id}";
	bindtextdomain ($domain, dirname(__FILE__) . "/../functions/locale");
	bind_textdomain_codeset ($domain, "UTF-8");
	textdomain ($domain);
}

/**
 * Returns all enabled translations, ordered by name.
 * Returns an empty array if the translations table does not exist yet.
 *
 * @param  object $Database
 * @return array
 */
function load_translations ($Database)
{
	try {
		$rows = $Database->getObjectsQuery ("SELECT * FROM translations WHERE enabled = 1 ORDER BY name ASC", []);
		return $rows ?: [];
	} catch (Exception $e) {
		return [];
	}
}

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
include ("classes/class.WebAuthn.php");
include ("classes/class.testssl.php");

# testssl submodule availability flag
$testssl_available = file_exists(dirname(__FILE__)."/../functions/testSSL/testssl.sh");

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

		# initialise gettext locale for this request
		init_locale ($user, $Database);

		# load available translations for language switcher
		$all_translations = load_translations ($Database);

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
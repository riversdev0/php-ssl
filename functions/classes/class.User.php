<?php

/**
 * User authentication
 */
class User extends Common
{

	/**
	 * Database holder
	 * @var false|Database_PDO
	 */
	private $Database = false;

	/**
	 * Result holder
	 * @var false|object
	 */
	private $Result = false;

	/**
	 * Authenticated holder
	 * @var bool
	 */
	private $authenticated = false;

	/**
	 * Username holder
	 * @var string
	 */
	private $username = "";

	/**
	 * User details
	 * @var null|object
	 */
	private $user = null;



	/**
	 * Constructor
	 * @method __construct
	 * @param  Database_PDO $Database
	 */
	public function __construct(Database_PDO $Database)
	{
		// Save database object
		$this->Database = $Database;
		// register session
		$this->register_session();
		// save current user
		$this->save_current_user();
		// result
		$this->Result = new Result();
	}

	/**
	 * Starts new sesison
	 * @method register_session
	 * @return void
	 */
	private function register_session()
	{
		//register session
		if (@$_SESSION === NULL) {
			session_start();
		}
	}

	/**
	 * Returns the session CSRF token, creating it if it does not yet exist.
	 * Embed the returned value in every form as a hidden input named csrf_token.
	 * @method create_csrf_token
	 * @return string
	 */
	public function create_csrf_token(): string
	{
		if (empty($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		return $_SESSION['csrf_token'];
	}

	/**
	 * Validates the CSRF token submitted in $_POST against the session token.
	 * Terminates the request with a danger alert on failure.
	 * Call at the top of every modal *-submit.php handler.
	 * @method validate_csrf_token
	 * @return void
	 */
	public function validate_csrf_token(): void
	{
		$submitted = $_POST['csrf_token'] ?? '';
		$expected  = $_SESSION['csrf_token'] ?? '';

		if (empty($expected) || !hash_equals($expected, $submitted)) {
			$this->Result->show("danger", _("Invalid or missing CSRF token."), true);
		}
	}

	/**
	 * Destroys session
	 * @method destroy_session
	 * @return void
	 */
	public function destroy_session()
	{
		session_destroy();
		$this->authenticated = false;
	}

	/**
	 * Returns all users / by tenant or all if admin requests
	 * @method get_all
	 * @return [type]
	 */
	public function get_all($index = "id")
	{
		// fetch
		try {
			if ($this->user->admin == "1") {
				$users = $this->Database->getObjectsQuery("select * from users order by email asc");
			}
			else {
				$users = $this->Database->getObjectsQuery("select * from users where t_id = ? order by email asc", [$this->user->t_id]);
			}
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die();
		}
		// reindex
		if (sizeof($users) > 0) {
			$users_new = [];
			foreach ($users as $t) {
				$users_new[$t->{$index}] = $t;
			}
			$users = $users_new;
		}
		// return
		return $users;
	}

	/**
	 * Saves current user to user object
	 * @method save_current_user
	 * @return void
	 */
	public function save_current_user()
	{
		if (isset($_SESSION['username'])) {
			// fetch
			try {
				$user = $this->Database->getObjectQuery("select *,t.name as t_name,u.name as name from users as u, tenants as t where u.t_id = t.id and email = ?", [$_SESSION['username']]);
			}
			catch (Exception $e) {
				$this->errors[] = $e->getMessage();
				$this->result_die();
			}
			// save
			if ($user != null) {
				$this->user = $user;
			}
		}
	}

	/**
	 * Return all active domains
	 * @method get_domains
	 * @return array
	 */
	public function get_active_domains()
	{
		return $this->Database->fetch_multiple_objects("domains", "active", "Yes");
	}

	/**
	 * Get domain details
	 * @method get_domain_id_details
	 * @param  int $domain_id
	 * @return object|bool
	 */
	public function get_domain_id_details(int $domain_id = 0)
	{
		return $this->Database->fetch_object("domains", "id", $domain_id);
	}

	/**
	 * Executes authentication
	 * @method authenticate
	 * @param  string $email
	 * @param  string $password
	 * @return void
	 */
	public function authenticate($email, $password, $domain_id)
	{
		// get domain
		$domain = $this->get_domain_id_details($domain_id);

		// false ?
		if ($domain === false) {
			print $this->Result->show("danger", _("Invalid domain") . ".");
		}
		// not active
		elseif ($domain->active != "Yes") {
			print $this->Result->show("danger", _("Domain is not active") . ".");
		}
		// ok
		else {
			// local
			if ($domain->type == "local") {
				$this->authenticate_local($email, $password, $domain);
			}
			// AD
			else {
				$this->authenticate_ad($email, $password, $domain);
			}
		}

	}

	/**
	 * Authenticate local user
	 * @method authenticate_local
	 * @param  string $email
	 * @param  string $password
	 * @param  object $domain
	 * @return void
	 */
	public function authenticate_local(string $email = "", string $password = "", object $domain)
	{
		// fetch user details
		$user = $this->fetch_user_details($email);
		// auth ok
		if ($user->password == hash('sha512', $password)) {
			// regenerate session ID to prevent session fixation
			session_regenerate_id(true);
			// save user
			$_SESSION['username'] = $user->email;
			// redirect ?
			if (isset($_SESSION['redirect_url'])) {
				$redirect = $_SESSION['redirect_url'];
				unset($_SESSION['redirect_url']);
			}
			else {
				$redirect = "/";
			}

			// print ok
			print $this->Result->show("success", _("Login successful"));
			print "<div id='login_redirect'>" . $redirect . "</div>";
			// log
			global $Log;
			$Log->write("user", $user->id, $user->t_id, $user->id, "login", false, "User has logged in");
		}
		// auth failed
		else {
			$this->show("danger", _("Invalid username or password"), true);
		}
	}


	/**
	 * Main function for authenticating users via AD
	 *
	 * @access public
	 * @param mixed $username
	 * @param mixed $password
	 * @return void
	 */
	public function authenticate_ad($username = "", $password = "", object $domain)
	{
		// connect to ad and init search
		$AD = new ADsync($this->Database, $domain);

		// if user has provided email address, try to get username !
		if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
			$dname = $AD->ad_user_info_by_email($username);
			$username = $dname === false ? $username : $dname;
		}

		// authenticate
		if ($AD->ad_user_authenticate($username, $password)) {
			// first check if user is already in db, if not fetch from AD and save
			$user = $this->fetch_user_details($username);
			// save username
			$this->username = $username;

			// create
			if (!$user) {
				// fetch
				$AD->init_search();
				$userinfo = $AD->ad_user_info($username);
				// create
				$AD->create_pw_user($username);
				// save user details
				$user = $AD->user_tmp;
			// update photo
			// --$this->update_user_photo($user['id'], $AD);
			// update login
			// --$this->update_login_time($user['id']);
			}
			else {
			// --$this->update_login_time($user->id);
			// update photo
			// --$this->update_user_photo($user->id, $AD);
			}
			# save locale
			//$this->save_user_locale ($user);
			# save to session
			$this->username = $username;
			// write log
			// -- $this->write_auth_log($username, "success", "Login successfull");
			# success print
			print $this->Result->show("success", _("Login successfull") . ".");
			// where to ?
			if (isset($_SESSION['redirect_url'])) {
				$redirect = $_SESSION['redirect_url'];
				unset($_SESSION['redirect_url']);
			}
			else {
				$redirect = strlen($user->home) > 0 ? $user->home : "/";
			}
			print "<div id='login_redirect'>" . $redirect . "</div>";
		}
		else {
			// print
			print $this->Result->show("danger", _("Invalid username or password") . ".");
		}
	}


	/**
	 * Fetches user details from email
	 * @method fetch_user_details
	 * @param  string $email
	 * @return object|bool
	 */
	private function fetch_user_details($email = "")
	{
		// execute
		try {
			$user = $this->Database->getObjectQuery("select * from users where `email` = ?", [$email]);
		}
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ") . $e->getMessage(), true);
		}
		// return
		return $user;
	}

	/**
	 * Returns current user object
	 * @method get_current_user
	 * @return object
	 */
	public function get_current_user()
	{
		return $this->user;
	}

	/**
	 * Checks is user has a valid session
	 * @method validate_session
	 * @param  bool $require_admin :: Refers to tenant logged in user belongs to must be admin.
	 * @return void
	 */
	public function validate_session($require_admin = false, $is_popup = false, $is_popup_result = false)
	{
		// not logged in
		if ($this->user === null) {
			if (!$is_popup) {
				// save redirect url
				if (strpos($_SERVER['REQUEST_URI'], "/route/") === false) {
					$_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
				}
				header("Location: /login/");
				die();
			}
			else {
				if (!$is_popup_result) {
					global $Modal;
					$Modal->modal_print("Session expired.", '<a class="btn btn-sm btn-info" href="/login/">' . _("Please log in.") . "</a>", "", false, "danger");
				}
				else {
					$this->Result->show("info", _("Session expired. Redirecting to login..."), true);
				}
				die();
			}
		}
		// not admin
		elseif ($require_admin && $this->user->admin !== "1") {
			if ($is_popup && !$is_popup_result) {
				global $Modal;
				$Modal->modal_print("Error", "<div class='alert alert-danger'>" . _("Administrative privileges required") . ".</div>", "", false, "danger");
				die();
			}
			else {
				print "<div class='header'><h3>" . _("Error") . "</h3></div>";
				print '<div class="container-fluid main">';
				$this->save_error("Administrative privileges required");
				$this->result_die();
				die();
			}
		}
	}

	/**
	 * Checks if user is authenticated
	 * @method is_authenticated
	 * @param  bool $die
	 * @return bool
	 */
	public function is_authenticated($die = false)
	{
		# if checked for subpages first check if $user is array
		if (!is_array($this->user)) {
			if (isset($_SESSION['username']) && strlen(@$_SESSION['username']) > 0) {
				# save username
				$this->username = $_SESSION['username'];
				$this->authenticated = true;
			}
			else {
				$this->authenticated = false;
			}
		}
		# return
		return $this->authenticated;
	}

	public function read_all_logs()
	{
		try {
			$this->Database->runQuery("update users set notif_id = (select id from logs order by id desc limit 1) where id = ?", [$this->user->id]);
			return true;
		}
		catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			return false;
		}
	}

	/**
	 * Valdates tenant
	 * @method validate_tenant
	 * @param  bool $modal
	 * @param  bool $modal2
	 * @return true|void
	 */
	public function validate_tenant($modal = false, $modal2 = false)
	{
		// admin
		if ($this->user->admin == "1") {
			return true;
		}
		// check
		else {
			global $_params;
			if ($this->user->href != $_params['tenant']) {
				if ($modal2 !== true)
					print "<div class='header'><h3>" . _("Error") . "</h3></div>";
				print '<div class="container-fluid main">';
				$this->save_error("Invalid tenant");
				$this->result_die($modal, $modal2);
				die();
			}
		}
	}

	/**
	 * Make sure user has permission to access resource
	 * @method validate_user_permissions
	 * @param  int $required
	 * @param  bool $popup
	 * @return void
	 */
	public function validate_user_permissions($required = 3, $popup = false)
	{
		// check
		if ($this->user->permission < $required) {
			if ($popup) {
				# init modal
				$Modal = new Modal();
				# set content
				$content[] = $this->Result->show("danger", _("Insuffiecient permissions to access this site. Please contact administrators") . ".", false, false, true);
				# print modal
				$Modal->modal_print("Insufficient permissions", implode("\n", $content), "", "");
				# die
				die();
			}
			else {
				$this->Result->show("danger", _("Insuffiecient permissions to access this site. Please contact administrators") . ".", true);
			}
		}
	}

	/**
	 * check if user has suffiecient permission level
	 * @method get_user_permissions
	 * @param  int $required
	 * @return bool
	 */
	public function get_user_permissions($required = 3)
	{
		return $this->user->permission < $required ? false : true;
	}

	/**
	 * Reformats user permission from int to string
	 * @method get_permissions_nice
	 * @param  int $permission
	 * @return string
	 */
	public function get_permissions_nice($permission = 0)
	{
		switch ($permission) {
			case 1:
				return "Read";
			case 2:
				return "Write";
			case 3:
				return "Admin";
			default:
				return "No access";
		}
	}

	/**
	 * Returns statistics for dashboard
	 * @method get_stats
	 * @return array
	 */
	public function get_stats()
	{
		// init
		$stats = ["tenants" => [], "zones" => [], "certificates" => []];

		// fetch
		if ($this->user->admin == "1") {
			// tenants
			$tenants = $this->Database->count_database_objects("tenants", "id", "%", true);
			// zones
			$zones = $this->Database->count_database_objects("zones", "t_id", "%", true);
			// hosts
			$hosts = $this->Database->count_database_objects("hosts", "id", "%", true);
			// certs
			$certs = $this->Database->count_database_objects("certificates", "t_id", "%", true);
			// users
			$users = $this->Database->count_database_objects("users", "t_id", "%", true);
		}
		else {
			// zones
			$zones = $this->Database->count_database_objects("zones", "t_id", $this->user->t_id, false);
			// hosts
			$hosts = $this->Database->getObjectQuery("select count(*) as cnt from hosts as h, zones as z where h.z_id = z.id and z.t_id = ?", [$this->user->t_id]);
			$hosts = $hosts->cnt;
			// certs
			$certs = $this->Database->count_database_objects("certificates", "t_id", $this->user->t_id, false);
			// users
			$users = $this->Database->count_database_objects("users", "t_id", $this->user->t_id, false);
		}

		// save
		$stats = [];
		if ($this->user->admin == "1")
			$stats['tenants'] = $tenants;
		$stats['users'] = $users;
		$stats['zones'] = $zones;
		$stats['hosts'] = $hosts;
		$stats['certificates'] = $certs;
		// result
		return $stats;
	}
	/**
	 * Result holder
	 * @return bool
	 */
	function getResult()
	{
		return $this->Result;
	}

	/**
	 * Result holder
	 * @param bool $Result Result holder
	 * @return User
	 */
	function setResult($Result): self
	{
		$this->Result = $Result;
		return $this;
	}
}
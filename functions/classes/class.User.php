<?php

/**
 * User authentication
 */
class User extends Common {

	/**
	 * Database holder
	 * @var bool
	 */
	private $Database = false;

	/**
	 * User details
	 * @var bool
	 */
	private $user = false;



	/**
	 * Constructor
	 * @method __construct
	 * @param  Database_PDO $Database
	 */
	public function __construct (Database_PDO $Database) {
		// Save database object
		$this->Database = $Database;
		// register session
		$this->register_session ();
		// save current user
		$this->save_current_user ();
		// result
		$this->Result = new Result();
	}

	/**
	 * Starts new sesison
	 * @method register_session
	 * @return void
	 */
	private function register_session () {
		//register session
		if(@$_SESSION===NULL) {
			session_start();
		}
	}

	/**
	 * Destroys session
	 * @method destroy_session
	 * @return void
	 */
	public function destroy_session () {
		session_destroy();
		$this->authenticated = false;
	}

	/**
	 * Returns all users / by tenant or all if admin requests
	 * @method get_all
	 * @return [type]
	 */
	public function get_all () {
		// fetch
		try {
			if($this->user->admin=="1") {
				$users = $this->Database->getObjectsQuery("select * from users order by email asc");
			}
			else {
				$users = $this->Database->getObjectsQuery("select * from users where t_id = ? order by email asc", [$this->user->t_id]);
			}
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->result_die ();
		}
		// reindex
		if(sizeof($users)>0) {
			$users_new = [];
			foreach ($users as $t) {
				$users_new[$t->id] = $t;
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
	public function save_current_user () {
		if(isset($_SESSION['username'])) {
			// fetch
			try {
				$user = $this->Database->getObjectQuery("select *,t.name as t_name,u.name as name from users as u, tenants as t where u.t_id = t.id and email = ?", [$_SESSION['username']]);
			} catch (Exception $e) {
				$this->errors[] = $e->getMessage();
				$this->result_die ();
			}
			// save
			if($user!=null) {
				$this->user = $user;
			}
		}
	}

	/**
	 * Executes authentication
	 * @method authenticate
	 * @param  string $email
	 * @param  string $password
	 * @return void
	 */
	public function authenticate ($email, $password) {
		// fetch user details
        $user = $this->fetch_user_details ($email);
        // auth ok
        if($user->password == hash('sha512', $password)) {
        	// save user
            $_SESSION['username'] = $user->email;
            // print ok
            $this->show("success", _("Login successful"));
            // log
            global $Log;
            $Log->write ("user", $user->id, $user->t_id, $user->id, "login", false, "User has logged in");
        }
        // auth failed
        else {
            $this->show("danger", _("Invalid username or password"), true);
        }
	}

	/**
	 * Fetches user details from email
	 * @method fetch_user_details
	 * @param  string $email
	 * @return [type]
	 */
	private function fetch_user_details ($email = "") {
		// execute
		try { $user = $this->Database->getObjectQuery("select * from users where `email` = ?", [$email]); }
		catch (Exception $e) {
			$this->Result->show("danger", _("Error: ").$e->getMessage(), true);
		}
		// return
		return $user;
	}

	/**
	 * Returns current user object
	 * @method get_current_user
	 * @return object
	 */
	public function get_current_user () {
		return $this->user;
	}

	/**
	 * Checks is user has a valid session
	 * @method validate_session
	 * @param  bool $require_admin :: Refers to tenant logged in user belongs to must be admin.
	 * @return [type]
	 */
	public function validate_session ($require_admin = false, $is_popup = false, $is_popup_result = false) {
		// not logged in
		if($this->user === false) {
			if (!$is_popup) {
				header("Location: /login/");
				die();
			}
			else {
				if (!$is_popup_result) {
					global $Modal;
					$Modal->modal_print ("Session expired.", '<a class="btn btn-sm btn-info" href="/login/">'._("Please log in.")."</a>", "", false, "danger");
				}
				else {
					$this->Result->show("info", _("Session expired. Redirecting to login..."), true);
				}
				die();
			}
		}
		// not admin
		elseif($require_admin && $this->user->admin !== "1") {
			if ($is_popup && !$is_popup_result) {
				global $Modal;
				$Modal->modal_print ("Error", "<div class='alert alert-danger'>"._("Administrative privileges required").".</div>", "", false, "danger");
				die();
			}
			else {
				print "<div class='header'><h3>"._("Error")."</h3></div>";
				print '<div class="container-fluid main">';
				$this->save_error("Administrative privileges required");
				$this->result_die ();
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
	public function is_authenticated ($die = false) {
		# if checked for subpages first check if $user is array
		if(!is_array($this->user)) {
			if( isset( $_SESSION['username'] ) && strlen( @$_SESSION['username'] )>0 ) {
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

	public function read_all_logs () {
		try {
			$this->Database->runQuery("update users set notif_id = (select id from logs order by id desc limit 1) where id = ?", [$this->user->id]);
			return truel;
		} catch (Exception $e) {
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
	public function validate_tenant ($modal = false, $modal2 = false) {
		// admin
		if ($this->user->admin=="1")	{ return true; }
		// check
		else {
			global $_params;
			if ($this->user->href!=$_params['tenant']) {
				if($modal2!==true)
				print "<div class='header'><h3>"._("Error")."</h3></div>";
				print '<div class="container-fluid main">';
				$this->save_error("Invalid tenant");
				$this->result_die ($modal, $modal2);
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
	public function validate_user_permissions ($required = 3, $popup = false) {
		// check
		if($this->user->permission < $required) {
			if ($popup) {
				# init modal
				$Modal = new Modal ();
				# set content
				$content[] = $this->Result->show("danger", _("Insuffiecient permissions to access this site. Please contact administrators").".", false, false, true);
			    # print modal
			    $Modal->modal_print ("Insufficient permissions", implode("\n", $content), "", "");
			    # die
			    die();
			}
			else {
				$this->Result->show("danger", _("Insuffiecient permissions to access this site. Please contact administrators").".", true);
			}
		}
	}

	/**
	 * check if user has suffiecient permission level
	 * @method get_user_permissions
	 * @param  int $required
	 * @return bool
	 */
	public function get_user_permissions ($required = 3) {
		return $this->user->permission < $required ? false : true;
	}

	/**
	 * Reformats user permission from int to string
	 * @method get_permissions_nice
	 * @param  int $permission
	 * @return string
	 */
	public function get_permissions_nice ($permission = 0) {
		switch ($permission) {
			case 1: return "Read"; break;
			case 2: return "Write"; break;
			case 3: return "Admin"; break;
			default:return "No access"; break;
		}
	}

	/**
	 * Returns statistics for dashboard
	 * @method get_stats
	 * @return array
	 */
	public function get_stats () {
		// init
		$stats = ["tenants" => [], "zones" => [], "certificates" => []];

		// fetch
		if ($this->user->admin=="1") {
			// tenants
			$tenants = $this->Database->count_database_objects ("tenants", "id", "%", true);
			// zones
			$zones   = $this->Database->count_database_objects ("zones", "t_id", "%", true);
			// hosts
			$hosts   = $this->Database->count_database_objects ("hosts", "id", "%", true);
			// certs
			$certs   = $this->Database->count_database_objects ("certificates", "t_id", "%", true);
			// users
			$users   = $this->Database->count_database_objects ("users", "t_id", "%", true);
		}
		else {
			// zones
			$zones   = $this->Database->count_database_objects ("zones", "t_id", $this->user->t_id, false);
			// hosts
			$hosts   = $this->Database->getObjectQuery ("select count(*) as cnt from hosts as h, zones as z where h.z_id = z.id and z.t_id = ?", [$this->user->t_id]);
			$hosts   = $hosts->cnt;
			// certs
			$certs   = $this->Database->count_database_objects ("certificates", "t_id", $this->user->t_id, false);
			// users
			$users   = $this->Database->count_database_objects ("users",  "t_id", $this->user->t_id, false);
		}

		// save
		$stats = [];
		if ($this->user->admin=="1")
		$stats['tenants']      = $tenants;
		$stats['users']		   = $users;
		$stats['zones']        = $zones;
		$stats['hosts']        = $hosts;
		$stats['certificates'] = $certs;
		// result
		return $stats;
	}
}
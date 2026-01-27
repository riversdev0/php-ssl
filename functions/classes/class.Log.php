<?php

/**
 *
 * Class to log changes
 *
 */
class Log extends Common {

	/**
	 * Database holder
	 * @var bool
	 */
	private $Database = false;

	/**
	 * Allowed Objects
	 * @var [type]
	 */
	private $objects = [
		"tenants",
		"users",
		"user",
		"zones",
		"hosts",
		"certificates",
		"scanning"
		];

	/**
	 * Allowed actions
	 * @var [type]
	 */
	private $actions = [
		"add",
		"edit",
		"delete",
		"refresh",
		"login",
		"truncate",
		"sync"
		];

	/**
	 * Constructor
	 * @method __construct
	 * @param  Database_PDO $Database
	 */
	public function __construct (Database_PDO $Database) {
		// Save database object
		$this->Database = $Database;
	}


	public function write ($object = "", $object_id = null, $object_t_id = null, $object_u_id = null, $action = null, bool $public = false, $text = "", $json_object_old = null, $json_object_new = null) {
		try {
			// validations
			$this->validate_object ($object);
			$this->validate_int ($object_id);
			$this->validate_int ($object_t_id);
			$this->validate_int ($object_u_id);
			$this->validate_action ($action);
			$this->validate_alphanumeric ($text);
			$this->validate_json ($json_object_old);
			$this->validate_json ($json_object_new);

			// insert object
			$insert = [
				"object"          => $object,
				"object_id"       => $object_id,
				"object_t_id"     => $object_t_id,
				"object_u_id"     => $object_u_id,
				"action"          => $action,
				"public"          => (int) $public,
				"text"            => $text
			];

			// object logging
			global $log_object;
			if (@$log_object===true) {
				$insert["json_object_old"] = $json_object_old;
				$insert["json_object_new"] = $json_object_new;
			}

			// insert
			$this->Database->insertObject("logs", $insert);
		}
		catch (Exception $e) {
			print "<div class='alert alert-warning'>Error: ".$e->getMessage()."</div>";
		}
	}

	/**
	 * Make sure we have an valid object
	 * @method validate_object
	 * @param  string $object
	 * @return void
	 */
	public function validate_object ($object = "") {
		if (!in_array($object, $this->objects)) {
			Throw new Exception ("Invalid log object");
		}
	}

	/**
	 * Validate integer
	 * @method validate_int
	 * @param  string $int
	 * @return void
	 */
	public function validate_int ($int = "") {
		if(!is_numeric($int) && $int!==null) {
			Throw new Exception ("Invalid integer");
		}
	}

	/**
	 * Validate user action
	 * @method validate_action
	 * @param  string $action
	 * @return void
	 */
	public function validate_action ($action = "") {
		if (!in_array($action, $this->actions)) {
			Throw new Exception ("Invalid action");
		}
	}

	/**
	 * Validate alphanumeric astring
	 * @method validate_alphanumeric
	 * @param  string $text
	 * @param  bool $allow_empty
	 * @return void
	 */
	public function validate_alphanumeric_log ($text = "") {
		// check
		if(!preg_match('/^[a-zA-Z\,čČšŠžŽ.\d\-_\s{}:\"]+$/i', $text) && strlen($text)>0) {
			Throw new Exception ("Invalid content");
		}
	}

	/**
	 * JSON validation
	 *
	 * @method validate_json
	 * @param  string $json_object
	 * @return void
	 */
	public function validate_json ($json_object = "") {
		if(json_decode($json_object)===NULL && $json_object!==NULL) {
			Throw new Exception ("Invalid object");
		}
	}


	public function get_logs ($user = null, $new_only = false, $public = false, $limit = 10) {
		try {
			// only new
			$var_arr = [];

			// set query
			$query = [];
			$query[] = "select * from logs where id > ?" ;
			// unread entries only
			if ($new_only)
			$var_arr[] = $user->notif_id;
			else
			$var_arr[] = 0;
			// public events only
			if ($public)
			$query[] = "and public = 1";
			// tenant ?
			if ($user->admin!="1") {
			$query[] = "and object_t_id =?";
			$var_arr[] = $user->t_id;
			}
			// limit
			$query[] = "order by id desc limit ".$limit;

			// fetch
			$logs = $this->Database->getObjectsQuery(implode(" ", $query), $var_arr);

		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
		}
		// return
		return $logs;
	}
}
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
		"agents",
		"ignored",
		"certificates",
		"scanning",
		"portgroups",
		"cron"
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
			// fix string(null) for json objects. Comes with json_encode (NULL)
			if($json_object_old=="null") $json_object_old = NULL;
			if($json_object_new=="null") $json_object_new = NULL;
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


	/**
	 * Get logs
	 * @method get_logs
	 * @param  [type] $user
	 * @param  bool $new_only
	 * @param  bool $public
	 * @param  int $limit
	 * @return [type]
	 */
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

	/**
	 * Get specific log
	 * @method get_log_by_id
	 * @param  int $id
	 * @param  string $user
	 * @return object
	 */
	public function get_log_by_id ($id = 0, $user = null) {
		try {
			// fetch
			if($user->admin=="1")
			$log = $this->Database->getObjectQuery("select * from logs where id = ?", [$id]);
			else
			$log = $this->Database->getObjectQuery("select * from logs where id = ? and object_t_id = ?", [$id, $user->t_id]);
			// return
			return $log;

		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
		}
	}

	/**
	 * Get number of new logs for user
	 * @method count_new_logs
	 * @param  object|null $user
	 * @return number
	 */
	public function count_new_logs ($user = null) {
		try {
			// fetch
			$logs = $this->Database->getObjectQuery("select count(*) as cnt from logs where public = 1 and id > ?", $user->notif_id);
			// return
			return is_null($logs->cnt) ? 0 : $logs->cnt;

		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
		}
	}

	/**
	 * Fetch all unique log tenants
	 * @method get_all_log_tenants
	 * @return array
	 */
	public function get_all_log_tenants () {
		try {
			// fetch
			return $this->Database->getObjectsQuery("select distinct(object_t_id) as tid from logs");
		} catch (Exception $e) {
			var_dump($e->getMessage());
			$this->errors[] = $e->getMessage();
			return [];
		}
	}

	/**
	 * Truncate logs for specific tenants
	 * @method truncate_logs
	 * @param  array $tenant_ids
	 * @return bool
	 */
	public function truncate_logs ($tenant_ids = []) {
		try {
			$placeholders = array_map(function() { return '?'; }, $tenant_ids);
			$params = array_values($tenant_ids);
			// delete
			$this->Database->runQuery("delete from logs where object_t_id in (".implode(",", $placeholders).")", $params);
			// update user id's
			$this->Database->runQuery("update users set notif_id = 0 where t_id in (".implode(",", $placeholders).")", $params);
			// return
			return true;

		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			return false;
		}
	}

	/**
	 * Format logging entry
	 * @method format_log_entry
	 * @param  object $l
	 * @param  string $user
	 * @return object
	 */
	public function format_log_entry ($l, $user = null) {
		// object
		$l->object  = $this->format_log_object ($l->object, $user->href, $l->id);
		// diff
		$l->diff    = $this->format_log_diff ($l->json_object_old, $l->json_object_new, $l->id);
		// action
		$l->action  = $this->format_log_action_badge ($l->action);
		// id - check if unread
		$is_unread = $user->notif_id !== null && $l->id > $user->notif_id;
		// id
		$l->id 	    = $this->format_log_id ($l->id, $user->href, $is_unread);
		// sate
		$l->date    = $this->format_log_date ($l->date);
		// content
		$l->text    = $this->format_log_content ($l->text);

		// return
		return $l;
	}

	/**
	 * Format log id link
	 * @method format_log_id
	 * @param  int $logid
	 * @param  string $href
	 * @param  bool $is_unread
	 * @return string
	 */
	public function format_log_id ($logid = 0, $href = "", $is_unread = false) {
		$red_dot = $is_unread ? "<span class='badge bg-red badge-blink'></span>" : "";
		return "<span class='badge'><a href='/".$href."/logs/".$logid."/'>".$logid."</a></span> ".$red_dot;
	}

	/**
	 * Format log object
	 * @method format_log_object
	 * @param  string $object
	 * @param  string $href
	 * @param  int $logid
	 * @return string
	 */
	public function format_log_object ($object = "", $href = "", $logid = 0) {
		return "<a class='btn btn-sm' href='/".$href."/logs/".$logid."/'>".ucwords($object)." :: ".$logid."</a>";
	}

	/**
	 * Format diff / sho
	 * @method format_log_diff
	 * @param  string $old
	 * @param  string $new
	 * @param  int $logid
	 * @return string
	 */
	public function format_log_diff ($old = "", $new = "", $logid = 0) {
		return strlen($old)>0||strlen($new)>0 ?
			"<a class='btn btn-sm' data-bs-toggle='modal' data-bs-target='#modal1' href='/route/modals/logs/show.php?id=".$logid."'><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='icon icon-tabler icons-tabler-outline icon-tabler-zoom-code'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><path d='M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0' /><path d='M21 21l-6 -6' /><path d='M8 8l-2 2l2 2' /><path d='M12 8l2 2l-2 2' /></svg> Show</a>"
			: "";
	}

	/**
	 * Format date
	 * @method format_log_date
	 * @param  string $date
	 * @return string
	 */
	public function format_log_date ($date = '') {
		return "<span class='text-secondary'>".$date."</span>";
	}

	/**
	 * Format badge
	 * @method format_log_action_badge
	 * @param  string $action
	 * @return string
	 */
	public function format_log_action_badge ($action = "") {
		switch($action) {
			case 'add'      : return "<span class='badge bg-teal-lt'>"._("Create")."</span>"; break;
			case 'delete'   : return "<span class='badge bg-red-lt'>"._("Delete")."</span>"; break;
			case 'login'    : return "<span class='badge bg-info-lt'>"._("Login")."</span>"; break;
			case 'edit'     : return "<span class='badge bg-info-lt'>"._("Edit")."</span>"; break;
			case 'truncate' : return "<span class='badge bg-orange-lt'>"._("Truncate")."</span>"; break;
			case 'refresh'  : return "<span class='badge bg-teal-lt'>"._("Refresh")."</span>"; break;
			case 'sync'     : return "<span class='badge bg-teal-lt'>"._("Zone sync")."</span>"; break;
			default         : return "<span class='badge'>".$action."</span>"; break;
		}
	}

	public function format_log_content ($text = "") {
		return "<div class='text-truncate' style='max-width:500px'>".$text."</div>";
	}


    /**
     * Returns prettified json to display
     *
     * @access public
     * @param mixed $json
     * @return void
     */
    public function pretty_json($json) {

        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '  ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element,
            // output a new line and indent the next line.
            } else if(($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }
}
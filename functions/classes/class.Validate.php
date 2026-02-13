<?php

/**
 *
 * Class for validations
 *
 * Never die, always returns bool value
 *
 */
class Validate extends Result {

	/**
	 * Validate url
	 * @method validate_url
	 * @param  string $hostname
	 * @return bool
	 */
	public function validate_hostname ($hostname = "") {
		if(filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)===false) {
			// save
			$this->errors[] = "invalid hostname";
			// false
			return false;
		}
		// all ok
		return true;
	}

	/**
	 * Validate requested URL
	 * @method validate_url
	 * @param  string $url
	 * @return [type]
	 */
	public function validate_url ($url = "") {
		if(filter_var($url, FILTER_VALIDATE_URL)===false) {
			// save
			$this->errors[] = "invalid URL";
			// false
			return false;
		}
		// all ok
		return true;
	}

	/**
	 * Validate user action
	 * @method validate_action
	 * @param  string $action
	 * @return bool
	 */
	public function validate_action ($action = "") {
		return in_array($action, ["add", "edit", "delete"]);
	}

	/**
	 * Validate ip
	 * @method validate_action
	 * @param  string $action
	 * @return bool
	 */
	public function validate_ip ($ip = "") {
		return filter_var($ip, FILTER_VALIDATE_IP);
	}

	/**
	 * Validate bin
	 * @method validate_bin
	 * @param  int $bin
	 * @return bool
	 */
	public function validate_bin ($bin = 0) {
		return !in_array($bin, [0,1,"0","1"]) ? false : true;
	}

	/**
	 * Validate alphanumeric astring
	 * @method validate_alphanumeric
	 * @param  string $text
	 * @param  bool $allow_empty
	 * @return bool
	 */
	public function validate_alphanumeric ($text = "", $allow_empty = false) {
		// empty
		if($allow_empty && strlen($text)==0) { return true; }
		elseif(!$allow_empty && strlen($text)==0) { return false; }
		// check
		return preg_match('/^[a-zA-Z\,čČšŠžŽ.\d\-_\s]+$/i', $text);
	}

	/**
	 * Validate integer
	 * @method validate_int
	 * @param  string $int
	 * @return bool
	 */
	public function validate_int ($int = "") {
		return is_numeric($int);
	}

	/**
	 * Validate regex provided
	 * @method validate_regex_string
	 * @param  string $pattern
	 * @return bool
	 */
	public function validate_regex_string ($pattern = "") {
		return preg_match($pattern, "lalala")===false ? false : true;
	}

	/**
	 * Validates input date
	 * @method validate_date
	 * @param  string $date
	 * @return bool
	 */
	public function validate_date ($date = "") {
		$date = DateTime::createFromFormat('Y-m-d H:i:s', $date);
		$date_errors = DateTime::getLastErrors();
		if ($date_errors['warning_count'] + $date_errors['error_count'] > 0) {
		    return false;
		}
		else {
			return true;
		}
	}


	/**
	 * Validates email address
	 * @method validate_mail
	 * @param  string $email
	 * @return bool
	 */
	public function validate_mail ($email = "") {
		return filter_var($email, FILTER_VALIDATE_EMAIL) ? true : false;
	}

	public function strip_input_tags ($input) {
		if(is_array($input)) {
			foreach($input as $k=>$v) {
    			$input[$k] = strip_tags($v);
            }
		}
		else {
			$input = strip_tags($input);
		}
		# stripped
		return $input;
	}

	/**
	 * Validate cron expression
	 * @method validate_cron
	 * @param  string $expression (minute hour day month weekday)
	 * @return bool
	 */
	public function validate_cron ($expression = "") {
		$parts = explode(' ', trim($expression));
		if (count($parts) !== 5) {
			$this->errors[] = "Cron expression must have 5 fields";
			return false;
		}

		$patterns = [
			0 => '/^(\*|\d+|\d+-\d+|(\d+,)+\d+|\*\/\d+)$/',   # minute: 0-59
			1 => '/^(\*|\d+|\d+-\d+|(\d+,)+\d+|\*\/\d+)$/',   # hour: 0-23
			2 => '/^(\*|\d+|\d+-\d+|(\d+,)+\d+|\*\/\d+)$/',   # day: 1-31
			3 => '/^(\*|\d+|\d+-\d+|(\d+,)+\d+|\*\/\d+)$/',   # month: 1-12
			4 => '/^(\*|\d+|\d+-\d+|(\d+,)+\d+|\*\/\d+)$/'    # weekday: 0-7
		];

		$names = ['minute', 'hour', 'day', 'month', 'weekday'];

		foreach ($parts as $i => $part) {
			if (!preg_match($patterns[$i], $part)) {
				$this->errors[] = "Invalid cron {$names[$i]}: {$part}";
				return false;
			}
		}
		return true;
	}
}
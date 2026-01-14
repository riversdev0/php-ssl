<?php

/**
 *
 * Class to handle URL request
 *
 */
class URL extends Common {

	/**
	 * Formulated URI parameters
	 * @var array
	 */
	private $uri_params = [];

	/**
	 * Constructor
	 * @method __construct
	 */
	public function __construct () {
		// parse url
		$uri = parse_url($_SERVER['REQUEST_URI']);
		// process query first to prevent overriding
		$this->process_query (@$uri['query']);
		// process path
		$this->process_path (@$uri['path']);
		// validate
		$this->validate_requested_uri ();
	}

	/**
	 * Process query args
	 *
	 * 	?val=123
	 *
	 * @method process_query
	 * @param  string $uri
	 * @return void
	 */
	private function process_query ($uri = "") {
		// split by &
		$uri_arr = explode("&", $uri);
		// loop
		foreach ($uri_arr as $line) {
			// split
			$tmp = explode("=", $line);
			// save
			$this->uri_params[$tmp[0]] = strip_tags($tmp[1]);
		}
	}

	/**
	 * Process requested path
	 *
	 * 	/v1/provider/token/
	 *
	 * @method process_path
	 * @param  string $path
	 * @return void
	 */
	private function process_path ($path = "") {
		// split by /
		$path_arr = array_values(array_filter(explode("/", $path)));
		// ajax-loaded -- ignore
		if(@$path_arr[0]=="route") { return false; }
		// default
		$this->uri_params['route'] = "dashboard";
		// save
		if (isset($path_arr[0]))	{ $this->uri_params['tenant'] = strip_tags($path_arr[0]); }
		if (isset($path_arr[1]))	{ $this->uri_params['route'] = strip_tags($path_arr[1]); }
		if (isset($path_arr[2]))	{ $this->uri_params['app']   = strip_tags($path_arr[2]); }
		if (isset($path_arr[3]))	{ $this->uri_params['id1']   = strip_tags($path_arr[3]); }
		if (isset($path_arr[4]))	{ $this->uri_params['id2']   = strip_tags($path_arr[4]); }
		if (isset($path_arr[5]))	{ $this->uri_params['id3']   = strip_tags($path_arr[5]); }
		// filter empty
		$this->uri_params = array_filter($this->uri_params);
	}

	/**
	 * Validate each requested string
	 * @method validate_requested_uri
	 * @return void
	 */
	private function validate_requested_uri () {
		foreach ($this->uri_params as $k=>$p) {
			if(preg_match('/[^a-z0-9\._\-]/i', $p)!=0 && $k!="certificate" && $k!="search") {
				$this->errors[] = "Invalid URI string";
				$this->result_die ();
			}
		}
	}

	/**
	 * Validate requested patch for user
	 * @method validate_path
	 * @param  object $user
	 * @return [bool]
	 */
	public function validate_path ($user) {
		// admin
		if($user->admin=="1")	{ return true; }
		// non-admin - tenant check
		if ($user->href==$this->uri_params['tenant'])	{ return true; }
		// fail
		return false;
	}

	/**
	 * Return all formulated params
	 * @method get_params
	 * @return array
	 */
	public function get_params () {
		return array_filter($this->uri_params);
	}
}
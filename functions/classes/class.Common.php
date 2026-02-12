<?php

/**
 *
 * Class for common functions
 *
 */
class Common extends Validate {

	/**
	 * Check if config exists
	 * @method config_exists
	 * @return bool
	 */
	public function config_exists () {
		return file_exists(dirname(__FILE__)."/../../config.php") ? true : false;
	}

	/**
	 * Creates permalink
	 * @method create_permalink
	 * @param  [type] $title
	 * @return [type]
	 */
	public function create_permalink ($title) {

		//replace slovenian characters
		$title = str_replace(array("č","Č"), "c", $title);
		$title = str_replace(array("š","Š"), "s", $title);
		$title = str_replace(array("ž","Ž"), "z", $title);

		$title = strip_tags($title);
		// Preserve escaped octets.
		$title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
		// Remove percent signs that are not part of an octet.
		$title = str_replace('%', '', $title);
		// Restore octets.
		$title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

		$title = strtolower($title);
		//$title = utf8_uri_encode($title, 200);

		$title = preg_replace('/&.+?;/', '', $title); // kill entities
		$title = str_replace('.', '-', $title);

		if ( 'save' == $context ) {
			// Convert nbsp, ndash and mdash to hyphens
			$title = str_replace( array( '%c2%a0', '%e2%80%93', '%e2%80%94' ), '-', $title );

			// Strip these characters entirely
			$title = str_replace( array(
				// iexcl and iquest
				'%c2%a1', '%c2%bf',
				// angle quotes
				'%c2%ab', '%c2%bb', '%e2%80%b9', '%e2%80%ba',
				// curly quotes
				'%e2%80%98', '%e2%80%99', '%e2%80%9c', '%e2%80%9d',
				'%e2%80%9a', '%e2%80%9b', '%e2%80%9e', '%e2%80%9f',
				// copy, reg, deg, hellip and trade
				'%c2%a9', '%c2%ae', '%c2%b0', '%e2%80%a6', '%e2%84%a2',
				// grave accent, acute accent, macron, caron
				'%cc%80', '%cc%81', '%cc%84', '%cc%8c',
			), '', $title );

			// Convert times to x
			$title = str_replace( '%c3%97', 'x', $title );
		}

		$title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
		$title = preg_replace('/\s+/', '-', $title);
		$title = preg_replace('|-+|', '-', $title);
		$title = trim($title, '-');

		return $title;
	}
}




/**
 *
 *
 * Global functions
 *
 *
 *
 */





/**
 * Check if required php features are missing
 * @param  mixed $required_extensions
 * @param  mixed $required_functions
 * @return string|bool
 */
function php_feature_missing($required_extensions = null, $required_functions = null) {

    if (is_array($required_extensions)) {
        foreach ($required_extensions as $ext) {
	        if (extension_loaded($ext))
	                continue;

	        return _('Required PHP extension not installed: ').$ext;
        }
    }

    if (is_array($required_functions)) {
        foreach ($required_functions as $function) {
            if (function_exists($function))
                continue;

            $ini_path = trim( php_ini_loaded_file() );
            $disabled_functions = ini_get('disable_functions');
            if (is_string($disabled_functions) && in_array($function, explode(';',$disabled_functions)))
                return _('Required function disabled')." : $ini_path, disable_functions=$function";

            return _('Required function not found: ').$function.'()';
        }
    }

    return false;
}


/**
 * Cronjob helpr function for scanning via forked process
 *
 * @method scan_host
 * @param  object $host
 * @param  datetime $execution_time
 * @param  int $tenant_id
 * @return void
 */
function scan_host ($host, $execution_time, $tenant_id) {
	# load classes
	$Database = new Database_PDO ();
	$SSL       = new SSL ($Database);

	// try to fetch cert
	$host_certificate = $SSL->fetch_website_certificate ($host, $execution_time, $tenant_id);

	// update cert if fopund
	if ($host_certificate!==false) {
		$cert_id = $SSL->update_db_certificate ($host_certificate, $host->t_id, $host->z_id, $execution_time);
		// get IP if not set from remote agent
		$ip = !isset($host_certificate['ip']) ? $SSL->resolve_ip($host->hostname) : $host_certificate['ip'];
		// if Id of certificate changed
		if($host->c_id!=$cert_id) {
			$SSL->assign_host_certificate ($host, $ip, $host->host_id, $cert_id, $host_certificate['port'], $execution_time, $host_certificate['tls_proto'], $host_certificate['serial']);
		}
	}
	// dummy return
	exit(1);
}
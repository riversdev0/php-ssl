<?php

/*	database connection details
 ******************************/
$db['host'] = "127.0.0.1";
$db['user'] = "phpssladmin";
$db['pass'] = "phpssladmin";
$db['name'] = "php-ssl";
$db['port'] = 3306;
$db['ssl']  = false;

/**
 * Flag that determines if php-ssl is cconsidered installed.
 *
 * If false it will load installtion in case SQL connection fails
 *
 * @var bool
 */
$installed = false;

/**
 * php debugging on/off
 *
 * true  = SHOW all php errors
 * false = HIDE all php errors
 ******************************/
$debugging = false;

/**
 *	manual set session name for auth
 *	increases security
 *	optional
 */
$phpsessname = "phpssl";

/**
 * Error reporting
 */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);

/**
 * Days before expiration to treat certificates as expire soon.
 *
 * This is used for cronjob reporting only, for GUI it is overridden by user settings
 *
 * @var int
 */
$expired_days = 20;

/**
 * Days after expiration to report certificates as expired
 *
 * This is used for cronjob reporting only, for GUI it is overridden by user settings
 *
 * @var int
 */
$expired_after_days = 7;

/**
 * Weather to log all object changes to database.
 *
 * If selected all changes to object will be written to database. DB might grow significantly.
 *
 * @var bool
 */
$log_object = true;

/**
 * Number of days to retain database backups.
 *
 * Backups older than this value will be removed by the backup cronjob.
 *
 * @var int
 */
$backup_retention_period = 30;

/**
 * Mail sending parameters - move to database later !
 *
 * @var StdClass
 */
$mail_settings          = new StdClass ();
$mail_settings->mtype   = "smtp";
$mail_settings->msecure = "tls";
$mail_settings->mauth   = "no";
$mail_settings->mserver = "127.0.0.1";
$mail_settings->mport   = 25;
$mail_settings->muser   = "";
$mail_settings->mpass   = "";

/**
 * Mail params - content
 *
 * @var StdClass
 */
$mail_sender_settings            = new StdClass ();
$mail_sender_settings->mail_from = "SSL Certificate check";
$mail_sender_settings->mail_addr = "noreply@mydomain.com";
$mail_sender_settings->email     = "php-ssl@ydomain.com";		// help - mail footer
$mail_sender_settings->www       = "https://mywebsite.com";
$mail_sender_settings->bcc       = "";							// always BCC
$mail_sender_settings->url       = "myurl";

/**
 * WebAuthn / Passkey settings
 *
 * Set these explicitly when running behind a reverse proxy that terminates TLS,
 * so PHP cannot reliably detect the public origin from $_SERVER.
 *
 * $webauthn_origin — full public origin: scheme + host (+ port if non-standard)
 *                    e.g. "https://php-ssl.example.com"
 * $webauthn_rpid   — relying-party ID: the hostname without scheme or port
 *                    e.g. "php-ssl.example.com"
 *
 * Leave both as empty string to auto-detect from the HTTP request (only works
 * when PHP can see the correct scheme via $_SERVER['HTTPS']).
 */
$webauthn_origin = "";
$webauthn_rpid   = "";

/**
 * Path to the nmap binary used for network host discovery scans.
 *
 * Install nmap via your package manager: apt install nmap / yum install nmap
 * The web server user must have execute permission on this binary.
 *
 * @var string
 */
$nmap_path = "/usr/bin/nmap";

/**
 * Private key encryption keys — one entry per tenant (keyed by tenant ID).
 *
 * Each value is used to derive a 256-bit AES-GCM key for encrypting stored
 * private keys. Use a long random string (32+ chars) per tenant and keep this
 * file out of version control.
 *
 * Example:
 *   $private_key_encryption_key[1] = 'change-me-to-a-long-random-secret';
 *   $private_key_encryption_key[2] = 'another-secret-for-tenant-2';
 *
 * @var array<int, string>
 */
$private_key_encryption_key = [];
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
 *	BASE definition if phpipam
 * 	is not in root directory (e.g. /phpipam/)
 *
 *  Also change
 *	RewriteBase / in .htaccess
 ******************************/
if(!defined('BASE'))
define('BASE', "/");

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
$mail_sender_settings->bcc       = "";							// always BCC
$mail_sender_settings->url       = "myurl";
# ************************************************************
# Sequel Ace SQL dump
# Version 20100
#
# https://sequel-ace.com/
# https://github.com/Sequel-Ace/Sequel-Ace
#
# Host: 127.0.0.1 (MySQL 5.5.5-10.5.9-MariaDB)
# Database: php-ssl-test
# Generation Time: 2026-05-14 06:00:28 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
SET NAMES utf8mb4;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE='NO_AUTO_VALUE_ON_ZERO', SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table agents
# ------------------------------------------------------------

DROP TABLE IF EXISTS `agents`;

CREATE TABLE `agents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `is_global` tinyint(1) NOT NULL DEFAULT 0,
  `atype` enum('local','remote') DEFAULT 'remote',
  `url` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `comment` text DEFAULT NULL,
  `last_check` datetime DEFAULT NULL,
  `last_success` datetime DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `protected` tinyint(4) NOT NULL DEFAULT 0,
  `version` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `atenants` (`t_id`),
  CONSTRAINT `atenants` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `agents` WRITE;
/*!40000 ALTER TABLE `agents` DISABLE KEYS */;

INSERT INTO `agents` (`t_id`, `is_global`, `atype`, `url`, `name`, `comment`, `last_check`, `last_success`, `last_error`, `protected`, `version`)
VALUES
  (1, 1, 'local', '127.0.0.1', 'Local', 'Scanning from local server', NULL, NULL, NULL, 1, NULL);

/*!40000 ALTER TABLE `agents` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table cas
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cas`;

CREATE TABLE `cas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `t_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `certificate` text DEFAULT NULL,
  `pkey_id` int(11) unsigned DEFAULT NULL,
  `parent_ca_id` int(11) DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  `ski` varchar(255) DEFAULT NULL,
  `source` enum('manual','auto') DEFAULT 'manual',
  `ignore_updates` tinyint(1) NOT NULL DEFAULT 0,
  `ignore_expiry` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `t_id` (`t_id`),
  KEY `pkey_id` (`pkey_id`),
  KEY `parent_ca_id` (`parent_ca_id`),
  KEY `cas_ski_tid` (`ski`,`t_id`),
  CONSTRAINT `cas_parent_fk` FOREIGN KEY (`parent_ca_id`) REFERENCES `cas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cas_pkey_fk` FOREIGN KEY (`pkey_id`) REFERENCES `pkey` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table certificates
# ------------------------------------------------------------

DROP TABLE IF EXISTS `certificates`;

CREATE TABLE `certificates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `z_id` int(11) unsigned NOT NULL,
  `t_id` int(11) unsigned NOT NULL,
  `serial` varchar(255) NOT NULL DEFAULT '',
  `certificate` text NOT NULL,
  `pkey_id` int(11) unsigned DEFAULT NULL,
  `chain` text DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_manual` tinyint(1) NOT NULL DEFAULT 0,
  `aki` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_zone_serial` (`z_id`,`serial`),
  KEY `serial` (`serial`),
  KEY `c_tenants` (`t_id`),
  KEY `c_pkey` (`pkey_id`),
  KEY `cert_aki` (`aki`),
  CONSTRAINT `c_pkey` FOREIGN KEY (`pkey_id`) REFERENCES `pkey` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `c_tenants` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `c_zones` FOREIGN KEY (`z_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table config
# ------------------------------------------------------------

DROP TABLE IF EXISTS `config`;

CREATE TABLE `config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(128) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `s_tenants` (`t_id`),
  CONSTRAINT `s_tenants` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cron
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cron`;

CREATE TABLE `cron` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `minute` varchar(6) NOT NULL DEFAULT '*',
  `hour` varchar(6) NOT NULL DEFAULT '*',
  `day` varchar(6) NOT NULL DEFAULT '*',
  `month` varchar(6) NOT NULL DEFAULT '*',
  `weekday` varchar(6) NOT NULL DEFAULT '*',
  `script` varchar(255) NOT NULL DEFAULT '',
  `last_executed` timestamp NULL DEFAULT NULL,
  `force` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Force execution on next cron run',
  PRIMARY KEY (`id`),
  KEY `cront_tenant` (`t_id`),
  CONSTRAINT `cront_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


LOCK TABLES `cron` WRITE;
/*!40000 ALTER TABLE `cron` DISABLE KEYS */;

INSERT INTO `cron` (`t_id`, `minute`, `hour`, `day`, `month`, `weekday`, `script`, `last_executed`, `force`)
VALUES
  (1, '*/30', '*', '*', '*', '*', 'update_certificates', NULL, 0),
  (1, '15', '2', '*', '*', '*', 'remove_orphaned', NULL, 0),
  (1, '0', '8', '*', '*', '*', 'expired_certificates', NULL, 0),
  (1, '0', '3', '*', '*', '*', 'axfr_transfer', NULL, 0),
  (1, '15', '1', '*', '*', '*', 'backup', NULL, 0);

/*!40000 ALTER TABLE `cron` ENABLE KEYS */;
UNLOCK TABLES;

# Dump of table csr_templates
# ------------------------------------------------------------

DROP TABLE IF EXISTS `csr_templates`;

CREATE TABLE `csr_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `key_algo` enum('RSA','EC') NOT NULL DEFAULT 'RSA',
  `key_size` int(5) NOT NULL DEFAULT 2048,
  `country` varchar(2) DEFAULT NULL,
  `state` varchar(128) DEFAULT NULL,
  `locality` varchar(128) DEFAULT NULL,
  `org` varchar(256) DEFAULT NULL,
  `ou` varchar(256) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `key_usage` text DEFAULT NULL,
  `ext_key_usage` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `csr_tpl_tenant` (`t_id`),
  CONSTRAINT `csr_tpl_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table csrs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `csrs`;

CREATE TABLE `csrs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `cn` varchar(255) NOT NULL DEFAULT '',
  `sans` text DEFAULT NULL,
  `key_algo` enum('RSA','EC') NOT NULL DEFAULT 'RSA',
  `key_size` int(5) NOT NULL DEFAULT 2048,
  `country` varchar(2) DEFAULT NULL,
  `state` varchar(128) DEFAULT NULL,
  `locality` varchar(128) DEFAULT NULL,
  `org` varchar(256) DEFAULT NULL,
  `ou` varchar(256) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('pending','submitted','signed') NOT NULL DEFAULT 'pending',
  `source` enum('internal','external') NOT NULL DEFAULT 'internal',
  `csr_pem` text DEFAULT NULL,
  `extensions` text DEFAULT NULL,
  `pkey_id` int(11) unsigned DEFAULT NULL,
  `cert_id` int(11) unsigned DEFAULT NULL,
  `renewed_by` int(11) unsigned DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `csrs_tenant` (`t_id`),
  KEY `csrs_pkey` (`pkey_id`),
  KEY `csrs_cert` (`cert_id`),
  KEY `csr_renewed_by` (`renewed_by`),
  CONSTRAINT `csr_renewed_by` FOREIGN KEY (`renewed_by`) REFERENCES `csrs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `csrs_cert` FOREIGN KEY (`cert_id`) REFERENCES `certificates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `csrs_pkey` FOREIGN KEY (`pkey_id`) REFERENCES `pkey` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `csrs_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table domains
# ------------------------------------------------------------

DROP TABLE IF EXISTS `domains`;

CREATE TABLE `domains` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL DEFAULT 1,
  `name` varchar(32) DEFAULT NULL,
  `type` enum('AD','local') DEFAULT 'local',
  `account_suffix` varchar(256) DEFAULT '@domain.local',
  `base_dn` varchar(256) DEFAULT 'CN=Users,CN=Company,DC=domain,DC=local',
  `domain_controllers` varchar(256) DEFAULT 'dc1.domain.local;dc2.domain.local',
  `use_ssl` tinyint(1) DEFAULT 0,
  `use_tls` tinyint(1) DEFAULT 0,
  `port` int(5) DEFAULT 389,
  `adminUsername` varchar(255) DEFAULT NULL,
  `adminPassword` varchar(256) DEFAULT NULL,
  `autocreateGroup` varchar(255) DEFAULT NULL,
  `active` set('Yes','No') DEFAULT 'No',
  PRIMARY KEY (`id`),
  KEY `t_id` (`t_id`),
  CONSTRAINT `fk_domains_t_id` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


LOCK TABLES `domains` WRITE;
/*!40000 ALTER TABLE `domains` DISABLE KEYS */;

INSERT INTO `domains` (`id`, `t_id`, `name`, `type`, `account_suffix`, `base_dn`, `domain_controllers`, `port`, `active`)
VALUES
  (1, 1, 'local', 'local', '@local', '', '', 0, 'Yes');

/*!40000 ALTER TABLE `domains` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table hosts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `hosts`;

CREATE TABLE `hosts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `z_id` int(11) unsigned NOT NULL,
  `c_id` int(11) unsigned DEFAULT NULL,
  `c_id_old` int(11) unsigned DEFAULT NULL,
  `pg_id` int(11) unsigned NOT NULL DEFAULT 1,
  `ignore` tinyint(1) NOT NULL DEFAULT 0,
  `mute` tinyint(1) NOT NULL DEFAULT 0,
  `hostname` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `ip` varchar(15) CHARACTER SET utf8 DEFAULT NULL,
  `port` int(5) DEFAULT NULL,
  `tls_version` varchar(32) COLLATE utf8_slovenian_ci DEFAULT NULL,
  `h_recipients` text CHARACTER SET utf8 DEFAULT NULL,
  `last_check` timestamp NULL DEFAULT NULL,
  `last_success` timestamp NULL DEFAULT NULL,
  `last_change` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `h_domain` (`z_id`),
  KEY `h_cert` (`c_id`),
  KEY `zone_hostname` (`z_id`,`hostname`,`pg_id`),
  CONSTRAINT `h_cert` FOREIGN KEY (`c_id`) REFERENCES `certificates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `h_domain` FOREIGN KEY (`z_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_slovenian_ci;

LOCK TABLES `hosts` WRITE;
/*!40000 ALTER TABLE `hosts` DISABLE KEYS */;

INSERT INTO `hosts` (`id`, `z_id`, `c_id`, `c_id_old`, `pg_id`, `ignore`, `mute`, `hostname`, `ip`, `port`, `tls_version`, `h_recipients`, `last_check`, `last_success`, `last_change`)
VALUES
  (1, 1, NULL, NULL, 1, 0, 0, 'google.com', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-14 08:10:49');

/*!40000 ALTER TABLE `hosts` ENABLE KEYS */;
UNLOCK TABLES;



# Dump of table logs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `logs`;

CREATE TABLE `logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object` varchar(32) NOT NULL,
  `object_id` int(11) unsigned DEFAULT NULL,
  `object_t_id` int(11) unsigned DEFAULT NULL,
  `object_u_id` int(11) unsigned DEFAULT NULL,
  `action` varchar(32) NOT NULL,
  `public` tinyint(1) NOT NULL DEFAULT 0,
  `text` text DEFAULT NULL,
  `json_object_old` text DEFAULT NULL,
  `json_object_new` text DEFAULT NULL,
  `is_revertable` tinyint(1) NOT NULL DEFAULT 0,
  `date` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `object_id` (`object_id`),
  KEY `object_t_id` (`object_t_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table migrations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table passkeys
# ------------------------------------------------------------

DROP TABLE IF EXISTS `passkeys`;

CREATE TABLE `passkeys` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `credential_id` varchar(512) NOT NULL,
  `public_key` text NOT NULL,
  `sign_count` int(11) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credential_id` (`credential_id`(255)),
  KEY `passkeys_user_id` (`user_id`),
  CONSTRAINT `passkeys_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table pkey
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pkey`;

CREATE TABLE `pkey` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `private_key_enc` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table ssl_port_groups
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ssl_port_groups`;

CREATE TABLE `ssl_port_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `name` varchar(64) DEFAULT NULL,
  `ports` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pg_tenant` (`t_id`),
  CONSTRAINT `pg_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `ssl_port_groups` WRITE;
/*!40000 ALTER TABLE `ssl_port_groups` DISABLE KEYS */;

INSERT INTO `ssl_port_groups` (`t_id`, `name`, `ports`)
VALUES
  (1, 'pg_ssl', '443,8443'),
  (1, 'pg_ssh', '22'),
  (1, 'pg_smtp', '25,456,587');

/*!40000 ALTER TABLE `ssl_port_groups` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table tenants
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tenants`;

CREATE TABLE `tenants` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `href` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `admin` tinyint(1) NOT NULL DEFAULT 0,
  `recipients` text DEFAULT NULL,
  `mail_style` enum('table','list') DEFAULT 'list',
  `remove_orphaned` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(2) DEFAULT 99,
  `log_retention` int(4) NOT NULL DEFAULT 30,
  `lang_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenants_lang_id` (`lang_id`),
  CONSTRAINT `tenants_lang_id` FOREIGN KEY (`lang_id`) REFERENCES `translations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;

INSERT INTO `tenants` (`id`, `name`, `href`, `description`, `active`, `admin`, `recipients`, `mail_style`, `remove_orphaned`, `order`, `log_retention`, `lang_id`)
VALUES
  (1,'Administrators','admins','Administrator users',1,1,'','list',1,1,30,NULL);

/*!40000 ALTER TABLE `tenants` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table translations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `translations`;

CREATE TABLE `translations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'English name, e.g. Slovenian',
  `native_name` varchar(100) NOT NULL COMMENT 'Native name, e.g. Slovenščina',
  `locale_code` varchar(30) NOT NULL COMMENT 'gettext locale, e.g. sl_SI.UTF-8',
  `lang_code` varchar(5) NOT NULL COMMENT 'ISO 639-1, e.g. sl',
  `flag` varchar(10) DEFAULT NULL COMMENT 'Emoji flag',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locale_code` (`locale_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Available UI translation languages';

LOCK TABLES `translations` WRITE;
/*!40000 ALTER TABLE `translations` DISABLE KEYS */;

INSERT INTO `translations` (`id`, `name`, `native_name`, `locale_code`, `lang_code`, `flag`, `enabled`)
VALUES
  (1,'English','English','en_US.UTF-8','en','????????',1),
  (2,'Slovenian','Slovenščina','sl_SI.UTF-8','sl','????????',1),
  (3,'German','Deutsch','de_DE.UTF-8','de','ðŸ‡©ðŸ‡ª',1);

/*!40000 ALTER TABLE `translations` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(128) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `permission` int(1) NOT NULL DEFAULT 0,
  `days` int(4) NOT NULL DEFAULT 20,
  `days_expired` int(4) NOT NULL DEFAULT 10,
  `notif_id` int(11) unsigned DEFAULT 0,
  `changePass` tinyint(1) NOT NULL DEFAULT 0,
  `disabled` tinyint(1) NOT NULL DEFAULT 0,
  `force_passkey` tinyint(1) NOT NULL DEFAULT 0,
  `lang_id` int(11) unsigned DEFAULT NULL,
  `create_date` datetime NOT NULL DEFAULT current_timestamp(),
  `last_active` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `u_tenants` (`t_id`),
  KEY `users_lang_id` (`lang_id`),
  CONSTRAINT `u_tenants` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_lang_id` FOREIGN KEY (`lang_id`) REFERENCES `translations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;

INSERT INTO `users` (`id`, `t_id`, `email`, `password`, `name`, `permission`, `days`, `days_expired`, `notif_id`, `changePass`, `disabled`, `force_passkey`, `lang_id`)
VALUES
  (1,1,'admin','c7ad44cbad762a5da0a452f9e854fdc1e0e7a52a38015f23f3eab1d80b931dd472634dfac71cd34ebc35d16ab7fb8a90c81f975113d6c7538dc69dd8de9077ec','Administrator',3,20,14,0,1,0,0,1);

/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table zones
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zones`;

CREATE TABLE `zones` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `aname` varchar(255) NOT NULL DEFAULT '',
  `ignore` tinyint(1) NOT NULL DEFAULT 0,
  `type` enum('local','axfr') NOT NULL DEFAULT 'local',
  `agent_id` int(11) unsigned DEFAULT 1,
  `description` text DEFAULT NULL,
  `dns` varchar(255) DEFAULT NULL,
  `tsig_name` varchar(255) DEFAULT NULL,
  `tsig` varchar(255) DEFAULT NULL,
  `record_types` varchar(255) DEFAULT NULL,
  `delete_records` tinyint(1) NOT NULL DEFAULT 0,
  `check_ip` tinyint(1) NOT NULL DEFAULT 0,
  `is_domain` tinyint(1) NOT NULL DEFAULT 1,
  `regex_include` text DEFAULT NULL,
  `regex_exclude` text DEFAULT NULL,
  `private_zone_uid` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `t_id` (`t_id`,`name`),
  KEY `name` (`name`),
  KEY `d_agent` (`agent_id`),
  KEY `fk_zone_private_uid` (`private_zone_uid`),
  CONSTRAINT `d_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `d_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_zone_private_uid` FOREIGN KEY (`private_zone_uid`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


LOCK TABLES `zones` WRITE;
/*!40000 ALTER TABLE `zones` DISABLE KEYS */;

INSERT INTO `zones` (`id`, `t_id`, `name`, `aname`, `ignore`, `type`, `agent_id`, `description`, `dns`, `tsig_name`, `tsig`, `record_types`, `delete_records`, `check_ip`, `is_domain`, `regex_include`, `regex_exclude`, `private_zone_uid`)
VALUES
  (1, 1, 'google.com', '', 0, 'local', 1, '', NULL, NULL, NULL, NULL, 0, 0, 1, NULL, NULL, NULL);

/*!40000 ALTER TABLE `zones` ENABLE KEYS */;
UNLOCK TABLES;





/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

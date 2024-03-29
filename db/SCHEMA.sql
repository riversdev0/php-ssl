# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.5.5-10.5.9-MariaDB)
# Database: php-ssl
# Generation Time: 2024-03-26 14:23:42 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table _ssl_ports
# ------------------------------------------------------------

DROP TABLE IF EXISTS `_ssl_ports`;

CREATE TABLE `_ssl_ports` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pg_id` int(11) unsigned NOT NULL,
  `port` int(5) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `port_group` (`pg_id`),
  CONSTRAINT `port_group` FOREIGN KEY (`pg_id`) REFERENCES `ssl_port_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



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
  PRIMARY KEY (`id`),
  KEY `atenants` (`t_id`),
  CONSTRAINT `atenants` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_zone_serial` (`z_id`,`serial`),
  KEY `serial` (`serial`),
  KEY `c_tenants` (`t_id`),
  KEY `c_pkey` (`pkey_id`),
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
  `minute` varchar(128) NOT NULL DEFAULT '',
  `hour` varchar(128) NOT NULL DEFAULT '',
  `day` varchar(128) NOT NULL DEFAULT '',
  `weekday` varchar(128) NOT NULL DEFAULT '',
  `script` varchar(255) NOT NULL DEFAULT '',
  `last_executed` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cront_tenant` (`t_id`),
  CONSTRAINT `cront_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



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
  `h_recipients` text CHARACTER SET utf8 DEFAULT NULL,
  `last_check` timestamp NULL DEFAULT NULL,
  `last_change` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `zone_hostname` (`z_id`,`hostname`),
  KEY `h_domain` (`z_id`),
  KEY `h_cert` (`c_id`),
  CONSTRAINT `h_cert` FOREIGN KEY (`c_id`) REFERENCES `certificates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `h_domain` FOREIGN KEY (`z_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_slovenian_ci;



# Dump of table pkey
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pkey`;

CREATE TABLE `pkey` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` text DEFAULT NULL,
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



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
  `days` int(4) NOT NULL DEFAULT 30,
  `days_expired` int(4) NOT NULL DEFAULT 30,
  PRIMARY KEY (`id`),
  KEY `u_tenants` (`t_id`),
  CONSTRAINT `u_tenants` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



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
  `regex_include` text DEFAULT NULL,
  `regex_exclude` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `t_id` (`t_id`,`name`),
  KEY `name` (`name`),
  KEY `d_agent` (`agent_id`),
  CONSTRAINT `d_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `d_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table ignored_issuers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ignored_issuers`;

CREATE TABLE `ignored_issuers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `ski` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sku_tid` (`t_id`,`ski`),
  CONSTRAINT `tid` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

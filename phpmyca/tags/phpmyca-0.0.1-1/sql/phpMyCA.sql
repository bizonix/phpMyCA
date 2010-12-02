drop database if exists phpMyCA;
create database phpMyCA;
use phpMyCA;

CREATE TABLE `cert_authorities` (
  `ca_id` int(10) unsigned NOT NULL auto_increment,
  `parent_id` int(10) unsigned NOT NULL,
  `ca_desc` varchar(128) default NULL,
  `create_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `start_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `expire_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `cert_request` text,
  `cert_public_key` text,
  `cert_private_key` text,
  `cert_cert` text NOT NULL,
  `cert_serial` decimal(64,0) unsigned NOT NULL default '0',
  `last_serial_issued` decimal(64,0) unsigned NOT NULL default '0',
  `fingerprint_md5` varchar(32) default NULL,
  `fingerprint_sha1` varchar(40) default NULL,
  `commonName` varchar(255) default NULL,
  `organizationName` varchar(255) NOT NULL default '',
  `organizationalUnitName` text,
  `countryName` char(2) default NULL,
  `localityName` varchar(64) default NULL,
  PRIMARY KEY  (`ca_id`),
  UNIQUE KEY `fingerprint` (`fingerprint_sha1`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `cert_clients` (
  `crt_id` int(10) unsigned NOT NULL auto_increment,
  `ca_id` int(10) unsigned NOT NULL,
  `crt_desc` varchar(128) default NULL,
  `create_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `start_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `expire_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `cert_request` text,
  `cert_public_key` text NOT NULL,
  `cert_private_key` text NOT NULL,
  `cert_cert` text NOT NULL,
  `cert_serial` decimal(64,0) unsigned NOT NULL default '0',
  `fingerprint_md5` varchar(32) default NULL,
  `fingerprint_sha1` varchar(40) default NULL,
  `commonName` varchar(255) NOT NULL default '',
  `organizationName` varchar(255) default NULL,
  `organizationalUnitName` text default NULL,
  `emailAddress` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`crt_id`),
  UNIQUE KEY `fingerprint` (`fingerprint_sha1`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `cert_servers` (
  `crt_id` int(10) unsigned NOT NULL auto_increment,
  `ca_id` int(10) unsigned NOT NULL,
  `crt_desc` varchar(128) default NULL,
  `create_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `start_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `expire_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `cert_request` text,
  `cert_public_key` text NOT NULL,
  `cert_private_key` text NOT NULL,
  `cert_cert` text NOT NULL,
  `cert_serial` decimal(64,0) unsigned NOT NULL default '0',
  `fingerprint_md5` varchar(32) default NULL,
  `fingerprint_sha1` varchar(40) default NULL,
  `commonName` varchar(255) NOT NULL default '',
  `organizationName` varchar(255) NOT NULL default '',
  `organizationalUnitName` text,
  `emailAddress` varchar(255) default NULL,
  `countryName` char(2) default NULL,
  `stateOrProvinceName` varchar(64) default NULL,
  `localityName` varchar(64) default NULL,
  PRIMARY KEY  (`crt_id`),
  UNIQUE KEY `fingerprint` (`fingerprint_sha1`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `csr_servers` (
  `csr_id` int(10) unsigned NOT NULL auto_increment,
  `csr_desc` varchar(128) default NULL,
  `create_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `cert_request` text,
  `cert_public_key` text NOT NULL,
  `cert_private_key` text NOT NULL,
  `commonName` varchar(255) NOT NULL default '',
  `organizationName` varchar(255) NOT NULL,
  `organizationalUnitName` text,
  `emailAddress` varchar(255) default NULL,
  `countryName` char(2) default NULL,
  `stateOrProvinceName` varchar(64) default NULL,
  `localityName` varchar(64) default NULL,
  PRIMARY KEY  (`csr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

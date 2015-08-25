DROP DATABASE scrapper;

CREATE DATABASE scrapper;

CREATE USER 'scrapper'@'localhost' IDENTIFIED BY 'scrapper_pass';
GRANT ALL PRIVILEGES ON `scrapper`.* TO 'scrapper'@'localhost';

USE scrapper;

CREATE TABLE `scrapper_company` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `site_company_id` varchar(20) DEFAULT NULL,
  `name` varchar(250) DEFAULT NULL,
  `type` varchar(250) DEFAULT NULL,
  `markets` varchar(250) DEFAULT NULL,
  `location` varchar(250) DEFAULT NULL,
  `domain` varchar(250) DEFAULT NULL,
  `social` varchar(500),
  `description` text,
  PRIMARY KEY (`id`),
  KEY `idx-site_company_id` (`site_company_id`),
  KEY `idx-name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `scrapper_founder` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(250) DEFAULT NULL,
  `social` varchar(500),
  PRIMARY KEY (`id`),
  KEY `idx-company_id` (`company_id`),
  KEY `idx-name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `scrapper_employee` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(250) DEFAULT NULL,
  `social` varchar(500),
  PRIMARY KEY (`id`),
  KEY `idx-company_id` (`company_id`),
  KEY `idx-name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


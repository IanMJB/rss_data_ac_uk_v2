<?php

$config	= parse_ini_file(__DIR__.'/../secrets.ini', true);

$db	= create_database($config);
create_tables($db);

function create_database($config)
{
	$db_version	= $config['db'];
	$config		= $config[$db_version];

	$db_host	= $config['db_host'];
	$db_name	= $config['db_name'];
	$db_charset	= $config['db_charset'];
	$db_user	= $config['db_user'];
	$db_password	= $config['db_password'];

	$db		= new PDO("mysql:host=$db_host;charset=$db_charset", $db_user, $db_password, array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

	$db->exec('CREATE DATABASE `'.$db_name.'`;');

	return $db;
}

function create_tables($db)
{
	$db->exec('CREATE TABLE `institutions` (
			`inst_id` int(11) NOT NULL AUTO_INCREMENT,
			`inst_name` varchar(255) DEFAULT NULL,
			`inst_groups` varchar(255) DEFAULT NULL,
			`inst_pdomain` varchar(255) DEFAULT NULL,
			PRIMARY KEY (`inst_id`),
			UNIQUE KEY `inst_pdomain` (`inst_pdomain`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;');

	$db->exec('CREATE TABLE `feeds` (
			`feed_id` int(11) NOT NULL AUTO_INCREMENT,
			`feed_title` varchar(255) DEFAULT NULL,
			`feed_desc` varchar(2550 DEFAULT NULL,
			`feed_url` varchar(255) NOT NULL,
			`title_url_hash` char(32) NOT NULL,
			`inst_id` int(11) NOT NULL,
			PRIMARY KEY (`feed_id`),
			UNIQUE KEY `feed_url` (`feed_url`),
			KEY `inst_id` (`inst_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;');

	$db->exec('CREATE TABLE `posts` (
			`post_id` int(11) NOT NULL AUTO_INCREMENT,
			`post_title` mediumtext DEFAULT NULL,
			`post_desc` mediumtext DEFAULT NULL,
			`post_date` datetime NOT NULL,
			`post_url` varchar(255) DEFAULT NULL,
			`title_url_hash` char(32) NOT NULL,
			`feed_id` int(11) NOT NULL,
			PRIMARY KEY (`post_id`),
			UNIQUE KEY `post_url` (`post_url`),
			KEY `feed_id` (`feed_id`),
			FULLTEXT KEY `search_index` (`post_title`, `post_desc`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
}

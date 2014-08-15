#!/usr/bin/bash

<?php

include __DIR__.'/../lib/lastRSS.php';
include __DIR__.'/../lib/db_utilities.php';

function __autoload($class_name)
{
	include $class_name.'.php';
}


$config	= parse_ini_file(__DIR__.'/../secrets.ini', true);
$db	= db_utilities::connect($config);

$no_recent_posts	= 20;
$terms 			= 'Christians in Syria and Iraq';
$groups			= array(
				'NCUK',
				'1994_Group'
	     		       );

$test	= db_utilities::get_posts_last_x_from_groups($db, $no_recent_posts, $groups);
#$test	= db_utilities::get_posts_with_terms_from_groups($db, $terms, $groups);

print_r($test);



/*echo 'INIT START', "\n\n";

$last_rss		= new lastRSS;
$last_rss->cache_dir	= '../tmp';
$last_rss->cache_time	= 1200;
echo 'lastRSS instantiated.', "\n";

$config	= parse_ini_file(__DIR__.'/../secrets.ini', true);
$db	= db_utilities::connect($config);
echo 'Database connected.', "\n";

$raw_data	= json_decode(file_get_contents('http://observatory.data.ac.uk/data/observations/latest.json'), true);
echo 'JSON data acquired.', "\n";

$processed_data = data_manipulator::process_raw_json_array($raw_data);
echo 'JSON data sanitised.', "\n";

echo 'INIT END', "\n\n";



echo 'DATABASE POPULATION START', "\n\n";

echo 'RSS CRAWL START', "\n\n";
data_inserter::populate_institutions($db, $processed_data);
echo "\n", 'RSS CRAWL END', "\n\n";

echo 'INSTITUTION META-INTEGRATION START', "\n\n";
group_integration::integrate_data($db, group_integration::get_university_groups());
echo "\n", 'INSTITUTION META-INTEGRATION END', "\n\n";

echo 'DATABASE POPULATION END';*/

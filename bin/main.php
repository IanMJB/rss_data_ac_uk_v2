#!/usr/bin/bash

<?php

include __DIR__.'/../lib/lastRSS.php';
include __DIR__.'/../lib/db_utilities.php';

function __autoload($class_name)
{
	include $class_name.'.php';
}


$config	= parse_ini_file(__dir__.'/../secrets.ini', true);
$db	= db_utilities::connect($config);


echo 'INIT START', "\n\n";

$last_rss		= new lastrss;
$last_rss->cache_dir	= '../tmp';
$last_rss->cache_time	= 1200;
echo 'LASTRSS INSTANTIATED', "\n";

$config	= parse_ini_file(__dir__.'/../secrets.ini', true);
$db	= db_utilities::connect($config);
echo 'DATABASE CONNECTED', "\n";

$raw_data	= json_decode(file_get_contents('http://observatory.data.ac.uk/data/observations/latest.json'), true);
echo 'JSON DATA ACQUIRED', "\n";

$processed_data = data_manipulator::process_raw_json_array($raw_data);
echo 'JSON DATA SANITISED', "\n";

echo 'INIT END', "\n\n";



echo 'DATABASE POPULATION START', "\n\n";

echo 'RSS CRAWL START', "\n\n";
data_inserter::populate_institutions($db, $processed_data);
echo "\n", 'RSS CRAWL END', "\n\n";

echo 'INSTITUTION META-INTEGRATION START', "\n\n";
group_integration::integrate_data($db, group_integration::get_university_groups());
echo "\n", 'INSTITUTION META-INTEGRATION END', "\n\n";

echo 'DATABASE POPULATION END';

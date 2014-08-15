#!/usr/bin/php

<?php

include __DIR__.'/../lib/db_utilities.php';

$config	= parse_ini_file(__DIR__.'/../secrets.ini', true);
$db	= db_utilities::connect($config);

$table_names = array(
			'institutions',
			'feeds',
			'posts'
		    );

echo date('Y-m-d H:i:s'), "\n";
get_counts_for_tables($db, $table_names);

function get_counts_for_tables($db, $table_names)
{
	foreach($table_names as $table_name)
	{
		$select_count_stmt = db_utilities::create_select_count($db, $table_name);
		$select_count_stmt->execute();
		$result = $select_count_stmt->fetch();
		echo $table_name, ',', $result['COUNT(*)'], "\n";
	}
}

<?php

class db_utilities{

	/********************************************************
	* Connects to the database details in $config (should	*
	* be a parsed .ini file. See secrets.ini.template as an *
	* example of the format.				*
	* Returns handle to the connected database.		*
	********************************************************/
	function connect($config)
	{
		#Detects whether live or test (or others), and uses the relevant database.
		$db_version	= $config['db'];
		$config		= $config[$db_version];

		$db_host	= $config['db_host'];
		$db_name	= $config['db_name'];
		$db_charset	= $config['db_charset'];
		$db_user	= $config['db_user'];
		$db_password	= $config['db_password'];

		$db		= new PDO("mysql:host=$db_host;dbname=$db_name;charset=$db_charset", $db_user, $db_password, array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
		return $db;
	}
	
	/********************************************************
	* Simple insert-ignore - takes a database, table name	*
	* and an array of column-name/values and creates an	*
	* insert-ignore statement.				*
	********************************************************/
	function create_insert_ignore($db, $table, $insert_array)
	{
		$column_names	= array();
		$question_marks	= array();
		foreach(array_keys($insert_array) as $column_name)
		{
			$column_names[]		= "`$column_name`";
			$question_marks[]	= '?';
		}

		$sql_insert	= "INSERT IGNORE INTO $table(".implode(',', $column_names).') VALUES ('.implode(',', $question_marks).')';

		return $db->prepare($sql_insert);
	}

	/********************************************************
	* Simple update - takes a database, table name, data to *
	* insert and a where column.				*
	* Updates the entered fields where the where column	*
	* matches.						*
	********************************************************/
	function create_update($db, $table, $update_array, $where_column)
	{
		$column_names	= array();
		foreach(array_keys($update_array) as $column_name)
		{
			$column_names[]	= "`$column_name` = ?";
		}

		$sql_update	= "UPDATE $table SET ".implode(', ', $column_names)." WHERE $where_column = ?";

		return $db->prepare($sql_update);
	}

	/********************************************************
	* Simple select - takes a database, table name, select	*
	* column and where column.				*
	* Returns the value from a single column, defined by	*
	* the select column.					*
	********************************************************/
	function create_select_single($db, $table, $select_column, $where_column)
	{
		$sql_select	= "SELECT $select_column FROM $table WHERE $where_column = ?";

		return $db->prepare($sql_select);
	}

	/********************************************************
	* Simple count - takes a database and table name,	*
	* returns the count.					*
	********************************************************/
	function create_select_count($db, $table)
	{
		$sql_select	= "SELECT COUNT(*) FROM $table";

		return $db->prepare($sql_select);
	}

	/********************************************************
	* Formats the date from that provided by the RSS feeds	*
	* to that expected by the database.			*
	********************************************************/
	function rss_date_to_mysql_date($rss_date)
	{
		$time_from_epoch	= strtotime($rss_date);
		$date			= date('Y-m-d H:i:s', $time_from_epoch);
		return $date;
	}

	/********************************************************
	* Used by the front-end - will select the most recent X	*
	* posts when given X.					*
	********************************************************/
	function get_posts_last_x($db, $no_recent_posts)
	{
		$sql_select	= db_utilities::posts_last_x_stmt();

		$sql_select_stmt	= $db->prepare($sql_select);
		$input			= array($no_recent_posts);
		$sql_select_stmt->execute(array_values($input));

		return $sql_select_stmt->fetchAll();
	}

	/********************************************************
	* Used by the front-end - intersects recent posts with	*
	* those from selected university groups, returning the 	*
	* most recent X.					*
	* str_replace/adding 'LIMIT ?' to the end used so as to	*
	* limit after the intersect, not before.		*
	********************************************************/
	function get_posts_last_x_in_groups($db, $no_recent_posts, $groups)
	{
		$x_select	= db_utilities::posts_last_x_stmt();
		$x_select	= str_replace('LIMIT ?', '', $x_select);
		$group_select	= db_utilities::posts_in_groups_stmt($groups);

		$sql_select	= 'SELECT post_title, post_desc, post_date, post_url
					FROM
					(
						('
							.$x_select.
						')
						UNION ALL
						('
							.$group_select.
						')
					)
					AS intersect_table
					GROUP BY post_title, post_desc, post_date, post_url
					HAVING COUNT(*) >= 2
					ORDER BY post_date
					DESC
					LIMIT ?';

		$sql_select_stmt	= $db->prepare($sql_select);
		$input			= array_merge($groups, array($no_recent_posts));
		$sql_select_stmt->execute(array_values($input));

		return $sql_select_stmt->fetchAll();
	}

	/********************************************************
	* Used by the front-end - will select posts matching to	*
	* the term(s) supplied. Uses IN NATURAL LANGUAGE MODE,	*
	* then orders by its relevance factor to attempt to	*
	* provide the most accurate results.			*
	********************************************************/
	function get_posts_with_terms($db, $terms)
	{
		$sql_select	= db_utilities::posts_with_terms_stmt();

		$sql_select_stmt	= $db->prepare($sql_select);
		$input			= array($terms, $terms);
		$sql_select_stmt->execute(array_values($input));

		return $sql_select_stmt->fetchAll();
	}

	/********************************************************
	* Used by the front end- selects posts matching the 	*
	* terms supplied, intersected with the groups supplied.	*
	********************************************************/
	function get_posts_with_terms_in_groups($db, $terms, $groups)
	{
		$terms_select	= db_utilities::posts_with_terms_stmt();
		$group_select	= db_utilities::posts_in_groups_stmt($groups);

		$sql_select	= 'SELECT post_title, post_desc, post_date, post_url
					FROM
					(
						('
							.$terms_select.
						')
						UNION ALL
						('
							.$group_select.
						')
					)
					AS intersect_table
					GROUP BY post_title, post_desc, post_date, post_url
					HAVING COUNT(*) >= 2
					ORDER BY post_date
					DESC';

		$sql_select_stmt	= $db->prepare($sql_select);
		$input			= array_merge(array($terms, $terms), $groups);
		$sql_select_stmt->execute(array_values($input));

		return $sql_select_stmt->fetchAll();
	}

	/********************************************************
	* Returns the statement used to query the database for	*
	* the last X posts - to avoid code duplication.		*
	********************************************************/
	function posts_last_x_stmt()
	{
		$sql_select	= 'SELECT post_title, post_desc, post_date, post_url
					FROM posts
					WHERE post_date < NOW()
					ORDER BY
					post_date
					DESC
					LIMIT ?';

		return $sql_select;
	}

	/********************************************************
	* Returns the statement used to query the database for	*
	* posts matching supplied terms - to avoid code		*
	* duplication.						*
	********************************************************/
	function posts_with_terms_stmt()
	{
		$sql_select	= 'SELECT post_title, post_desc, post_date, post_url
					FROM posts
					WHERE
					(
						MATCH(post_title, post_desc)
						AGAINST(? IN NATURAL LANGUAGE MODE)
					)
					AND post_date < NOW()
					ORDER BY
					MATCH(post_title, post_desc)
					AGAINST(? IN NATURAL LANGUAGE MODE)
					DESC';

		return $sql_select;
	}

	/********************************************************
	* Returns the statement used to query to database for	*
	* posts from the groups provided - to avoid code	*
	* duplication.						*
	********************************************************/
	function posts_in_groups_stmt($groups)
	{
		$question_marks	= array();
		foreach($groups as $group)
		{
			$question_marks[]	= '?';
		}

		$sql_select	= 'SELECT post_title, post_desc, post_date, post_url
					FROM posts
					INNER JOIN feeds
					ON posts.feed_id = feeds.feed_id
					INNER JOIN institutions
					ON feeds.inst_id = institutions.inst_id
					WHERE institutions.inst_groups in ('.implode(',', $question_marks).')';

		return $sql_select;
	}
}

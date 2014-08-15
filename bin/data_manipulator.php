<?php

class data_manipulator
{

	/********************************************************
	* Creates an array of:					*
	* inst_pdomain => ('rss_feeds')				*
	* Institution pdomains without associated RSS feeds are *
	* not added to the institutions table.			*
	********************************************************/
	function process_raw_json_array($raw_data)
	{
		$relevant_data	= array();
		foreach($raw_data as $pdomain => $details)
		{
			#Only interested in institutions with a listed RSS feed.
			if(!empty($details['site_profile']['rss']))
			{
				$rss_feeds	= array();
				#Auto-generates a full RSS URL for short RSS names using the site URL,
				#e.g. '/rss' becomes '"pdomain"/rss'.
				foreach($details['site_profile']['rss'] as $prelim_rss)
				{
					if(substr($prelim_rss, 0, 7) != 'http://')
					{
						$rss_feeds[]	= $details['site_url'].substr($prelim_rss, 2);
					}
					else
					{
						$rss_feeds[]	= $prelim_rss;
					}
				}
				$relevant_data['http://www.'.$pdomain.'/'] =	$rss_feeds;
			}
		}
		return $relevant_data;
	}

	/********************************************************
	* Generates an institution object that can be entered	*
	* into the database.					*
	* Forces to UTF-8, starts off with just the pdomain	*
	* (further details added in a later sweep).		*
	********************************************************/
	function create_inst_from_data($inst_pdomain)
	{
		$inst_pdomain	= iconv(mb_detect_encoding($inst_pdomain, mb_detect_order(), true), 'UTF-8//TRANSLIT', $inst_pdomain);
		$inst		= array(
					'inst_pdomain'	=> $inst_pdomain
				       );

		return $inst;
	}

	/********************************************************
	* Takes a feed from lastRSS - returns false if it is	*
	* missing any required fields (currently just the URL	*
	* is required).						*
	* Formats it into the shape expected by the database,	*
	* converting to UTF-8 and filling in any blank fields	*
	* with NULL. Adds a title-url hash for duplication-	*
	* checking purposes.					*
	********************************************************/
	function create_feed_from_rss($rss_feed)
	{
		#Maps RSS value keys to MySQL column title-required status pair.
		$field_config	= array(
					'title'		=> array(
									'db_col_name'	=> 'feed_title',
									'required'	=> false
								),
					'description'	=> array(
									'db_col_name'	=> 'feed_desc',
									'required'	=> false
								),
					'link'		=> array(
									'db_col_name'	=> 'feed_url',
									'required'	=> true
								)
				       );

		$sql_feed;
		foreach($field_config as $rss_id => $config)
		{
			$field_value	= NULL;
			if(!array_key_exists($rss_id, $rss_feed) && $config['required'])
			{
				return false;
			}
			elseif(array_key_exists($rss_id, $rss_feed))
			{
				#Forces to UTF-8.
				$rss_feed[$rss_id]	= iconv(mb_detect_encoding($rss_feed[$rss_id], mb_detect_order(), true), 'UTF-8//TRANSLIT', $rss_feed[$rss_id]);
				$field_value		= $rss_feed[$rss_id];
			}
			$sql_feed[$config['db_col_name']]	= $field_value;
		}
		$sql_feed['title_url_hash']	= hash('md5', $sql_feed['feed_title'].$sql_feed['feed_url']);

		return $sql_feed;
	}

	/********************************************************
	* Very similar to create_feed_from_rss(), but has	*
	* slight differences dues to differences in the tables	*
	* etc.							*
	********************************************************/
	function create_post_from_rss($rss_post)
	{
		#Maps RSS value keys to MySQL column title-required status pair.
		$field_config	= array(
					'title'		=> array(
									'db_col_name'	=> 'post_title',
									'required'	=> false
								),
					'description'	=> array(
									'db_col_name'	=> 'post_desc',
									'required'	=> false
								),
					'pubDate'	=> array(
									'db_col_name'	=> 'post_date',
									'required'	=> true
								),
					'link'		=> array(
									'db_col_name'	=> 'post_url',
									'required'	=> true
								)
				       );

		$sql_post;
		foreach($field_config as $rss_id => $config)
		{
			$field_value	= NULL;
			if(!array_key_exists($rss_id, $rss_post) && $config['required'])
			{
				return false;
			}
			elseif(array_key_exists($rss_id, $rss_post))
			{
				#Eliminates CDATA tags.
				$rss_post[$rss_id]	= str_replace('<![CDATA[', '', $rss_post[$rss_id]);
				$rss_post[$rss_id]	= str_replace(']]>', '', $rss_post[$rss_id]);

				#Forces to UTF-8.
				$rss_post[$rss_id]	= iconv(mb_detect_encoding($rss_post[$rss_id], mb_detect_order(), true), 'UTF-8//TRANSLIT', $rss_post[$rss_id]);
				$field_value		= $rss_post[$rss_id];
			}
			$sql_post[$config['db_col_name']]	= $field_value;
		}
		$sql_post['post_date']		= db_utilities::rss_date_to_mysql_date($sql_post['post_date']);
		$sql_post['title_url_hash']	= hash('md5', $sql_post['post_title'].$sql_post['post_url']);

		return $sql_post;
	}

}

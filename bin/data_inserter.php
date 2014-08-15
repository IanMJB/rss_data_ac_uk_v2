<?php

class data_inserter
{

	/********************************************************
	* Inserts the information about the institutions	*
	* (initially just pdomain) into the database.		*
	* Simple insert-ignore, instead of check, is sufficient	*
	* as just the URL may have changed, which results in a	*
	* fresh insert.						*
	********************************************************/
	function populate_institutions($db, $relevant_data)
	{
		$inst_insert_stmt	= NULL;
		foreach($relevant_data as $inst_pdomain => $rss_feeds)
		{
			$inst	= data_manipulator::create_inst_from_data($inst_pdomain);

			if(!$inst_insert_stmt)
			{
				$inst_insert_stmt	= db_utilities::create_insert_ignore($db, 'institutions', $inst);
			}
			$inst_insert_stmt->execute(array_values($inst));
			$affected_rows	= $inst_insert_stmt->rowCount();
			if($affected_rows === 0)
			{
				echo 'Duplicate institution, ignored: ', $inst['inst_pdomain'], "\n";
			}
			elseif($affected_rows === 1)
			{
				echo 'New institution, added: ', $inst['inst_pdomain'], "\n";
			}
			else
			{
				echo 'ERROR ADDING INSTITUTION: $inst_pdomain = ', $inst['inst_pdomain'], ', $affected_rows = ', $affected_rows, "\n";
			}
			data_inserter::populate_feeds($db, $inst_pdomain, $rss_feeds);
		}
	}

	/********************************************************
	* Queries the database for the ID assigned to the	*
	* institute provided. Then loops through the array of	*
	* RSS feeds, calling populate_single_feed() on each,	*
	* providing the ID of the parent institute.		*
	********************************************************/
	function populate_feeds($db, $inst_pdomain, $rss_feeds)
	{
		$inst_id_select_stmt	= db_utilities::create_select_single($db, 'institutions', 'inst_id', 'inst_pdomain');
		$inst_id_select_stmt->execute(array($inst_pdomain));
		$inst_id_array		= $inst_id_select_stmt->fetch();
		$inst_id		= $inst_id_array['inst_id'];

		foreach($rss_feeds as $rss_feed)
		{
			data_inserter::populate_single_feed($db, $inst_id, $rss_feed);
		}
	}

	#TODO
	#Unhack global.
	/********************************************************
	* Attempts to retrieve a feed using lastRSS - then	*
	* formats it into a database-acceptable format.		*
	* Attempts to insert into the database - either		*
	* inserting, updating or ignoring the entry, depending	*
	* on if it currently exists within, and its state	*
	* (based on title/url hash, if the URL exists but the	*
	* hash is different it is assumed to have changed and	*
	* so is updated).					*
	* Passes the array of child-posts to populate_posts().	*
	********************************************************/
	function populate_single_feed($db, $inst_id, $rss_feed)
	{
		global $last_rss;

		if($rss	= @$last_rss->get($rss_feed))
		{
			$feed			= data_manipulator::create_feed_from_rss($rss);
			if(!$feed)
			{
				return;
			}
			$feed['inst_id']	= $inst_id;

			$feed_select_stmt	= db_utilities::create_select_single($db, 'feeds', 'title_url_hash', 'feed_url');
			$feed_select_stmt->execute(array($feed['feed_url']));
			$feed_hash_array	= $feed_select_stmt->fetch();

			if(!empty($feed_hash_array))
			{
				$stored_feed_hash	= $feed_hash_array['title_url_hash'];
				if($stored_feed_hash !== $feed['title_url_hash'])
				{
					$truncated_feed		= array(
									'feed_title'		=> $feed['feed_title'],
									'feed_desc'		=> $feed['feed_desc'],
									'title_url_hash'	=> $feed['title_url_hash'],
									'inst_id'		=> $feed['inst_id']
								       );

					$feed_update_stmt		= db_utilities::create_update($db, 'feeds', $truncated_feed, 'feed_url');
					$truncated_feed['feed_url']	= $feed['feed_url'];

					$feed_update_stmt->execute(array_values($truncated_feed));
					echo 'Modified feed, updated: ', $feed['feed_url'], "\n";
				}
				else
				{
					echo 'Duplicate feed, ignored: ', $feed['feed_url'], "\n";
				}
			}
			else
			{
				$feed_insert_stmt	= db_utilities::create_insert_ignore($db, 'feeds', $feed);
				$feed_insert_stmt->execute(array_values($feed));
				echo 'New feed, added: ', $feed['feed_url'], "\n";
			}

			data_inserter::populate_posts($db, $feed['feed_url'], $rss);
		}
	}

	/********************************************************
	* Queries the database for the ID assigned to the	*
	* feed (URL) provided. Then loops through the array of	*
	* RSS posts, calling populate_single_post() on each,	*
	* providing the ID of the parent feed.			*
	********************************************************/
	function populate_posts($db, $feed_url, $rss)
	{
		$feed_select_stmt	= db_utilities::create_select_single($db, 'feeds', 'feed_id', 'feed_url');
		$feed_select_stmt->execute(array($feed_url));
		$feed_id_array		= $feed_select_stmt->fetch();
		$feed_id		= $feed_id_array['feed_id'];

		if(!empty($rss['items']))
		{
			foreach($rss['items'] as $post)
			{
				data_inserter::populate_single_post($db, $feed_id, $post);
			}
		}
	}

	/********************************************************
	* Similar to populate_single_feed().			*
	* Given a feed_id and post, formats the post and	*
	* attempts to insert it into the database (with the	*
	* attached feed_id).					*
	********************************************************/
	function populate_single_post($db, $feed_id, $post)
	{
		$post			= data_manipulator::create_post_from_rss($post);
		if(!$post)
		{
			return;
		}
		$post['feed_id']	= $feed_id;

		$post_select_stmt	= db_utilities::create_select_single($db, 'posts', 'title_url_hash', 'post_url');
		$post_select_stmt->execute(array($post['post_url']));
		$post_hash_array	= $post_select_stmt->fetch();

		if(!empty($post_hash_array))
		{
			$stored_post_hash	= $post_hash_array['title_url_hash'];
			if($stored_post_hash !== $post['title_url_hash'])
			{
				$truncated_post		= array(
								'post_title'		=> $post['post_title'],
								'post_desc'		=> $post['post_desc'],
								'post_date'		=> $post['post_date'],
								'title_url_hash'	=> $post['title_url_hash'],
								'feed_id'		=> $post['feed_id']
							       );

				$post_update_stmt		= db_utilities::create_update($db, 'posts', $truncated_post, 'post_url');
				$truncated_post['post_url']	= $post['post_url'];

				$post_update_stmt->execute(array_values($truncated_post));
				echo 'Modified post, updated: ', $post['post_url'], "\n";
			}
			else
			{
				echo 'Duplicate post, ignored: ', $post['post_url'], "\n";
			}
		}
		else
		{
			$post_insert_stmt	= db_utilities::create_insert_ignore($db, 'posts', $post);
			$post_insert_stmt->execute(array_values($post));
			echo 'New post, added: ', $post['post_url'], "\n";
		}
	}

}

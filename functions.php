<?php

include_once __DIR__.'/lib/db_utilities.php';
foreach(glob(__DIR__.'/lib/php-rss-writer-master/Source/Suin/RSSWriter/*Interface.php') as $filename)
{
	include_once $filename;
}
foreach(glob(__DIR__.'/lib/php-rss-writer-master/Source/Suin/RSSWriter/*.php') as $filename)
{
	include_once $filename;
}
use \Suin\RSSWriter\Feed;
use \Suin\RSSWriter\Channel;
use \Suin\RSSWriter\Item;

/********************************************************
* List of current university groups - ones that are	*
* potentially listed in the database.			*
* Currently hard-coded, will fix if find an online	*
* listing at data.ac.uk.				*
********************************************************/
function get_university_groups()
{
	$groups	= array(
			'1994 Group'				=> '1994_Group',
			'Cathedrals Group'			=> 'Cathedrals_Group',
			'million+'				=> 'Million_Plus',
			'N8 Research Partnership'		=> 'N8_Research_Partnership',
			'NCUK'					=> 'NCUK',
			'Russell Group'				=> 'Russell_Group',
			'University Alliance'			=> 'University_Alliance',
			'White Rose University Consortium'	=> 'White_Rose_University_Consortium',
		       );

	return $groups;
}

/********************************************************
* Creates a URL for the RSS link.			*
********************************************************/
function generate_rss_url()
{
	$rss_url	= 'rss?';
	foreach($_GET as $html_name => $html_value)
	{
		if(!is_array($html_value))
		{
			$rss_url		.= urlencode($html_name).'='.urlencode($html_value).'&';
		}
		else
		{
			foreach($html_value as $html_array_value)
			{
				$rss_url	.= urlencode($html_name).'[]='.urlencode($html_array_value).'&';
			}
		}
	}
	return $rss_url;
}

/********************************************************
* Calls the function within db_utilities to retrieve	*
* recent posts.						*
********************************************************/
function recent_posts($no_recent_posts)
{
	$db_utilities	= new db_utilities;

	$config		= parse_ini_file(__DIR__.'/secrets.ini', true);
	$db		= $db_utilities->connect($config);

	return $db_utilities->get_posts_last_x($db, $no_recent_posts);
}

/********************************************************
* Calls the function within db_utilities to retrieve	*
* recent posts within specified groups.			*
********************************************************/
function recent_posts_in_groups($no_recent_posts)
{
	$groups		= $_GET['groups'];
	$db_utilities	= new db_utilities;

	$config		= parse_ini_file(__DIR__.'/secrets.ini', true);
	$db		= $db_utilities->connect($config);

	return $db_utilities->get_posts_last_x_in_groups($db, $no_recent_posts, $groups);
}	

/********************************************************
* As above, but then generates an RSS feed out of the	*
* results using Suin RSSWriter.				*
********************************************************/
function recent_posts_as_rss($no_recent_posts)
{
	$feed		= new Feed();
	$channel	= new Channel();

	$channel	->title('Latest '.$no_recent_posts.' Results')
			->description('last '.$no_recent_posts.' results from rss.data.ac.uk')
			->url('https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'])
			->language('en-GB')
			->copyright('')
			->pubDate(time())
			->lastBuildDate(time())
			->ttl(60)
			->appendTo($feed);

	$posts		= recent_posts($no_recent_posts);
	foreach($posts as $post)
	{
		$item	= new Item();
		$item	->title($post['post_title'])
			->description(html_entity_decode($post['post_desc'], ENT_QUOTES, 'UTF-8'))
			->url($post['post_url'])
			->pubDate(strtotime($post['post_date']))
			->appendTo($channel);
	}

	return $feed;
}

/********************************************************
* As above, but within specified groups.		*
********************************************************/
function recent_posts_in_groups_as_rss($no_recent_posts)
{
	$groups		= $_GET['groups'];
	$feed		= new Feed();
	$channel	= new Channel();

	$channel	->title('Latest '.$no_recent_posts.' Results')
			->description('last '.$no_recent_posts.' results from rss.data.ac.uk')
			->url('https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'])
			->language('en-GB')
			->copyright('')
			->pubDate(time())
			->lastBuildDate(time())
			->ttl(60)
			->appendTo($feed);

	$posts		= recent_posts_in_groups($no_recent_posts);
	foreach($posts as $post)
	{
		$item	= new Item();
		$item	->title($post['post_title'])
			->description(html_entity_decode($post['post_desc'], ENT_QUOTES, 'UTF-8'))
			->url($post['post_url'])
			->pubDate(strtotime($post['post_date']))
			->appendTo($channel);
	}

	return $feed;
}

/********************************************************
* Calls the function within db_utilities to retrieve	*
* posts related to the search terms.			*
********************************************************/
function searched_posts()
{
	$terms		= $_GET['query'];
	$db_utilities	= new db_utilities;

	$config	= parse_ini_file(__DIR__.'/secrets.ini', true);
	$db	= $db_utilities->connect($config);

	return $db_utilities->get_posts_with_terms($db, $terms);
}

/********************************************************
* Calls the function within db_utilities to retrieve	*
* posts related to the search terms in the specified	*
* groups.						*
********************************************************/
function searched_posts_in_groups()
{
	$terms		= $_GET['query'];
	$groups		= $_GET['groups'];
	$db_utilities	= new db_utilities;

	$config	= parse_ini_file(__DIR__.'/secrets.ini', true);
	$db	= $db_utilities->connect($config);

	return $db_utilities->get_posts_with_terms_in_groups($db, $terms, $groups);
}

/********************************************************
* As above, but then generates an RSS feed out of the	*
* results using Suin RSSWriter.				*
********************************************************/
function searched_posts_as_rss()
{
	$terms		= $_GET['query'];
	$feed		= new Feed();
	$channel	= new Channel();

	$channel	->title('Search Results for '.$terms)
			->description('search results for '.$terms.' from rss.data.ac.uk')
			->url('https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'])
			->language('en-GB')
			->copyright('')
			->pubDate(time())
			->lastBuildDate(time())
			->ttl(60)
			->appendTo($feed);

	$posts		= searched_posts();
	foreach($posts as $post)
	{
		$item	= new Item();
		$item	->title($post['post_title'])
			->description(html_entity_decode($post['post_desc'], ENT_QUOTES, 'UTF-8'))
			->url($post['post_url'])
			->pubDate(strtotime($post['post_date']))
			->appendTo($channel);
	}

	return $feed;
}

/********************************************************
* As above, but within specified groups.		*
********************************************************/
function searched_posts_in_groups_as_rss()
{
	$terms		= $_GET['query'];
	$groups		= $_GET['groups'];
	$feed		= new Feed();
	$channel	= new Channel();

	$channel	->title('Search Results for '.$terms)
			->description('search results for '.$terms.' from rss.data.ac.uk')
			->url('https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'])
			->language('en-GB')
			->copyright('')
			->pubDate(time())
			->lastBuildDate(time())
			->ttl(60)
			->appendTo($feed);

	$posts		= searched_posts_in_groups();
	foreach($posts as $post)
	{
		$item	= new Item();
		$item	->title($post['post_title'])
			->description(html_entity_decode($post['post_desc'], ENT_QUOTES, 'UTF-8'))
			->url($post['post_url'])
			->pubDate(strtotime($post['post_date']))
			->appendTo($channel);
	}

	return $feed;
}

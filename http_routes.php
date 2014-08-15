<?php

/********************************************************
* If $_GET['query'] is set (and not null) it means a	*
* search has been input, so a search is conducted on	*
* the terms, and 'posts' is set as the result.		*
* Otherwise 'posts' is set to recent results.		*
* Filters on 'groups' if set ($_GET['groups']).		*
* Generates an RSS-URL based off of any $_GET['query']	*
* values, as well.					*
* Renders the /main.html template.			*
********************************************************/
function home($f3)
{
	if(isset($_GET['query']) && $_GET['query'] !== '')
	{
		$f3->set('title', 'Search Results for '.$_GET['query']);
		if(isset($_GET['groups']))
		{
			$f3->set('posts', searched_posts_in_groups()); 
		}
		else
		{
			$f3->set('posts', searched_posts());
		}
	}
	else
	{
		$f3->set('title', 'Latest 20 Results');
		if(isset($_GET['groups']))
		{
			$f3->set('posts', recent_posts_in_groups(20));
		}
		else
		{
			$f3->set('posts', recent_posts(20));
		}
	}

	$f3->set('groups', get_university_groups());
	$f3->set('rss_url', generate_rss_url());
        $f3->set('templates', array('home.htm'));

        echo Template::instance()->render($f3->get('STYLE').'/main.htm');
}

/********************************************************
* If $_GET['query'] is set it means a search has been	*
* input, and so search for posts with the terms and	*
* set them, else show recent posts.			*
* Filters on 'groups' if set ($_GET['groups']).		*
* Renders the /rss.xml template.			*
********************************************************/
function rss($f3)
{
	if(isset($_GET['query']) && $_GET['query'] !== '')
	{
		if(isset($_GET['groups']))
		{
			$f3->set('rss', searched_posts_in_groups_as_rss());
		}
		else
		{
			$f3->set('rss', searched_posts_as_rss());
		}
	}
	else
	{
		if(isset($_GET['groups']))
		{
			$f3->set('rss', recent_posts_in_groups_as_rss(20));
		}
		else
		{
			$f3->set('rss', recent_posts_as_rss(20));
		}
	}

	echo Template::instance()->render($f3->get('STYLE').'/rss.xml', 'application/xml');
}

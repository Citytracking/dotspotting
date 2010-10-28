<?php

	#
	# $Id$
	#

	include("include/init.php");
	loadlib("search");

	#################################################################

	if (! $GLOBALS['cfg']['enable_feature_search']){
		$GLOBALS['error']['search_disabled'] = 1;
		$smarty->display('page_search.txt');
		exit();
	}

	#################################################################

	if ($geohash = get_str('gh')){

		$page = get_int32('page');

		$args = array(
			'page' => $page
		);

		if (strlen($geohash) >= 8){
			$geohash = substr($geohash, 0, -2);
		}

		$dots = search_dots_for_geohash($geohash, $args);
		$smarty->assign_by_ref('dots', $dots);

		$smarty->display('page_search_results.txt');
		exit();
	}

	#
	# There is no search to speak of yet and the geohash stuff
	# relies on a magic rewrite rule so don't even pretend.
	# (20101026/straup)
	# 

	error_404();

	$smarty->display('page_search.txt');
	exit();
?>
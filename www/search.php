<?php

	#
	# $Id$
	#

	include("include/init.php");
	loadlib("search");

	#################################################################

	if (! $GLOBALS['cfg']['enable_feature_search']){
		$smarty->display('page_search_disabled.txt');
		exit();
	}

	#################################################################

	if ($geohash = get_str('gh')){

		$page = get_int32('page');

		$args = array(
			'page' => $page,
		);

		if (strlen($geohash) >= 8){
			$geohash = substr($geohash, 0, -2);
		}

		$dots = search_dots_for_geohash($geohash, $GLOBALS['cfg']['user']['id'], $args);
		$smarty->assign_by_ref('dots', $dots);

		if (count($dots) == 0){
			$GLOBALS['smarty']->display('page_search_noresults.txt');
			exit();
		}

		$page_as_queryarg = 0;

		if (get_str("nearby")){
			$pagination_url = "/nearby/" . urlencode($gh);
		}

		else {
			unset($_GET['page']);
			$pagination_url = "/search/?" . http_build_query($_GET);
			$page_as_queryarg = 1;
		}

		$GLOBALS['smarty']->assign("pagination_url", $pagination_url);
		$GLOBALS['smarty']->assign("pagination_page_as_queryarg", $page_as_queryarg);

		$GLOBALS['smarty']->display('page_search_results.txt');
		exit();
	}

	#
	# There is no search to speak of yet and the geohash stuff
	# relies on a magic rewrite rule so don't even pretend.
	# (20101026/straup)
	# 

	error_404();

	$GLOBALS['smarty']->display('page_search.txt');
	exit();
?>
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

	# First, do some basic args munging

	$args = array();

	foreach ($_GET as $key => $value){
		$args[ $key ] = sanitize($value, 'str');
	}

	#################################################################

	if (count($args)){

		$rsp = search_dots($args, $GLOBALS['cfg']['user']['id']);

		if ((! $rsp['ok']) || (! count($rsp['dots']))){
			$GLOBALS['smarty']->display('page_search_noresults.txt');
			exit();
		}

		$smarty->assign_by_ref('dots', $rsp['dots']);
		$page_as_queryarg = 0;

		if (($args['nearby']) && ($args['gh'])){
			$enc_gh = urlencode($args['gh']);
			$pagination_url = "/nearby/{$enc_gh}/";
		}

		else {
			unset($_GET['page']);
			$pagination_url = "/search/?" . http_build_query($_GET);
			$page_as_queryarg = 1;

			if ($_GET['u']){
				unset($_GET['u']);
				$smarty->assign("query_all_url", "/search/?" . http_build_query($_GET));
			}
		}

		$GLOBALS['smarty']->assign("pagination_url", $pagination_url);
		$GLOBALS['smarty']->assign("pagination_page_as_queryarg", $page_as_queryarg);

		$perms_map = dots_permissions_map();
		$GLOBALS['smarty']->assign_by_ref('permissions_map', $perms_map);

		$GLOBALS['smarty']->display('page_search_results.txt');
		exit();
	}

	$GLOBALS['smarty']->display('page_search.txt');
	exit();
?>
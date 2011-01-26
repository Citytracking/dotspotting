<?php

	#
	# $Id$
	#

	include("include/init.php");
	loadlib("search");

	loadlib("export");
	loadlib("formats");

	#################################################################

	if (! $GLOBALS['cfg']['enable_feature_search']){
		$smarty->display('page_search_disabled.txt');
		exit();
	}

	#################################################################

	$display = (get_str('d') == 'sheets') ? 'sheets' : 'dots';

	if (count($_GET)){

		# are we trying to export?

		if (($display == 'dots') && (isset($_GET['export']))){

			$query_args = http_build_query($_GET);
			$redir = $GLOBALS['cfg']['abs_root_url'] . 'search/export/?' . $query_args;

			header('location: ' . $redir);
			exit;
		}

		# carry on

		$page = get_str('page');
		$GLOBALS['smarty']->assign('page', $page);

		$more = array(
			'page' => $page
		);

		#
		# Go!
		#

		if ($display == 'sheets'){

			$rsp = search_sheets($_GET, $GLOBALS['cfg']['user']['id'], $more);

			if ((! $rsp['ok']) || (! count($rsp['sheets']))){
				$GLOBALS['smarty']->display('page_search_noresults.txt');
				exit();
			}

			$GLOBALS['smarty']->assign_by_ref('sheets', $rsp['sheets']);
		}

		else {

			$rsp = search_dots($_GET, $GLOBALS['cfg']['user']['id'], $more);

			if ((! $rsp['ok']) || (! count($rsp['dots']))){
				$GLOBALS['smarty']->display('page_search_noresults.txt');
				exit();
			}

			$GLOBALS['smarty']->assign_by_ref('dots', $rsp['dots']);

			$dots_indexed = dots_indexed_on($rsp['dots']);
			$GLOBALS['smarty']->assign_by_ref('dots_indexed', $dots_indexed);
		}

		#
		# Display inline
		#

		$page_as_queryarg = 0;

		#

		$args = $_GET;

		$to_remove = array(
			'format',
		);

		foreach ($to_remove as $k){

			if (isset($args[$k])){
				unset($args[$k]);
			}
		}

		$query_args = http_build_query($args);
		$GLOBALS['smarty']->assign_by_ref("enc_query_args", $query_args);

		#

		if (($args['nearby']) && ($args['gh'])){
			$enc_gh = urlencode($args['gh']);
			$pagination_url = "{$GLOBALS['cfg']['abs_root_url']}nearby/{$enc_gh}/";
		}

		else {
			unset($_GET['page']);
			$pagination_url = "{$GLOBALS['cfg']['abs_root_url']}search/?" . $query_args;
			$page_as_queryarg = 1;

			if ($_GET['u']){
				unset($_GET['u']);
				$smarty->assign("query_all_url", "{$GLOBALS['cfg']['abs_root_url']}search/?" . $query_args);
			}
		}

		$GLOBALS['smarty']->assign("pagination_url", $pagination_url);
		$GLOBALS['smarty']->assign("pagination_page_as_queryarg", $page_as_queryarg);

		$perms_map = dots_permissions_map();
		$GLOBALS['smarty']->assign_by_ref('permissions_map', $perms_map);

		$formats = array_values(formats_valid_export_map());
		$GLOBALS['smarty']->assign("export_formats", $formats);

		$GLOBALS['smarty']->display('page_search_results.txt');
		exit();
	}

	# At least until there's some sort of search UI...

	if ($GLOBALS['cfg']['enable_feature_search_facets']){
		$redir = $GLOBALS['cfg']['abs_root_url'] . "search/facets/";
		header("location: {$redir}");
		exit();
	}

	$GLOBALS['smarty']->display('page_search.txt');
	exit();
?>
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

	$query = array();

	foreach ($_GET as $key => $value){
		$query[ $key ] = sanitize($value, 'str');
	}

	#################################################################

	if (count($query)){

		$do_export = (($GLOBALS['cfg']['enable_feature_search_export']) && (get_str('export'))) ? 1 : 0;

		$more = array(
			'export_search' => $do_export,
		);

		# check export format, etc. here

		#
		# Go!
		#

		$rsp = search_dots($query, $GLOBALS['cfg']['user']['id'], $more);

		if ((! $rsp['ok']) || (! count($rsp['dots']))){
			$GLOBALS['smarty']->display('page_search_noresults.txt');
			exit();
		}

		#
		# Export this search?
		#

		if ($do_export){

			loadlib("export");
			loadlib("formats");

			$map = formats_valid_export_map('key by extension');

			$format = 'csv';
			$mimetype = $map[$format];

			$filename = "dotspotting-search.{$format}";

			if (! get_str('inline')){
				header("Content-Type: " . htmlspecialchars($mimetype));
				header("Content-Disposition: attachment; filename=\"{$filename}\"");
			}

			export_dots($rsp['dots'], $format);
			exit();
		}

		#
		# Display inline
		#

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
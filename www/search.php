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

		$do_export = 0;

		if (($GLOBALS['cfg']['enable_feature_search_export']) && (get_str('export'))){

			$format = get_str('format');

			if (! $format){
				$format = 'csv';
			}

			$map = formats_valid_export_map('key by extension');

			$do_export = (isset($map[$format])) ? 1 : 0;
		}

		$more = array(
			'export_search' => $do_export,
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
		# Export this search? (Only dots for now...)
		#

		if (($do_export) && ($display == 'dots')){

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
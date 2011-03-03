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

	if (! $GLOBALS['cfg']['enable_feature_search_export']){
		$smarty->display('page_search_disabled.txt');
		exit();
	}

	#################################################################

	$map = formats_valid_export_map('key by extension');

	$format = get_str('format');

	if (! $format){
		$format = 'csv';
	}

	if (! isset($map[$format])){
		$format = 'csv';
	}

	$page = get_str('page');

	#

	$dots = array();

	$dot_ids = array();
	$sheet_ids = array();
	$owner_ids = array();

	if ($ids = get_str('ids')){

		foreach (explode(",", $ids) as $id){

			$dot = dots_get_dot($id, $GLOBALS['cfg']['user']['id']);

			if (! $dot['id']){
				continue;
			}

			$sheet_ids[$dot['sheet_id']] ++;
			$owner_ids[$dot['user_id']] ++;

			$dot_ids[] = $dot['id'];
			$dots[] = $dot;
		}
	}

	else {

		$more = array(
			'page' => $page
		);

		$rsp = search_dots($_GET, $GLOBALS['cfg']['user']['id'], $more);

		if (! $rsp['ok']){
			exit();
		}

		$dots = $rsp['dots'];
	}

	#

	$export_more = array(
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
	);

	$export_props = export_collect_user_properties($format);
	$export_more = array_merge($export_props, $export_more);

	#

	$ok_cache = 0;

	if ($GLOBALS['cfg']['enable_feature_export_cache']){

		$ok_cache = 1;

		# basically we only bother to cache dots that are part
		# of a single sheet (because the cache invalidation on
		# anything more complicated is cry-making).

		$count_owners = count(array_keys($owner_ids));
		$count_sheets = count(array_keys($sheet_ids));

		if (($count_owners != 1) || ($count_sheets != 1)){
			$ok_cache = 0;
		}

		if (in_array($format, $GLOBALS['cfg']['export_cache_exclude_formats'])){
			$ok_cache = 0;
		}

		if (! is_dir($GLOBALS['cfg']['export_cache_root'])){
			$ok_cache = 0;
		}

	}

	#

	if (! $ok_cache){
		$export = export_dots($dots, $format, $export_more);
	}

	else {

		$owner_ids = array_keys($owner_ids);
		$sheet_ids = array_keys($sheet_ids);

		$sheet = sheets_get_sheet($sheet_ids[0]);

		$is_own = ($owner_ids[0] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;

		$tmp = $export_more;
		$tmp['dot_ids'] = $dot_ids;
		unset($tmp['viewer_id']);

		$fingerprint = md5(serialize($tmp));

		$filename = "{$sheet['id']}_{$is_own}_{$fingerprint}.{$format}";

		$cache_more = array(
			'filename' => $filename,
		);

		$cache_path = export_cache_path_for_sheet($sheet, $cache_more);

		if (file_exists($cache_path)){
			$export = $cache_path;
		}

		else {

			$export = export_dots($dots, $format, $export_more);

			if ($export){

				$cache_rsp = export_cache_store_file($export, $cache_path);

				if (! $cache_rsp['ok']){
					# log an error...
				}
			}
		}

	}

	if (! $export){
		error_500();
	}

	# go!

	$send_more = array(
		'path' => $export,
		'mimetype' => $map[$format],
		'filename' => "dotspotting-search.{$format}",
		'inline' => get_str('inline'),
	);

	if ($ok_cache){
		$send_more['unlink_file'] = 0;
		$send_more['x-headers']['Cached'] = 1;
	}

	export_send_file($export, $send_more);
	exit();
?>
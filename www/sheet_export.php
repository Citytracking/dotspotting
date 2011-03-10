<?php

	#
	# $Id$
	#

	include("include/init.php");

	loadlib("export");
	loadlib("export_cache");

	loadlib("formats");

	#################################################################

	#
	# Ensure the user, the sheet and perms
	#

	$owner = users_ensure_valid_user_from_url();

	$sheet_id = get_int64('sheet_id');

	if (! $sheet_id){
		error_404();
	}

	$sheet = sheets_get_sheet($sheet_id, $GLOBALS['cfg']['user']['id'], array('load_extent' => 1));

	if (! $sheet){
		error_404();
	}

	if (! sheets_can_view_sheet($sheet, $GLOBALS['cfg']['user']['id'])){
		error_403();
	}

	$is_own = ($sheet['user_id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;

	#
	# Ensure that this is something we can export
	#

	$format = get_str('format');

	if (! $format){
		$format = 'csv';
	}

	$map = formats_valid_export_map('key by extension');

	if (! isset($map[$format])){
		error_404();
	}

	# Hey look! At least to start we are deliberately not doing
	# any pagination on the 'dots-for-a-sheet' page. We'll see
	# how long its actually sustainable but for now it keeps a
	# variety of (display) avenues open.
	# (20101025/straup)

	$more = array(
		'per_page' => $GLOBALS['cfg']['import_max_records'],
		'sort' => request_str('_sort'),
		'order' => request_str('_order'),
	);

	$sheet['dots'] = dots_get_dots_for_sheet($sheet, $GLOBALS['cfg']['user']['id'], $more);
	$bbox = implode(", ", array_values($sheet['extent']));

	# valid extras are things like 

	$export_more = array(
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
	);

	$export_props = export_collect_user_properties($format);
	$export_more = array_merge($export_props, $export_more);

	# caching?

	$ok_cache = 1;

	if ($GLOBALS['cfg']['enable_feature_export_cache']){

		$ok_cache = 1;

		if (in_array($format, $GLOBALS['cfg']['export_cache_exclude_formats'])){
			$ok_cache = 0;
		}

		if (! is_dir($GLOBALS['cfg']['export_cache_root'])){
			$ok_cache = 0;
		}
	}

	# ok, can has file?

	if (! $ok_cache){
		$export = export_dots($sheet['dots'], $format, $export_more);
	}

	else {

		$tmp = $export_more;
		unset($tmp['viewer_id']);
		$fingerprint = md5(serialize($tmp));

		$filename = "{$sheet['id']}_{$is_own}_{$fingerprint}.{$format}";

		$cache_more = array(
			'filename' => $filename,
		);

		$cache_path = export_cache_path_for_sheet($sheet, $cache_more);

		#

		$cache_ok = 1;

		if (! file_exists($cache_path)){
			$cache_ok = 0;
		}

		#

		if ($cache_ok){
			$export = $cache_path;
		}

		else {

			$export = export_dots($sheet['dots'], $format, $export_more);

			if ($export){

				$cache_rsp = export_cache_store_file($export, $cache_path);

				if ($cache_rsp['ok']){
					unlink($export);
					$export = $cache_rsp['path'];	
				}
			}
		}
	}

	# sad face

	if (! $export){
		error_500();
	}

	# now send the file to the browser

	$send_more = array(
		'path' => $export,
		'mimetype' => $map[$format],
		'filename' => "dotspotting-sheet-{$sheet['id']}.{$format}",
		'inline' => get_str('inline'),
		'x-headers' => array(
			'Sheet-ID' => $sheet['id'],
			'Sheet-Label' => $sheet['label'],
			'Sheet-Extent' => $bbox,
		),
	);

	# this is set by default in lib_export

	if ($ok_cache){
		$send_more['unlink_file'] = 0;
		$send_more['x-headers']['Cached'] = 1;
	}

	#

	export_send_file($export, $send_more);
	exit();
?>
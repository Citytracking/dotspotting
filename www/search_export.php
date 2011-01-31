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

	if ($ids = get_str('ids')){

		foreach (explode(",", $ids) as $id){

			$dot = dots_get_dot($id, $GLOBALS['cfg']['user']['id']);

			if (! $dot['id']){
				continue;
			}

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

	$more = array(
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
	);

	$export = export_dots($dots, $format, $more);

	if (! $export){
		error_500();
	}

	# go!

	$more = array(
		'path' => $export,
		'mimetype' => $map[$format],
		'filename' => "dotspotting-search.{$format}",
		'inline' => get_str('inline'),
	);

	export_send_file($export, $more);
	exit();
?>
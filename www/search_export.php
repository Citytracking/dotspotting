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

	$more = array(
		'page' => $page
	);

	$rsp = search_dots($_GET, $GLOBALS['cfg']['user']['id'], $more);

	if ((! $rsp['ok']) || (! count($rsp['dots']))){
		$GLOBALS['smarty']->display('page_search_noresults.txt');
		exit();
	}

	#

	$mimetype = $map[$format];

	$filename = "dotspotting-search.{$format}";

	if (preg_match("/^image/", $mimetype)){
		header("Content-Type: " . htmlspecialchars($mimetype));
	}

	else if (get_str('inline')){
		# pass
	}

	else {
		header("Content-Type: " . htmlspecialchars($mimetype));
		header("Content-Disposition: attachment; filename=\"{$filename}\"");
	}

	export_dots($rsp['dots'], $format);
	exit();

?>
<?php

	#
	# $Id$
	#

	include("include/init.php");

	loadlib("export");
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

	# 

	$more = array(
		'viewer_id' => $GLOBALS['cfg']['user']['id'],
	);

	$export = export_dots($sheet['dots'], $format, $more);

	if (! $export){
		error_500();
	}

	# go!

	$more = array(
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

	export_send_file($export, $more);
	exit();
?>
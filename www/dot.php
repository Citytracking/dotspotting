<?php

	#
	# $Id$
	#

	include("include/init.php");
	loadlib("geo_geocode");

	$user = ensure_valid_user('get');

	# THIS IS WRONG AND DIRTY AND STILL NOT WORKED OUT
	# (20101024/straup)

	$dot_id = get_int64('dot_id');

	if (! $dot_id){
		error_404();
	}

	$public_id = implode("-", array($user['id'], $dot_id));

	$dot = dots_get_dot($public_id, $GLOBALS['cfg']['user']['id']);

	if (! $dot){
		error_404();
	}

	if (! dots_can_view_dot($dot, $GLOBALS['cfg']['user']['id'])){
		error_403();
	}

	$is_own = ($user['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$smarty->assign("is_own", $is_own);

	$smarty->assign_by_ref("user", $user);
	$smarty->assign_by_ref("dot", $dot);

	if ($is_own){
		$smarty->assign("permissions_map", dots_permissions_map());
		$smarty->assign("geocoder_map", geo_geocode_service_map());
	}

	$smarty->display("page_dot.txt");
	exit;
?>
<?php

	#
	# $Id$
	#

	include("include/init.php");
	loadlib("geo_geocode");

	$owner = users_ensure_valid_user_from_url();

	$dot_id = get_int64('dot_id');

	if (! $dot_id){
		error_404();
	}

	$dot = dots_get_dot($dot_id, $GLOBALS['cfg']['user']['id']);

	if (! $dot){
		error_404();
	}

	if ($dot['deleted']){
		$smarty->display("page_dot_deleted.txt");
		exit;
	}

	if (! dots_can_view_dot($dot, $GLOBALS['cfg']['user']['id'])){
		error_403();
	}

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$smarty->assign("is_own", $is_own);

	$smarty->assign_by_ref("owner", $owner);
	$smarty->assign_by_ref("dot", $dot);

	if ($is_own){
		$smarty->assign("permissions_map", dots_permissions_map());
		$smarty->assign("geocoder_map", geo_geocode_service_map());
	}

	$smarty->display("page_dot.txt");
	exit;
?>
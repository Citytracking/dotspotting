<?php

	#
	# $Id$
	#

	#################################################################

	include("include/init.php");
	loadlib("geo_geocode");

	#################################################################

	$owner = ensure_valid_user_from_url();
	
	$args = array(
		'page' => get_int64('page'),
	);

	$dots = dots_get_dots_for_user($owner, $GLOBALS['cfg']['user']['id'], $args);

	$smarty->assign_by_ref('owner', $owner);
	$smarty->assign_by_ref('dots', $dots);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$smarty->assign('is_own', $is_own);	

	if ($is_own){
		$smarty->assign("permissions_map", dots_permissions_map());
		$smarty->assign("geocoder_map", geo_geocode_service_map());
	}

	$smarty->assign("pagination_url", urls_dots_for_user($owner));

	$smarty->display('page_user_dots.txt');
	exit();

?>
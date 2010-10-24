<?php

	include("include/init.php");
	loadlib("dots");

	$user_id = get_int64('user_id');

	if (! $user_id){

		error_404();
	}

	$user = users_get_by_id($user_id);

	if ((! $user) || ($user['deleted'])){

		error_404();
	}
	
	$args = array(
		'page' => get_int64('page'),
	);

	$dots = dots_get_dots_for_user($user, $GLOBALS['cfg']['user']['id'], $args);

	$is_own = ($user['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;

	$smarty->assign('is_own', $is_own);	
	$smarty->assign_by_ref('user', $user);
	$smarty->assign_by_ref('dots', $dots);

	$smarty->assign("pagination_url", urls_dots_for_user($user));

	$smarty->assign("permissions_map", dots_permissions_map());
	$smarty->display('page_user_dots.txt');
	exit();

?>
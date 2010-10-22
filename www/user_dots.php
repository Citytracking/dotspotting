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
		'per_page' => 20,
		'spill' => 2
	);

	$dots = dots_get_dots_for_user($user, $GLOBALS['cfg']['user_id'], $args);
	
	$smarty->assign_by_ref('user', $user);
	$smarty->assign_by_ref('dots', $dots);
	$smarty->display('page_user_dots.txt');
	exit();
?>
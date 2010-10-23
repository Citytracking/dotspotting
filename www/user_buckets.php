<?php

	#
	# $Id$
	#

	include("include/init.php");

	#################################################################

	$user_id = get_int64('user_id');

	if (! $user_id){

		error_404();
	}

	$user = users_get_by_id($user_id);

	if ((! $user) || ($user['deleted'])){

		error_404();
	}

	$smarty->assign_by_ref('user', $user);

	#################################################################

	$page = get_int32('page');

	$args = array(
		'page' => $page,
	);

	$rsp = buckets_buckets_for_user($user, $GLOBALS['cfg']['user']['id'], $args);

	$smarty->assign_by_ref('buckets', $rsp['rows']);

	$smarty->assign("pagination_url", urls_buckets_for_user($user));

	$smarty->display('page_user_buckets.txt');
	exit();
?>
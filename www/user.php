<?php

	#
	# $Id$
	#

	include("include/init.php");

	loadlib("buckets");

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

	$user['counts'] = buckets_counts_for_user($user, $GLOBALS['cfg']['user']['id']);

	$smarty->display('page_user.txt');
	exit();
?>


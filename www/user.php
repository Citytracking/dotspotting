<?php

	#
	# $Id$
	#

	include("include/init.php");

	#################################################################

	$owner = ensure_valid_user_from_url();
	$smarty->assign_by_ref('owner', $owner);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$smarty->assign("is_own", $is_own);

	#################################################################

	$owner['counts'] = buckets_counts_for_user($owner, $GLOBALS['cfg']['user']['id']);

	# fetch some recent buckets for this user

	$args = array(
		'page' => 1,
		'per_page' => 10,
	);

	$buckets = buckets_buckets_for_user($owner, $GLOBALS['cfg']['user']['id'], $args);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;

	$smarty->assign("is_own", $is_own);
	$smarty->assign_by_ref('buckets', $buckets);

	#

	$smarty->display('page_user.txt');
	exit();
?>
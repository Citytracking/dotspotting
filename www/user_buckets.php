<?php

	#
	# $Id$
	#

	include("include/init.php");

	#################################################################

	$owner = ensure_valid_user_from_url();
	$smarty->assign_by_ref('owner', $owner);

	#################################################################

	$page = get_int32('page');

	$args = array(
		'page' => $page,
	);

	$buckets = buckets_buckets_for_user($owner, $GLOBALS['cfg']['user']['id'], $args);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;

	$smarty->assign("is_own", $is_own);
	$smarty->assign_by_ref('buckets', $buckets);

	$smarty->assign("pagination_url", urls_buckets_for_user($owner));

	$smarty->display('page_user_buckets.txt');
	exit();
?>
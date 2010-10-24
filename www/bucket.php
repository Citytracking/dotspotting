<?php

	#
	# $Id$
	#

	include("include/init.php");

	$user = ensure_valid_user('get');

	# THIS IS WRONG AND DIRTY AND STILL NOT WORKED OUT
	# (20101024/straup)

	$bucket_id = get_int64('bucket_id');

	if (! $bucket_id){
		error_404();
	}

	$public_id = implode("-", array($user['id'], $bucket_id));

	$bucket = buckets_get_bucket($public_id, $GLOBALS['cfg']['user']['id']);

	if (! $bucket){
		error_404();
	}

	if (! buckets_can_view_bucket($bucket, $GLOBALS['cfg']['user']['id'])){
		error_403();
	}

	$bucket['dots'] = dots_get_dots_for_bucket($bucket, $GLOBALS['cfg']['user']['id']);

	$is_own = ($user['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$smarty->assign("is_own", $is_own);

	$smarty->assign_by_ref("user", $user);
	$smarty->assign_by_ref("bucket", $bucket);

	$smarty->display("page_bucket.txt");
	exit;
?>
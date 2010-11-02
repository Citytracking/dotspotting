<?php

	#
	# $Id$
	#

	include("include/init.php");
	loadlib("geo_geocode");

	$owner = ensure_valid_user_from_url();

	$bucket_id = get_int64('bucket_id');

	if (! $bucket_id){
		error_404();
	}

	$bucket = buckets_get_bucket($bucket_id, $GLOBALS['cfg']['user']['id']);

	if (! $bucket){
		error_404();
	}

	if (! buckets_can_view_bucket($bucket, $GLOBALS['cfg']['user']['id'])){
		error_403();
	}

	#

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$smarty->assign("is_own", $is_own);

	$smarty->assign_by_ref("owner", $owner);
	$smarty->assign_by_ref("bucket", $bucket);

	# delete this bucket?

	if ($is_own){

		$crumb_key = 'delete-bucket';
		$smarty->assign("crumb_key", $crumb_key);

		if ((post_str('delete')) && (crumb_check($crumb_key))){

			if (post_str('confirm')){

				$rsp = buckets_delete_bucket($bucket);
				$smarty->assign('deleted', $rsp);
			}

			$smarty->display('page_bucket_delete.txt');
			exit();
		}
	}

	# Hey look! At least to start we are deliberately not doing
	# any pagination on the 'dots-for-a-bucket' page. We'll see
	# how long its actually sustainable but for now it keeps a
	# variety of (display) avenues open.
	# (20101025/straup)

	$more = array(
		'per_page' => $GLOBALS['cfg']['import_max_records'],
	);

	$bucket['dots'] = dots_get_dots_for_bucket($bucket, $GLOBALS['cfg']['user']['id'], $more);

	if ($is_own){
		$smarty->assign("permissions_map", dots_permissions_map());
		$smarty->assign("geocoder_map", geo_geocode_service_map());
	}

	$smarty->display("page_bucket.txt");
	exit;
?>
<?php

	#
	# $Id$
	#

	include("include/init.php");
	loadlib("export");

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

	$format = get_str('format');

	#
	# Hey look! See how we're not letting the user ask for a
	# specific format? that's because I haven't worked out the
	# code for validating mime-types and formats.
	# (20101028/straup)
	#

	$format = 'csv';

	#
	# validate format here (see above)
	#

	# Hey look! At least to start we are deliberately not doing
	# any pagination on the 'dots-for-a-bucket' page. We'll see
	# how long its actually sustainable but for now it keeps a
	# variety of (display) avenues open.
	# (20101025/straup)

	$more = array(
		'per_page' => $GLOBALS['cfg']['upload_max_records'],
	);

	$bucket['dots'] = dots_get_dots_for_bucket($bucket, $GLOBALS['cfg']['user']['id'], $more);
	$bbox = implode(", ", array_values($bucket['extent']));

	$mimetype = "text/csv";	# hack for now
	$filename = "dotspotting-bucket-{$bucket['id']}.{$format}";

	header("Content-Type: " . htmlspecialchars($mimetype));
	header("Content-Disposition: attachment; filename=\"{$filename}\"");

	header("X-Dotspotting-Bucket-ID: " . htmlspecialchars($bucket['id']));
	header("X-Dotspotting-Bucket-Label: " . htmlspecialchars($bucket['label']));
	header("X-Dotspotting-Bucket-Extent: " . htmlspecialchars($bbox));

	#
	# As of this writing, the 'export' functionality assumes that
	# there are complimentary (format)_export_(things) functions in
	# both lib_(format) and lib_export where the (things) are written
	# directly to a filehandle, or php://output. I *think* that this
	# is the right way to do it, as opposed to farming everything out
	# to smarty and enormous strings. That said, I just banged this
	# out at the end of the day so it may yet change.
	# (20101028/straup)
	#

	export_dots($bucket['dots'], $format);
	exit();
?>
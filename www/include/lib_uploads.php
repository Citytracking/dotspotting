<?php

	#
	# $Id$
	#

	#################################################################

	loadlib("csv");

	#################################################################

	function uploads_is_valid_mimetype(&$file){

		# TODO: read bits of the file?

		if (! isset($file['type'])){
			return 0;
		}

		if ($file['type'] !== 'text/csv'){
			return 0;
		}

		return 1;
	}

	#################################################################

	function uploads_process_file(&$file){

		$rsp = array( 'ok' => 0 ); 

		if ($file['type'] === 'text/csv'){
			$rsp = csv_parse_file($file['tmp_name']);
		}

		# TO DO: check $GLOBALS['cfg'] to see whether we should
		# store a permanent copy of $file['tmp_name'] somewhere
		# on disk. It would be nice to store it with the bucket
		# ID the data has been associated which we don't have
		# yet so maybe this isn't the best place to do the storing...
		# (2010107/straup) 

		return $rsp;
	}

	#################################################################

	function uploads_process_data(&$user, &$data, $more=array()){

		$bucket_rsp = buckets_create_bucket($user, $more);

		if (! $bucket_rsp['ok']){
			return $bucket_rsp;
		}

		# mostly just because it's boring to type...
		$bucket = $bucket_rsp['bucket'];		

		$dots_rsp = dots_import_dots($user, $bucket_rsp['bucket'], $data, $more);

		$dots_rsp['bucket'] = $bucket;

		$count_rsp = buckets_update_dot_count_for_bucket($bucket);
		$dots_rsp['update_bucket_count'] = $count_rsp['ok'];

		if ($more['return_dots']){
			$dots_rsp['dots'] = dots_get_dots_for_bucket($bucket, $bucket['user_id']);
		}
		
		return $dots_rsp;
	}

	#################################################################

	function uploads_exceeds_max_records($num){

		if (! $GLOBALS['cfg']['uploads_max_records']){
			return 0;
		}

		return ($GLOBALS['cfg']['uploads_max_records'] >= $num) ? 1 : 0;
	}

	#################################################################
?>
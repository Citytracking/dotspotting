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

		return $rsp;
	}

	#################################################################

	function uploads_process_data(&$user, &$data, $more=array()){

		$bucket = bucket_create_bucket($user, $more);

		if (! $bucket){
			return array(
				'ok' => 0,
				'error' => 'failed to create bucket'
			);
		}

		$rsp = dots_import_dots($user, $bucket, $data);

		$rsp['bucket'] = $bucket;

		$rsp2 = buckets_update_dot_count_for_bucket($bucket);
		$rsp['update_bucket_count'] = $rsp2['ok'];

		if ($more['return_dots']){
			$rsp['dots'] = dots_get_dots_for_bucket($rsp['bucket'], $bucket['user_id']);
		}
		
		return $rsp;
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
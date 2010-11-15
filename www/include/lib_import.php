<?php

	#
	# $Id$
	#

	#################################################################

	loadlib("csv");
	loadlib("formats");

	#################################################################

	function import_import_file(&$user, &$file, $more=array()){

		if (! import_is_valid_mimetype($file)){

			return array(
				'error' => 'invalid_mimetype',
				'ok' => 0,
			);
		}

		#
		# parse the file
		#

		$process_rsp = import_process_file($file);

		if (! $process_rsp['ok']){

			return $process_rsp;
		}

		#
		# store the data
		#

		$import_more = array(
			'return_dots' => $more['return_dots'],
			'label' => $more['label'],
			'mark_all_private' => $more['mark_all_private'],
			'mime_type' => $file['type'],
		);


		$import_rsp = import_process_data($user, $process_rsp['data'], $import_more);

		return $import_rsp;
	}

	#################################################################

	function import_import_uri(&$user, $uri, $more=array()){

		# QUESTION: do a HEAD here to check the content-type and file-size ?

		# TO DO: pass range headers here (also patch flamework to allow headers)

		$http_rsp = http_get($uri);
		dumper($http_rsp);

		if (! $http_rsp['ok']){
			return $http_rsp;
		}

		#
		# Write the file to disk
		#
	
		$type = $http_rsp['headers']['content-type'];

		$fname = tempnam("/tmp", $user['username']);
		$fh = fopen($fname, "w");

		if (! $fh){

			return array(
				'ok' => 0,
				'error' => 'failed to open tmp filehandle',
			);
		}

		fwrite($fh, $http_rsp['body']);
		fclose($fh);

		$upload = array(
			'type' => $type,
			'tmp_name' => $fname,
		);

		return import_import_file($user, $upload, $more);
	}

	#################################################################

	function import_is_valid_mimetype(&$file){

		# TODO: read bits of the file?

		if (! isset($file['type'])){
			return 0;
		}

		$map = formats_valid_import_map();
		$type = $file['type'];

		if (! isset($map[$type])){
			return 0;
		}

		return 1;
	}

	#################################################################

	# It is assumed that you've checked $file['type'] by now

	function import_process_file(&$file){

		$rsp = array(
			'ok' => 0,
		); 

		$more = array();

		if ($max = $GLOBALS['cfg']['import_max_records']){
			$more['max_records'] = $max;
		}

		if ($file['type'] === 'text/csv'){
			$rsp = csv_parse_file($file['tmp_name'], $more);
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

	function import_process_data(&$user, &$data, $more=array()){

		#
		# First do some sanity-checking on the data before
		# we bother to create a bucket.
		#

		$record = 1;

		foreach ($data as $row){

			$rsp = dots_ensure_valid_data($row);

			if (! $rsp['ok']){

				return array(
					'ok' => 0,
					'errors' => array(array(
						'error' => $rsp['error'],
						'record' => $record,
					))
				);
			}

			$record++;
		}

		#
		# CAN I HAS MAH BUCKET?
		#

		$bucket_rsp = buckets_create_bucket($user, $more);

		if (! $bucket_rsp['ok']){
			return $bucket_rsp;
		}

		$bucket = $bucket_rsp['bucket'];		

		#
		# OMG!!! IT'S FULL OF DOTS!!!!
		#
	
		$more['skip_validation'] = 1;	# see above

		$dots_rsp = dots_import_dots($user, $bucket_rsp['bucket'], $data, $more);

		# No soup for bucket! Or is it the other way around...

		if (! $dots_rsp['ok']){
			buckets_delete_bucket($bucket);
		}

		else {

			$dots_rsp['bucket'] = $bucket;

			$count_rsp = buckets_update_dot_count_for_bucket($bucket);
			$dots_rsp['update_bucket_count'] = $count_rsp['ok'];

			if ($more['return_dots']){
				$dots_rsp['dots'] = dots_get_dots_for_bucket($bucket, $bucket['user_id']);
			}
		}

		return $dots_rsp;
	}

	#################################################################

?>
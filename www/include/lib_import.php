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

		$label = ($more['label']) ? $more['label'] : $process_rsp['label'];

		$import_more = array(
			'return_dots' => $more['return_dots'],
			'extras' => $more['extras'],
			'label' => $label,
			'mark_all_private' => $more['mark_all_private'],
			'mime_type' => $file['type'],
		);

		return import_process_data($user, $process_rsp['data'], $import_more);
	}

	#################################################################

	function import_import_uri(&$user, $uri, $more=array()){

		# QUESTION: do a HEAD here to check the content-type and file-size ?

		$max_filesize = ini_get("upload_max_filesize");

		# http://www.php.net/manual/en/faq.using.php#faq.using.shorthandbytes

		if (preg_match("/^(\d+)(K|M|G)$/", $max_filesize, $m)){

			$unit = $m[1];
			$measure = $m[2];

			if ($measure == 'G'){
				$max_bytes = $unit * 1024 * 1024 * 1024; 
			}

			else if ($measure == 'M'){
				$max_bytes = $unit * 1024 * 1024; 
			}

			else {
				$max_bytes = $unit * 1024;
			}			
		}

		else {
			$max_bytes = $max_filesize;
		}

		# This is mostly just to try...
		#
		# "If no Accept header field is present, then it is assumed that the client accepts all media types.
		# If an Accept header field is present, and if the server cannot send a response which is acceptable
		# according to the combined Accept field value, then the server SHOULD send a 406 (not acceptable)
		# response." (http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html)

		$map = formats_valid_import_map();
		$accept = array_keys($map);

		if (! isset($map['text/plain'])){
			$accept[] = 'text/plain';
		}

		$headers = array(
			'Range' => "bytes=0-{$max_bytes}",
			'Accept' => implode(", ", $accept),
		);

		#
		# Try to do some basic anity checking before we suck in the entire file
		#

		if ($GLOBALS['cfg']['import_by_url_do_head']){

			$head_rsp = http_head($uri, $headers);

			if (! $head_rsp['ok']){
				return $head_rsp;
			}

			if ($head_rsp['info']['download_content_length'] > $max_bytes){

				return array(
					'ok' => 0,
					'error' => 'Remote file is too large',					
				);
			}
		}

		#
		# Go!
		#

		$http_rsp = http_get($uri, $headers);

		if (! $http_rsp['ok']){
			return $http_rsp;
		}

		#
		# Am I partial content?
		#

		if ($http_rsp['headers']['content-length'] > $max_bytes){
			# throw an error ?
		}
		
		#
		# Write the file to disk
		#

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

		#
		# Ima Viking!
		#

		$type = $http_rsp['headers']['content-type'];
		$type_map = formats_valid_import_map();

		if (! isset($type_map[$type])){

			# glurgh...
			# $finfo = new finfo(FILEINFO_MIME, "/usr/share/misc/magic");
			# $finfo->file($fname);

			if (preg_match("/\.([a-z0-9]+)$/", basename($uri), $m)){

				$ext = $m[1];
				$ext_map = formats_valid_import_map('key by extension');

				if (isset($ext_map[$ext])){
					$type = $ext_map[$ext];
				}		
			}
		}

		#
		# Okay, now hand off to the regular process
		# file uploads functionality
		#

		$upload = array(
			'type' => $type,
			'path' => $fname,
		);

		return import_import_file($user, $upload, $more);
	}

	#################################################################

	function import_is_valid_mimetype(&$file){

		#
		# TODO: read bits of the file?
		#

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

		#
		# Basic setup stuff
		#

		$rsp = array(
			'ok' => 0,
		); 

		$more = array();

		if ($max = $GLOBALS['cfg']['import_max_records']){
			$more['max_records'] = $max;
		}

		#
		# CAN HAZ FILE?
		#

		$fh = fopen($file['path'], 'r');

		if (! $fh){

			return array(
				'ok' => 0,
				'error' => 'failed to open file'
			);
		}

		#
		# Store the $file hash we're passing around. It may be the case
		# that some import related libraries do not have functions for
		# working with filehandles (DOMDocument for example...wtf?)
		#

		$more['file'] = $file;

		#
		# Okay, now figure what we need to load and call. We
		# do this by asking the import map for an extension
		# corresponding to the file's mime-type (note: at some
		# point we may need to make this a bit more fine-grained
		# but today we don't) and then load lib_EXTENSION and
		# call that library's 'parse_fh' function.
		#

		$map = formats_valid_import_map();

		$type = $map[$file['type']];
		$func = "{$type}_parse_fh";

		#
		# HEY LOOK! THIS PART IS IMPORTANT!! It is left to the
		# format specific libraries to sanitize both field names
		# and values (using lib_sanitize). This is *not* a 
		# question of validating the data (checking lat/lon
		# ranges etc.) but just making sure that the user isn't
		# passing in pure crap. Take a look at the parse_fh function
		# in lib_csv for an example of how/what to do.
		#

		loadlib($type);

		$rsp = call_user_func_array($func, array($fh, $more));

		# TO DO: check $GLOBALS['cfg'] to see whether we should
		# store a permanent copy of $file['tmp_name'] somewhere
		# on disk. It would be nice to store it with the sheet
		# ID the data has been associated which we don't have
		# yet so maybe this isn't the best place to do the storing...
		# (2010107/straup) 

		return $rsp;
	}

	#################################################################

	function import_process_data(&$user, &$data, $more=array()){

		#
		# First do some sanity-checking on the data before
		# we bother to create a sheet.
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
		# CAN I HAS MAH SHEET?
		#

		$sheet_rsp = sheets_create_sheet($user, $more);

		if (! $sheet_rsp['ok']){
			return $sheet_rsp;
		}

		$sheet = $sheet_rsp['sheet'];		

		#
		# OMG!!! IT'S FULL OF DOTS!!!!
		#
	
		$more['skip_validation'] = 1;	# see above

		$dots_rsp = dots_import_dots($user, $sheet_rsp['sheet'], $data, $more);

		# No soup for sheet! Or is it the other way around...

		if (! $dots_rsp['ok']){
			sheets_delete_sheet($sheet);
		}

		else {

			$dots_rsp['sheet'] = $sheet;

			$count_rsp = sheets_update_dot_count_for_sheet($sheet);
			$dots_rsp['update_sheet_count'] = $count_rsp['ok'];

			if ($more['return_dots']){
				$dots_rsp['dots'] = dots_get_dots_for_sheet($sheet, $sheet['user_id']);
			}
		}

		return $dots_rsp;
	}

	#################################################################

?>
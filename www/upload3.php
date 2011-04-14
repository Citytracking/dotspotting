<?php

	#
	# $Id$
	#

	include("include/init.php");

	loadlib("import");
	loadlib("formats");

	loadlib("flickr");
	loadlib("google");

	#################################################################

	login_ensure_loggedin("{$GLOBALS['cfg']['abs_root_url']}upload");

	# temporary bits until everything gets merged in to one
	# magic upload box...

	$GLOBALS['smarty']->assign("include_url_upload", 1);

	if (! $GLOBALS['cfg']['enable_feature_import']){

		$GLOBALS['error']['uploads_disabled'] = 1;
		$smarty->display("page_upload_disabled.txt");
		exit();
	}

	#################################################################

	$crumb_key = 'upload';
	$crumb_ok = crumb_check($crumb_key);

	$GLOBALS['smarty']->assign("crumb_key", $crumb_key);

	#

	$label = filter_strict(post_str('label'));
	$private = (post_str('private')) ? 1 : 0;
	$dots_index_on = filter_strict(post_str('dots_index_on'));
	$mime_type = filter_strict(post_str('mime_type'));

	$GLOBALS['smarty']->assign("label", $label);
	$GLOBALS['smarty']->assign("private", $private);
	$GLOBALS['smarty']->assign("dots_index_on", $dots_index_on);
	$GLOBALS['smarty']->assign("mime_type", $mime_type);

	#
	# First grab the file and do some basic validation
	#

	# Ideally the front end should remove the 'upload' parameter but
	# just in case...

	if (($crumb_ok) && ($_FILES['upload']) && (! post_str('url'))){

		$GLOBALS['smarty']->assign('step', 'process');

		$ok = 1;

		if ($_FILES['upload']['error']){

			$GLOBALS['error']['upload_error'] = 1;
			$GLOBALS['error']['upload_error_msg'] = $_FILES['upload']['error'];
			$ok = 0;
		}

		if ($ok){

			$more = array();

			if ($mime_type){
				$more['assume_mime_type'] = $mime_type;
			}

			if (! import_is_valid_mimetype($_FILES['upload'], $more)){
				$GLOBALS['error']['invalid_mimetype'] = 1;
				$ok = 0;
			}

			# okay. try to pre-process the data

			else {

				$_FILES['upload']['path'] = $_FILES['upload']['tmp_name'];

				$fingerprint = md5_file($_FILES['upload']['path']);
				$GLOBALS['smarty']->assign("fingerprint", $fingerprint);

				$sheets = sheets_lookup_by_fingerprint($fingerprint, $GLOBALS['cfg']['user']['id']);
				$GLOBALS['smarty']->assign_by_ref("sheets", $sheets);

				$more = array(
					'dots_index_on' => $dots_index_on,
				);

				$pre_process = import_process_file($_FILES['upload'], $more);

				# convert any errors from a bag of arrays in to a hash
				# where the key maps to record number (assuming the count
				# starts at 1.

				if (count($pre_process['errors'])){

					$_errors = array();

					foreach ($pre_process['errors'] as $e){
						$_errors[$e['record']] = $e;
					}

					$pre_process['errors'] = $_errors;
				}

				$GLOBALS['smarty']->assign_by_ref("pre_process", $pre_process);

				# store the file somewhere in a pending bin?
			}
		}
	}

	# Upload by URL

	else if (($crumb_ok) && (post_str('url'))){

		$url = post_str('url');

		# Can I even upload by URL?

		if (! $GLOBALS['cfg']['enable_feature_import_by_url']){

			$GLOBALS['error']['uploads_by_url_disabled'] = 1;
			$smarty->display("page_upload_disabled.txt");
			exit();
		}

		$parsed = utils_parse_url($url);
		$ok = $parsed['ok'];

		# dumper($parsed);

		$error_details = '';

		if (($ok) && (! in_array($parsed['scheme'], array('http', 'https')))){
			$error_details = 'Invalid scheme. Only http and https are currently supported.';
			$ok = 0;
		}

		if (($ok) && (! $parsed['host'])){
			$error_details = 'Missing or invalid hostname.';
			$ok = 0;
		}

		# Check to make sure there is a path ?

		if (($ok) && (is_array($GLOBALS['cfg']['import_by_url_blacklist']))){

			if (in_array($parsed['host'], $GLOBALS['cfg']['import_by_url_blacklist'])){
				$error_details = 'Uploads not allowed from host.';
				$ok = 0;
			}
		}

		else if (is_array($GLOBALS['cfg']['import_by_url_whitelist'])){

			if (! in_array($parsed['host'], $GLOBALS['cfg']['import_by_url_whitelist'])){
				$error_details = "Uploads not allowed from host: {$parsed['host']}.";
				$ok = 0;
			}
		}

		else {}

		if (! $ok){

			$GLOBALS['error']['invalid_url'] = 1;
			$GLOBALS['error']['details'] = $error_details;

			$GLOBALS['smarty']->display('page_upload3.txt');
			exit();
		}

		# Confirmation and/or remote fetching

		$GLOBALS['smarty']->assign_by_ref('parsed_url', $parsed);
		$GLOBALS['smarty']->assign('url', $url);

		# Is this remote URL special?

		$is_flickr = 0;
		$is_google = 0;

		if (preg_match("/(www\.)?flickr\.com/", $parsed['host'])){
			$is_flickr = 1;
		}

		else if (google_is_mymaps_hostname($parsed['host'])){
			$is_google = 1;
		}

		else {}

		$GLOBALS['smarty']->assign("is_flickr", $is_flickr);
		$GLOBALS['smarty']->assign("is_google", $is_google);

		# This is an upload from some random remote site
		# Please to make sure you are saying yes, ok?

		if (! post_isset('confirm')){

			$GLOBALS['smarty']->assign('step', 'confirm');
			$GLOBALS['smarty']->display('page_upload3.txt');
			exit();
		}

		# Am I Flickr?

		if ($is_flickr){

			if ($feed_url = flickr_get_georss_feed($url)){
				$url = $feed_url;
			}

			else {
				$GLOBALS['error']['no_feed_url'] = 1;
				$ok = 0;
			}
		}

		# Am I Google?

		else if ($is_google){

			if ($feed_url = google_get_mymaps_kml_feed($url)){
				$url = $feed_url;
			}

			else {
				$GLOBALS['error']['no_feed_url'] = 1;
				$ok = 0;
			}
		}

		# I am URL!

		else {}

		if (! $ok){
			$GLOBALS['smarty']->display('page_upload3.txt');
			exit();
		}

		# Okay, try to fetch the file...

		if ($mime_type = post_str('mime_type')){
			$more['assume_mime_type'] = $mime_type;
		}

		if ($is_flickr){
			$more['assume_mime_type'] = 'application/rss+xml';
		}

		else if ($is_google){
			$more['assume_mime_type'] = 'application/vnd.google-earth.kml+xml';
		}

		#

		$upload = import_fetch_uri($GLOBALS['cfg']['user'], $url, $more);

		# dumper($upload);

		if (! $upload['ok']){

			$GLOBALS['error']['upload_by_url_error'] = 1;
			$GLOBALS['error']['details'] = $upload['error'];
			$GLOBALS['smarty']->display('page_upload3.txt');
			exit();
		}

		# Okay, now process the file

		$more = array(
			'dots_index_on' => $dots_index_on,
		);

		$pre_process = import_process_file($upload, $more);

		# dumper($pre_process);

		# convert any errors from a bag of arrays in to a hash
		# where the key maps to record number (assuming the count
		# starts at 1.

		if (count($pre_process['errors'])){

			$_errors = array();

			foreach ($pre_process['errors'] as $e){
				$_errors[$e['record']] = $e;
			}

			$pre_process['errors'] = $_errors;
		}

		$GLOBALS['smarty']->assign_by_ref("pre_process", $pre_process);
		$GLOBALS['smarty']->assign('step', 'process');
	}

	#
	# Okay, finally try to import the data. Note that we re-validate $data
	# here and we don't reassign the (Smarty) $step variable until everything
	# looks like it's okay.
	#

	else if (($crumb_ok) && (post_str("data"))){

		$GLOBALS['smarty']->assign('step', 'process');

		$fingerprint = post_str('fingerprint');
		$mime_type = post_str('mime_type');
		$simplified = post_str('simplified');

		$raw_data = post_str("data");
		$data = json_decode($raw_data, "as hash");

		$ok = 1;

		if (! $data){

			$GLOBALS['error']['missing_data'] = 1;
			$ok = 0;
		}

		if ($ok){

			$more = array(
				'dots_index_on' => $dots_index_on,
			);

			$pre_process = import_ensure_valid_data($data);

			if (! $pre_process['ok']){

				$GLOBALS['error']['invalid_data'] = 1;
				$ok = 0;

				$pre_process['data'] = $data;

				if (count($pre_process['errors'])){

					$_errors = array();

					foreach ($pre_process['errors'] as $e){
						$_errors[$e['record']] = $e;
					}

					$pre_process['errors'] = $_errors;
				}

				$GLOBALS['smarty']->assign_by_ref("pre_process", $pre_process);
				
			}
		}

		#
		# Everything looks good, so let's try to talk to the database.
		# Note the part where we're also re-assign $step (below).
		#

		if ($ok){

			$GLOBALS['smarty']->assign('step', 'import');

			$more = array(
				'return_dots' => 0,
				'dots_index_on' => $dots_index_on,
				'label' => $label,
				'mark_all_private' => $private,
				'mime_type' => $mime_type,
				'fingerprint' => $fingerprint,
				'simplified' => $simplified,
			);

			$import = import_process_data($GLOBALS['cfg']['user'], $data, $more);
			$GLOBALS['smarty']->assign_by_ref("import", $import);
				
		}
	}

	else {

		# nuthin' 
	}
	
	$import_formats = formats_valid_import_map('key by extension');
	$GLOBALS['smarty']->assign_by_ref("import_formats", $import_formats);
	
	$import_formats_pretty = formats_pretty_import_names_map();
	$GLOBALS['smarty']->assign_by_ref("import_formats_pretty", $import_formats_pretty);

	$smarty->display("page_upload3.txt");
	exit();
?>

<?php

	#
	# $Id$
	#

	# HEY LOOK! THIS STILL DOESN'T HAVE ANY KIND OF SANE INPUT VALIDATION.

	include("include/init.php");
	loadlib("import");

	#################################################################

	#
	# First, just check that uploads work
	#

	if (! $GLOBALS['cfg']['enable_feature_import']){

		$GLOBALS['error']['uploads_disabled'] = 1;
		$smarty->display("page_upload_disabled.txt");
		exit();
	}

	if (! $GLOBALS['cfg']['enable_feature_import_by_url']){

		$GLOBALS['error']['uploads_by_url_disabled'] = 1;
		$smarty->display("page_upload_disabled.txt");
		exit();
	}

	login_ensure_loggedin("{$GLOBALS['cfg']['abs_root_url']}upload/url/?url=" . urlencode($url));

	#
	# Start setting things up...
	#

	$crumb_key = 'upload';
	$smarty->assign("crumb_key", $crumb_key);

	#
	# Ensure there's a URL and that the user is logged in
	#

	$url = request_str('url');

	if (! $url){
		$GLOBALS['smarty']->display('page_upload_by_url_form.txt');
		exit();
	}

	#
	# Validate $url here
	#

	$parsed = dotspotting_parse_url($url);
	$ok = $parsed['ok'];

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

	#
	# Check to make sure that 
	#

	if (($ok) && (is_array($GLOBALS['cfg']['import_by_url_blacklist']))){

		if (in_array($parsed['host'], $GLOBALS['cfg']['import_by_url_blacklist'])){
			$error_details = 'Uploads not allowed from host.';
			$ok = 0;
		}
	}

	else if (is_array($GLOBALS['cfg']['import_by_url_whitelist'])){

		if (! in_array($parsed['host'], $GLOBALS['cfg']['import_by_url_whitelist'])){
			$error_details = 'Uploads not allowed from host.';
			$ok = 0;
		}		
	}

	else {} 

	#
	# Okay, you buy?
	#

	if (! $ok){
		$GLOBALS['error']['invalid_url'] = 1;
		$GLOBALS['error']['details'] = $error_details;
		$GLOBALS['smarty']->display('page_upload_by_url_form.txt');
		exit();
	}

	#
	# Confirmation and/or remote fetching
	#

	$smarty->assign_by_ref('parsed_url', $parsed);
	$smarty->assign('url', $url);

	if ((post_isset('confirm')) && (crumb_check($crumb_key))){

		$label = filter_strict(post_str('label'));
		$private = (post_str('private')) ? 1 : 0;

		$more = array(
			'label' => $label,
			'mark_all_private' => $private,
			'return_dots' => 0,
		);

		$rsp = import_import_uri($GLOBALS['cfg']['user'], $url, $more);
		$smarty->assign_by_ref('import', $rsp);
	}

	$smarty->display("page_upload_by_url.txt");
	exit();
?>
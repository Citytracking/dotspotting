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

	if (($ok) && (! in_array($parsed['scheme'], array('http', 'https')))){
		$ok = 0;
	}

	if (($ok) && (! $parsed['host'])){
		$ok = 0;
	}

	# ensure a path ?

	if (! $ok){
		$GLOBALS['error']['invalid_url'] = 1;
		$GLOBALS['smarty']->display('page_upload_by_url_form.txt');
		exit();
	}

	#
	# Confirmation and/or remote fetching
	#

	$smarty->assign_by_ref('parsed_url', $parsed);
	$smarty->assign('url', $url);

	if ((post_isset('confirm')) && (crumb_check($crumb_key))){
		$rsp = import_import_uri($GLOBALS['cfg']['user'], $url);
		$smarty->assign_by_ref('import', $rsp);
	}

	$smarty->display("page_upload_by_url.txt");
	exit();
?>
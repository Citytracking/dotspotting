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

	if (! $GLOBALS['cfg']['enable_feature_uploads']){

		$GLOBALS['error']['uploads_disabled'] = 1;
		$smarty->display("page_upload_disabled.txt");
		exit();
	}

	if (! $GLOBALS['cfg']['enable_feature_uploads_by_url']){

		$GLOBALS['error']['uploads_by_url_disabled'] = 1;
		$smarty->display("page_upload_disabled.txt");
		exit();
	}

	#
	# Ensure there's a URL and that the user is logged in
	#

	$url = request_str('url');

	if (! $url){
		error_404();
	}

	login_ensure_loggedin("/upload/url/?url=" . urlencode($url));

	#
	# Start setting things up...
	#

	$crumb_key = 'upload';
	$smarty->assign("crumb_key", $crumb_key);

	#
	# Validate $url here
	#

	$smarty->assign('url', $url);

	#
	# Okay, now fetch the file here
	#

	if ((post_isset('confirm')) && (crumb_check($crumb_key))){

		$rsp = import_import_uri($GLOBALS['cfg']['user'], $url);
		$smarty->assign_by_ref('import', $rsp);
	}

	#
	# Happy happy!
	#

	$smarty->display("page_upload_by_url.txt");
	exit();
?>
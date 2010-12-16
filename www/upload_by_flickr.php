<?php

	include("include/init.php");

	# Hey! See this? That's because this page doesn't *do* anything yet...

	error_404();

	loadlib("import");
	loadlib("http");

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

	$loggedin_url = "{$GLOBALS['cfg']['abs_root_url']}upload/flickr/";

	if ($url = get_str("url")){
		$loggedin_url .= "?url=" . urlencode($url);
	}

	login_ensure_loggedin($loggedin_url);

	# display form / get flickr url

	# validate flickr url

	# fetch flickr url

	# parse response for georss feed url:
	# <link rel="alternate"	 type="application/rss+xml" title="Flickr: Your Photostream RSS feed"

	# hand off to import_import_uri()
?>
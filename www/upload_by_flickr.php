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

	$url = get_str("url");

	$loggedin_url = "{$GLOBALS['cfg']['abs_root_url']}upload/flickr/";

	if ($url){
		$loggedin_url .= "?url=" . urlencode($url);
	}

	login_ensure_loggedin($loggedin_url);

	#

	$feed_url = null;

	$http_rsp = http_get($url);

	$html = mb_convert_encoding($http_rsp['body'], 'html-entities', 'utf-8');

	libxml_use_internal_errors(true);

	$doc = new DOMDocument();
	$ok = $doc->loadHTML($html);

	foreach ($doc->getElementsByTagName('link') as $link){

		if ($link->getAttribute('rel') != 'alternate'){
			continue;
		}

		if ($link->getAttribute('type') != 'application/rss+xml'){
			continue;
		}

		$href = $link->getAttribute('href');

		# For example (note how we ask for RSS 2.0 explicitly) :
		# http://api.flickr.com/services/feeds/geo/?id=35034348999@N01&amp;lang=en-us 

		if (preg_match("/\/geo\//", $href)){
			$feed_url = $href . "&format=rss_200";
			break;
		} 
	}

	# check $feed_url here

	# because flickr returns text/xml

	$more = array(
		'assume_mimetype' => 'application/rss+xml',
	);

	$import_rsp = import_import_uri($GLOBALS['cfg']['user'], $feed_url, $more);

?>
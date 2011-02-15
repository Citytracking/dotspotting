<?php

	include("include/init.php");

	loadlib("import");
	loadlib("google");
	loadlib("utils");

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

	#

	$url = request_str("url");

	$loggedin_url = "{$GLOBALS['cfg']['abs_root_url']}upload/mymaps/";

	if ($url){
		$loggedin_url .= "?url=" . urlencode($url);
	}

	login_ensure_loggedin($loggedin_url);

	#

	$crumb_key = 'upload';
	$GLOBALS['smarty']->assign("crumb_key", $crumb_key);

	#

	$ok = 1;

	if ($url){
		
		$parsed_url = utils_parse_url($url);

		if (! preg_match("/maps\.google\.com/", $parsed_url['host'])){
			$GLOBALS['error']['not_flickr'] = 1;
			$ok = 0;
		}

		$GLOBALS['smarty']->assign("url", $url);
		$GLOBALS['smarty']->assign("parsed_url", $parsed_url);
	}

	if (($url) && ($ok)){

		$feed_url = google_get_mymaps_georss_feed($url);

		if (! $feed_url){
			$GLOBALS['error']['no_feed_url'] = 1;
			$ok = 0;
		}
	}

	#

	if (($url) && ($ok) && post_str('confirm') && crumb_check($crumb_key)){

		$label = filter_strict(post_str('label'));
		$private = (post_str('private')) ? 1 : 0;

		$more = array(
			'label' => $label,
			'mark_all_private' => $private,
			'return_dots' => 0,

			# because the GOOG returns text/xml
			'assume_mime_type' => 'application/rss+xml',
		);

		if ($GLOBALS['cfg']['enable_feature_dots_indexing']){
			$more['dots_index_on'] = post_str('dots_index_on');
		}

		$import_rsp = import_import_uri($GLOBALS['cfg']['user'], $feed_url, $more);
		$GLOBALS['smarty']->assign_by_ref("import_rsp", $import_rsp);
	}

	#

	$GLOBALS['smarty']->display("page_upload_by_mymaps.txt");
	exit();

?>
<?php

	#
	# $Id$
	#

	include("include/init.php");

	if (! $GLOBALS['cfg']['user']['id']){

		header("location: {$GLOBALS['cfg']['abs_root_url']}");
		exit();
	}
	$url = get_str('url');
	$url = rtrim($url, '/');

	$redir = urls_url_for_user($GLOBALS['cfg']['user']) . $url;

	header("location: {$redir}");
	exit();
?>
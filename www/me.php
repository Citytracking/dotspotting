<?php

	#
	# $Id$
	#

	include("include/init.php");

	if (! $GLOBALS['cfg']['user']['id']){

		$url = $_SERVER['REQUEST_URI'];
		$signin = $GLOBALS['cfg']['abs_root_url'] . "signin/?redir=" . urlencode($url);

		header("location: {$signin}");
		exit();
	}

	$url = get_str('url');
	$url = rtrim($url, '/');

	$redir = urls_url_for_user($GLOBALS['cfg']['user']) . $url;

	header("location: {$redir}");
	exit();
?>
<?php

	#
	# $Id$
	#

	#################################################################

	function urls_user(&$user){

		return 	$GLOBALS['cfg']['abs_root_url'] . "/u/{$user['id']}/";
	}

	#################################################################

	function urls_dots_for_user(&$user){

		return urls_user($user) . "dots";
	}

	#################################################################

	function urls_buckets_for_user(&$user){

		return urls_user($user) . "buckets";
	}

	#################################################################
?>
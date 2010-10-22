<?php

	#
	# $Id$
	#

	#################################################################

	function urls_url_for_user(&$user){

		return 	$GLOBALS['cfg']['abs_root_url'] . "/u/{$user['id']}/";
	}

	#################################################################

	function urls_url_for_bucket(&$bucket){

		$user = users_get_by_id($bucket['user_id']);
		return urls_buckets_for_user($user) . "{$bucket['id']}/";
	}

	#################################################################

	function urls_url_for_dot(&$dot){

		$user = users_get_by_id($dot['user_id']);
		return urls_buckets_for_user($user) . "{$dot['id']}/";
	}

	#################################################################

	function urls_dots_for_user(&$user){

		return urls_url_for_user($user) . "dots/";
	}

	#################################################################

	function urls_buckets_for_user(&$user){

		return urls_url_for_user($user) . "buckets/";
	}

	#################################################################

?>
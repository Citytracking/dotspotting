<?php

	#
	# $Id$
	#

	#################################################################

	function urls_url_for_user(&$user){

		return 	$GLOBALS['cfg']['abs_root_url'] . "u/{$user['id']}/";
	}

	#################################################################

	function urls_url_for_sheet(&$sheet){

		$user = users_get_by_id($sheet['user_id']);
		return urls_sheets_for_user($user) . "{$sheet['id']}/";
	}

	#################################################################

	function urls_url_for_dot(&$dot){

		$user = users_get_by_id($dot['user_id']);
		return urls_dots_for_user($user) . "{$dot['id']}/";
	}

	#################################################################

	function urls_dots_for_user(&$user){

		return urls_url_for_user($user) . "dots/";
	}

	#################################################################

	function urls_sheets_for_user(&$user){

		return urls_url_for_user($user) . "sheets/";
	}

	#################################################################

?>
<?php

	#
	# $Id$
	#

	include("include/init.php");

	#################################################################

	$owner = users_ensure_valid_user_from_url();
	$smarty->assign_by_ref('owner', $owner);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$smarty->assign("is_own", $is_own);

	#################################################################
    
    // need to look more into whether $GLOBALS['cfg']['user']['id'] should always be a string
    // because it is failing when doing strict type comparisons 
	$owner['counts'] = sheets_counts_for_user($owner, (string)$GLOBALS['cfg']['user']['id']);

	# fetch some recent sheets for this user

	$args = array(
		'page' => 1,
		'per_page' => 10,
	);

	$sheets = sheets_sheets_for_user($owner, (string)$GLOBALS['cfg']['user']['id'], $args);

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;

	$smarty->assign("is_own", $is_own);
	$smarty->assign_by_ref('sheets', $sheets);

	#
	$smarty->display('page_user.txt');
	exit();
?>
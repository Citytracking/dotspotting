<?php

	#
	# $Id$
	#

	include("include/init.php");

	#################################################################

	$owner = ensure_valid_user_from_url();
	$smarty->assign_by_ref('owner', $owner);

	#################################################################

	$owner['counts'] = buckets_counts_for_user($owner, $GLOBALS['cfg']['user']['id']);

	$smarty->display('page_user.txt');
	exit();
?>
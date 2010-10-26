<?php

	#
	# $Id$
	#

	include("include/init.php");

	#################################################################

	#
	# Is logged in?
	#

	if ($GLOBALS['cfg']['user']['id']){

		# Assign counts if showing dashboard on splash page

		$counts = buckets_counts_for_user($GLOBALS['cfg']['user'], $GLOBALS['cfg']['user']['id']);
		$GLOBALS['cfg']['user']['counts'] = $counts;
	}

	$recent_dots = dots_get_dots_recently_imported();
	$smarty->assign_by_ref('recent_dots', $recent_dots);

	#################################################################

	$smarty->display('page_index.txt');
	exit();
?>
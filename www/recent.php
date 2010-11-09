<?php

	#
	# $Id$
	#

	include("include/init.php");

	$recent_dots = dots_get_dots_recently_imported();
	$smarty->assign_by_ref('recent_dots', $recent_dots);

	$smarty->display("page_recent.txt");
	exit();
?>
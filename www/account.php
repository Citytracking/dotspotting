<?
	#
	# $Id$
	#

	include("include/init.php");

	login_ensure_loggedin("{$GLOBALS['cfg']['abs_root_url']}/account");


	#
	# output
	#

	$smarty->display("page_account.txt");
?>
<?php

	#
	# $Id$
	#

	$GLOBALS['this_is_api'] = 1;
	include("include/init.php");

	loadlib("api");
	loadlib("api_output_rest");

	api_dispatch();
	exit();
?>
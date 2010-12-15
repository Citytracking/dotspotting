<?php

	#
	# $Id$
	#

	include("include/init.php");
	loadlib("search_facets");

	if (! $GLOBALS['cfg']['enable_feature_search_facets']){
		$GLOBALS['smarty']->display("page_search_facets_disabled.txt");
		exit;
	}

	if ($name = get_str('name')){
		$values = search_facets_values_by_name($name, $GLOBALS['cfg']['user']['id']);
		$GLOBALS['smarty']->assign_by_ref("values", $values['rows']);
		$GLOBALS['smarty']->assign("name", $name);
	}

	else {
		$names = search_facets_by_name($GLOBALS['cfg']['user']['id']);
		$GLOBALS['smarty']->assign_by_ref("names", $names['rows']);
	}

	$GLOBALS['smarty']->display("page_search_facets.txt");
	exit();
?>
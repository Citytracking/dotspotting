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

	$user_id = get_int64("u");
	$page = get_int32('page');

	$more = array(
		'user_id' => $user_id,
		'page' => $page,
	);

	if ($name = get_str('name')){
		$values = search_facets_extras_values_by_name($name, $GLOBALS['cfg']['user']['id'], $more);
		$GLOBALS['smarty']->assign_by_ref("values", $values['rows']);
		$GLOBALS['smarty']->assign("name", $name);
	}

	else {
		$names = search_facets_by_extras_name($GLOBALS['cfg']['user']['id'], $more);
		$GLOBALS['smarty']->assign_by_ref("names", $names['rows']);
	}

	$GLOBALS['smarty']->display("page_search_facets.txt");
	exit();
?>
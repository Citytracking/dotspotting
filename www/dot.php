<?php

	#
	# $Id$
	#

	include("include/init.php");
	loadlib("geo_geocode");

	$owner = users_ensure_valid_user_from_url();

	$dot_id = get_int64('dot_id');

	if (! $dot_id){
		error_404();
	}

	$dot = dots_get_dot($dot_id, $GLOBALS['cfg']['user']['id']);

	if (! $dot){
		error_404();
	}

	if ($dot['deleted']){
		$smarty->display("page_dot_deleted.txt");
		exit;
	}

	if ($dot['user_id'] != $owner['id']){
		error_404();
	}

	if (! dots_can_view_dot($dot, $GLOBALS['cfg']['user']['id'])){
		error_403();
	}

	$dot['bookends'] = dots_get_bookends_for_dot($dot, $GLOBALS['cfg']['user']['id']);

	// quickly determine if dot is a magical one

	$smarty->assign("dot_is_flickr", ((isset($dot['details']['flickr:id']) && !empty($dot['details']['flickr:id']) ) ? true : false) );
	$smarty->assign("dot_is_youtube", ((isset($dot['details']['youtube:id']) && !empty($dot['details']['youtube:id']) ) ? true : false) );
	$smarty->assign("dot_is_woeid", ((isset($dot['details']['yahoo:woeid']) && !empty($dot['details']['yahoo:woeid']) ) ? true : false) );
	$smarty->assign("dot_is_oam", ((isset($dot['details']['oam:mapid']) && !empty($dot['details']['oam:mapid']) ) ? true : false) );
	$smarty->assign("dot_is_walkingpapers", ((isset($dot['details']['walkingpapers:scanid']) && !empty($dot['details']['walkingpapers:scanid']) ) ? true : false) );
	$smarty->assign("dot_is_foursquare", ((isset($dot['details']['foursquare:venue']) && !empty($dot['details']['foursquare:venue']) ) ? true : false) );

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$smarty->assign("is_own", $is_own);

	$smarty->assign_by_ref("owner", $owner);
	$smarty->assign_by_ref("dot", $dot);

	# define the selected title field
	$title_field = NULL;

	if(isset($dot['details']['title_internal'])){

		preg_match_all("/[\{](.+?)[\}]/i", $dot['details']['title_internal'][0]['value'], $title_matches);

		if(isset($title_matches[1]) && !empty($title_matches[1])){
			/*
			foreach($title_matches[1] as $match){
				var_dump($match);
			}
			*/
			$title_field = $title_matches[1][0];
		}
	}
	//[\{](.+?)[\}] -- gets all template patterns
	$GLOBALS['smarty']->assign_by_ref("assigned_title", $title_field);

	if ($is_own){
		$smarty->assign("permissions_map", dots_permissions_map());
		$smarty->assign("geocoder_map", geo_geocode_service_map());
	}

	$smarty->display("page_dot.txt");
	exit;
?>

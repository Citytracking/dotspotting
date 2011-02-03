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
	
	//	quickly determine if dot is a magical one
	$smarty->assign("dot_is_flickr", ((isset($dot['details']['flickr:id']) && !empty($dot['details']['flickr:id']) ) ? true : false) );
	$smarty->assign("dot_is_woeid", ((isset($dot['details']['yahoo:woeid']) && !empty($dot['details']['yahoo:woeid']) ) ? true : false) );
	$smarty->assign("dot_is_oam", ((isset($dot['details']['oam:mapid']) && !empty($dot['details']['oam:mapid']) ) ? true : false) );
	$smarty->assign("dot_is_walkingpapers", ((isset($dot['details']['walkingpapers:scanid']) && !empty($dot['details']['walkingpapers:scanid']) ) ? true : false) );
	$smarty->assign("dot_is_foursquare", ((isset($dot['details']['foursquare:venue']) && !empty($dot['details']['foursquare:venue']) ) ? true : false) );
	
	//	Setup for dot pagination
	//	Probably a better way to do pagination on single dot pages
	//	Possibly by including next/previous dot id's in return of dots_get_dot function
	if( isset($dot['sheet_id']) ){
		$sheet = sheets_get_sheet($dot['sheet_id']);
		if( isset($sheet) && !empty($sheet) ){
			$more = array(
				'per_page' => $GLOBALS['cfg']['import_max_records'],
			);
			
			//............................. CACHE DOTS ? .......................................//
			//..................................................................................//
			$cache_key = "dot_page_dots_{$dot_id}";
			$cache = cache_get($cache_key);
			if($cache['ok']){ 
				$dots = $cache['data'];
			}else{
				$dots = dots_get_dots_for_sheet($sheet, $GLOBALS['cfg']['user']['id'],$more);
			}
			//....................................................................................//
			//....................................................................................//
			
			if(isset($dots) && !empty($dots)){
				
				// set cache
				cache_set($cache_key, array(
					'ok' => 1,
					'data' => $dots,
				));
							
				$dots_total = count($dots) - 1;
				$dot_spot_in_array = -1;
				$ct = 0;
				foreach ($dots as $d){
					if($d['id'] == $dot_id){
						$dot_spot_in_array = $ct;
						break;
					}
					$ct++;
				}

				if($dot_spot_in_array > -1){
					$next_dot = (isset($dots[$dot_spot_in_array + 1]['id'])) ? $dots[$dot_spot_in_array + 1]['id'] : -1;
					$prev_dot = (isset($dots[$dot_spot_in_array - 1]['id'])) ? $dots[$dot_spot_in_array - 1]['id'] : -1; 
					$smarty->assign_by_ref("next_dot", $next_dot);
					$smarty->assign_by_ref("prev_dot", $prev_dot);
				}
			}
		}
	}
	//	end setup for dot pagination
	
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

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$smarty->assign("is_own", $is_own);

	$smarty->assign_by_ref("owner", $owner);
	$smarty->assign_by_ref("dot", $dot);
	
	if ($is_own){
		$smarty->assign("permissions_map", dots_permissions_map());
		$smarty->assign("geocoder_map", geo_geocode_service_map());
	}

	$smarty->display("page_dot.txt");
	exit;
?>
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
	
	//	quickly determine if dot is magical
	//	TODO: add checks for other magical types
	$smarty->assign("dot_is_flickr", ((isset($dot['details']['flickr:id']) && !empty($dot['details']['flickr:id']) ) ? true : false) );
	
	//	Setup for dot pagination
	//	Probably a better way to do pagination on single dot page
	//	Maybe by including next/previous dot id's in return of dots_get_dot function
	if( isset($dot['sheet_id']) ){
		$sheet = sheets_get_sheet($dot['sheet_id']);
		if( isset($sheet) && !empty($sheet) ){
			$more = array(
				'per_page' => $GLOBALS['cfg']['import_max_records'],
			);
			$dots = dots_get_dots_for_sheet($sheet, $GLOBALS['cfg']['user']['id'],$more);
			if(isset($dots) && !empty($dots)){			
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
					$next_dot = $dot_spot_in_array + 1;
					$prev_dot = $dot_spot_in_array - 1;
					$smarty->assign_by_ref("dots", $dots);
					$smarty->assign_by_ref("dot_spot_in_array", $dot_spot_in_array);
					$smarty->assign_by_ref("dots_total", $dots_total);
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
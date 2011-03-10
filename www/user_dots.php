<?php

	#
	# $Id$
	#

	#################################################################

	include("include/init.php");
	loadlib("geo_geocode");

	#################################################################

	$owner = users_ensure_valid_user_from_url();

	$more = array(
		'page' => get_int64('page'),
	);

	$dots = dots_get_dots_for_user($owner, $GLOBALS['cfg']['user']['id'], $more);

	$smarty->assign_by_ref('owner', $owner);
	$smarty->assign_by_ref('dots', $dots);
	
	// create a simplfied object for js
	$json_fields = array("id","sheet_id","created","details","geohash","is_interactive","latitude","longitude","user_id","perms","sheet_id");
	if($dots){
		$to_json = array();
		foreach ($dots as $dot) {
			$tmp = array();
			foreach($json_fields as $fi){
				if(isset($dot[$fi])){
					if($fi == "details"){
						$_details = array();
						foreach($dot[$fi] as $de){
							$_details[] = array(
								'label' => $de[0]['label'],
								'value' => $de[0]['value']
							);
						}
						$tmp[$fi] = $_details;
					}else{
						$tmp[$fi] = $dot[$fi];
					}
				}
				
			}

			$to_json[] = $tmp;
			
		}
		//if( isset($owner.username) )$ddd[] = array('owner'=>$owner.username);
		$smarty->assign("dots_simple", $to_json);
	}

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$smarty->assign('is_own', $is_own);

	if ($is_own){
		$smarty->assign("permissions_map", dots_permissions_map());
		$smarty->assign("geocoder_map", geo_geocode_service_map());
	}

	$smarty->assign("pagination_url", urls_dots_for_user($owner));

	$smarty->display('page_user_dots.txt');
	exit();

?>

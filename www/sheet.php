<?php

	#
	# $Id$
	#

	include("include/init.php");
	loadlib("geo_geocode");
	loadlib("formats");

	$owner = users_ensure_valid_user_from_url();

	$sheet_id = get_int64('sheet_id');

	if (! $sheet_id){
		error_404();
	}

	$more = array(
		'load_extent' => 1,
	);

	$sheet = sheets_get_sheet($sheet_id, $GLOBALS['cfg']['user']['id'], $more);

	if (! $sheet){
		error_404();
	}

	if ($sheet['deleted']){
		$GLOBALS['smarty']->display("page_sheet_deleted.txt");
		exit();
	}

	if ($sheet['user_id'] != $owner['id']){
		error_404();
	}

	if (! sheets_can_view_sheet($sheet, $GLOBALS['cfg']['user']['id'])){
		error_403();
	}

	#

	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;
	$smarty->assign("is_own", $is_own);

	$smarty->assign_by_ref("owner", $owner);
	$smarty->assign_by_ref("sheet", $sheet);

	# delete this sheet?

	if ($is_own){

		$crumb_key = 'delete-sheet';
		$smarty->assign("crumb_key", $crumb_key);

		if ((post_str('delete')) && (crumb_check($crumb_key))){

			if (post_str('confirm')){

				$rsp = sheets_delete_sheet($sheet);
				$smarty->assign('deleted', $rsp);
			}

			if ($rsp['ok']){

				$redir = urls_sheets_for_user($GLOBALS['cfg']['user']) . "?deleted=1";
				header("location: $redir");
				exit();
			}

			$smarty->display('page_sheet_delete.txt');
			exit();
		}
	}

	# Hey look! At least to start we are deliberately not doing
	# any pagination on the 'dots-for-a-sheet' page. We'll see
	# how long its actually sustainable but for now it keeps a
	# variety of (display) avenues open.
	# (20101025/straup)

	$more = array(
		'per_page' => $GLOBALS['cfg']['import_max_records'],
	);

	$sheet['dots'] = dots_get_dots_for_sheet($sheet, $GLOBALS['cfg']['user']['id'], $more);

	$to_index = array($sheet['dots'][0]);
	$dots_indexed = dots_indexed_on($to_index);

	$GLOBALS['smarty']->assign_by_ref("dots_indexed", $dots_indexed);

	# define the selected title field
	$title_field = NULL;
	#$sheet['dots'][0]['details']['title_internal'][0]['value'] = "{title} on {date}";

	if(isset($sheet['dots'][0]['details']['title_internal'])){
		preg_match_all("/[\{](.+?)[\}]/i", $sheet['dots'][0]['details']['title_internal'][0]['value'], $title_matches);

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

	// create a simplfied object for js
	$json_fields = array("id","created","details","geohash","is_interactive","latitude","longitude","user_id","perms","sheet_id");
	if($sheet['dots']){
		$to_json = array();
		foreach ($sheet['dots'] as $dot) {
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

				if(isset($dots_indexed)){
					foreach($dots_indexed as $ifield){
						if(isset($dot[$ifield])) $tmp[$ifield] = $dot[$ifield];
					}
				}

			}

			$to_json[] =$tmp;
		}

		//if( isset($owner.username) )$ddd[] = array('owner'=>$owner.username);
		$smarty->assign("dots_simple", $to_json);
	}

	$formats = array_values(formats_valid_export_map());
	$GLOBALS['smarty']->assign("export_formats", $formats);

	$formats_pretty_names = formats_pretty_names_map();
	$GLOBALS['smarty']->assign_by_ref("formats_pretty_names", $formats_pretty_names);

	$smarty->display("page_sheet.txt");
	exit;
?>

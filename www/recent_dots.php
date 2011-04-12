<?php

	#
	# $Id$
	#

	include("include/init.php");

	$recent_dots = dots_get_dots_recently_imported();
	$smarty->assign_by_ref('recent_dots', $recent_dots);
	
	
	// create a simplfied object for js
	$json_fields = array("id","created","details","geohash","is_interactive","latitude","longitude","user_id","perms","sheet_id");
	if($recent_dots){
		$to_json = array();
		foreach ($recent_dots as $dot) {
			$temp = array();
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
						$temp[$fi] = $_details;
					}else{
						$temp[$fi] = $dot[$fi];
					}
				}

			}

			$to_json[] = $temp;

		}
		//if( isset($owner.username) )$ddd[] = array('owner'=>$owner.username);
		$smarty->assign("dots_simple", $to_json);
	}

	$smarty->display("page_recent_dots.txt");
	exit();
?>
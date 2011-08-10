<?php

	#
	# Id$
	#

	include("include/init.php");
	
	$sheet_url= "";
	
	# if incoming owner id & sheet id
	if( $_GET['oid'] && $_GET['sid'] ){
	    $sheet = array(
	       'user_id' => (int)$_GET['oid'],
	       'id' => (int)$_GET['sid']
	    );
	    $sheet_url = urls_url_for_sheet( $sheet );
	}else{ # pass random URL to seed example
    	$recent_sheets = sheets_recently_created($GLOBALS['cfg']['user_id']);
        
        if($recent_sheets){
            $len = count($recent_sheets) - 1;
            if($len > 0){
                $rdm = rand(0,$len);
                $sheet_url = urls_url_for_sheet($recent_sheets['sheets'][$rdm]);
            }
        }
    }
	$chosen_theme = "default";	
	
	if($_GET['type']=="crime"){
	    $chosen_theme = "crime";
	}else if($_GET['type']=="photo"){
	    $chosen_theme = "photo";
	}else if($_GET['type']=="bucket"){
	    $chosen_theme = "bucket";
    }

    $GLOBALS['smarty']->assign_by_ref("recent_sheet_url", $sheet_url);
	$GLOBALS['smarty']->assign_by_ref("chosen_theme", $chosen_theme);
	$GLOBALS['smarty']->display('embed_themes/inc_themes_config.txt');
	exit();
?>
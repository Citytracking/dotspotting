<?php

	#
	# Id$
	#

	include("include/init.php");
	
	
	# pass random URL to seed example
	
	$recent_sheets = sheets_recently_created($GLOBALS['cfg']['user_id']);
    $url_from_random_sheet  = "";
    if($recent_sheets){
        $len = count($recent_sheets) - 1;
        if($len > 0){
            $rdm = rand(0,$len);
            $url_from_random_sheet = urls_url_for_sheet($recent_sheets['sheets'][$rdm]);
        }
    }
	
	$GLOBALS['smarty']->assign_by_ref("recent_sheet_url", $url_from_random_sheet);
	
	
	if($_GET['type']=="crime"){
        $GLOBALS['smarty']->display('embed_themes/crime/index.txt');
	}else if($_GET['type']=="default"){
	     $GLOBALS['smarty']->display('embed_themes/default/index.txt');
	}else{
	    $GLOBALS['smarty']->display('page_embed.txt');
    }
	exit();
?>
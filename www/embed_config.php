<?php

	#
	# Id$
	#

	include("include/init.php");
	
	$sheet_url= "";
	
	# pass random URL to seed example
	if( $_GET['oid'] && $_GET['sid'] ){
	    $sheet = array(
	       'user_id' => (int)$_GET['oid'],
	       'id' => (int)$_GET['sid']
	    );
	    $sheet_url = urls_url_for_sheet( $sheet );
	}else{
    	$recent_sheets = sheets_recently_created($GLOBALS['cfg']['user_id']);
        
        if($recent_sheets){
            $len = count($recent_sheets) - 1;
            if($len > 0){
                $rdm = rand(0,$len);
                $sheet_url = urls_url_for_sheet($recent_sheets['sheets'][$rdm]);
            }
        }
    }
	
	$GLOBALS['smarty']->assign_by_ref("recent_sheet_url", $sheet_url);
	
	
	if($_GET['type']=="crime"){
        $GLOBALS['smarty']->display('embed_themes/crime/index.txt');
	}else if($_GET['type']=="default"){
	     $GLOBALS['smarty']->display('embed_themes/default/index.txt');
	}else{
	    $GLOBALS['smarty']->display('page_embed.txt');
    }
	exit();
?>
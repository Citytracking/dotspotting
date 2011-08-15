<?php

	#
	# $Id$
	#

	include("include/init.php");
	
	// pagination...
	$more = array(
      "page" => get_int32("page")  
    );
    $pagination_url = "{$GLOBALS['cfg']['abs_root_url']}recent/sheets/";
    $GLOBALS['smarty']->assign("pagination_url",$pagination_url);
    
    // go get sheets
	$recent_sheets = sheets_recently_created($GLOBALS['cfg']['user_id'],$more);
	$GLOBALS['smarty']->assign_by_ref("recent_sheets", $recent_sheets['sheets']);
	
    // display sheets
	$smarty->display('page_recent_sheets.txt');
	exit();

?>
<?php

	#
	# Id$
	#

	include("include/init.php");
	if($_GET['type']=="crime"){
        $GLOBALS['smarty']->display('embed_themes/crime/index.txt');
	}else if($_GET['type']=="default"){
	     $GLOBALS['smarty']->display('embed_themes/default/index.txt');
	}else{
	    $GLOBALS['smarty']->display('page_embed.txt');
    }
	exit();
?>
<?php

	#
	# Id$
	#

	include("include/init.php");

	if($_GET['type']=="crime"){
        $GLOBALS['smarty']->display('embed_themes/crime/map.txt');
	}else if($_GET['type']=="default"){
	     $GLOBALS['smarty']->display('embed_themes/default/map.txt');
	}else if($_GET['type']=="photo"){
     	 $GLOBALS['smarty']->display('embed_themes/photo/map.txt');
	}else if($_GET['type']=="bucket"){
        $GLOBALS['smarty']->display('embed_themes/bucket/map.txt');
    }else if($_GET['type']=="bubbles"){
        $GLOBALS['smarty']->display('embed_themes/bubbles/map.txt');
	}else{
	    $GLOBALS['smarty']->display('page_embed.txt');
    }

	exit();
?>
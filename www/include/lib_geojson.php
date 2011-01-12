<?php

	#
	# $Id$
	#

	#################################################################

	function geojson_parse_fh($fh, $more=array()){

		$raw = fread($fh, filesize($more['file']['path']));
		$json = json_decode($raw, "as a hash");
	}

	#################################################################

	function geojson_export_dots(&$dots, $fh){

		$json = array();

		fwrite($fh, json_encode($json));
	}
	
	#################################################################
?>
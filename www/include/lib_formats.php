<?php

	#
	# $Id$
	#

	#################################################################

	function formats_valid_import_map($key_by_extension=0){

		$map = array(
			'text/csv' => 'csv',
			'application/x-javascript' => 'json',
			'application/vnd.google-earth.kml+xml' => 'kml',
			'application/rss+xml' => 'rss',
			'application/vnd.ms-excel' => 'xls',
		);

		# TODO: fix me so that this will work for mime-types
		# with multiple valid extensions (20101215/straup)

		if ($key_by_extension){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function formats_valid_export_map($key_by_extension=0){

		$map = array(
			'text/csv' => 'csv',
			'application/x-javascript' => 'json',
			'application/vnd.google-earth.kml+xml' => 'kml',
			'application/rss+xml' => 'rss',
			'application/vnd.ms-excel' => 'xls',
		);

		if ($key_by_extension){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################
?>
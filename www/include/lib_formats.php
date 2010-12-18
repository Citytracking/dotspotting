<?php

	#
	# $Id$
	#

	#################################################################

	function formats_valid_import_map($key_by_extension=0){

		$map = array(
			'text/csv' => 'csv',
			'application/rss+xml' => 'rss',
			'application/vnd.google-earth.kml+xml' => 'kml',
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
		);

		if ($key_by_extension){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################
?>
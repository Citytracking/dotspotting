<?php

	#
	# $Id$
	#

	#################################################################

	function formats_valid_import_map($key_by_extension=0){

		$map = array(
			'text/csv' => 'csv',
			'application/gpx+xml' => 'gpx',
			'application/x-javascript' => 'json',
			'application/vnd.google-earth.kml+xml' => 'kml',
			'application/rss+xml' => 'rss',
			'application/vnd.esri-shapefile' => 'shp',
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
			'application/gpx+xml' => 'gpx',
			'application/x-javascript' => 'json',
			'application/vnd.google-earth.kml+xml' => 'kml',
			'application/vnd.ms-powerpoint' => 'ppt',
			'application/rss+xml' => 'rss',
			'application/vnd.ms-excel' => 'xls',
		);

		# Ensure that we can actually generate PNG files
		# Also, we don't strictly speaking need GD to
		# generate PDF files except for the part where we
		# want to include PNG files so it's six of one...

		if (function_exists('imagecreatetruecolor')){
			$map['image/png'] = 'png';
			$map['application/pdf'] = 'pdf';
		}

		if ($key_by_extension){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	# This is called in the upload template to display a pretty list
	# of valid formats to upload.

	function formats_valid_import_list($sep=', '){

		$map = formats_valid_import_map('key by extension');

		$things_with_geo = array(
			'json',
			'rss',
		);

		$list = array();

		foreach (array_keys($map) as $format){

			$prefix = '';

			if (in_array($format, $things_with_geo)){
				$prefix = 'Geo';
			}

			$list[] = $prefix . strtoupper($format);
		}

		sort($list);

		return implode($sep, $list);
	}

	#################################################################
?>
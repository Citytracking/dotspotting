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
	
	/* 
	 * 	Just trying to map a description to each export type, so they can be used for link titles and such
	 *	TODO: eliminate this and roll into the regular one below 
	 *	the array_flip is the hangup
     *
	 *	NOTE: when fixed, need to change ref's in 'sheet.php' & 'search.php'
	*/
	function formats_valid_export_map_display(){
		$map = array(
			'text/csv' => array('label'=>'csv','desc'=>'export sheet in comma separated value file format'),
			'application/gpx+xml' => array('label'=>'gpx','desc'=>'export sheet in GPS file format'),
			'application/x-javascript' => array('label'=>'json','desc'=>'export sheet in JavaScript Object Notation format'),
			'application/vnd.google-earth.kml+xml' => array('label'=>'kml','desc'=>'export sheet in Keyhole Markup Language format'),
			'application/vnd.ms-powerpoint' => array('label'=>'ppt','desc'=>'export sheet as a Powerpoint'),
			'application/rss+xml' => array('label'=>'rss','desc'=>'export sheet in Really Simple Syndication format'),
			'application/vnd.ms-excel' => array('label'=>'xls','desc'=>'export sheet as a Microsoft Excel document'),
		);

		# Ensure that we can actually generate PNG files
		# Also, we don't strictly speaking need GD to
		# generate PDF files except for the part where we
		# want to include PNG files so it's six of one...

		if (function_exists('imagecreatetruecolor')){
			$map['image/png'] = array('label'=>'png','desc'=>'export map as a PNG image');
			$map['application/pdf'] = array('label'=>'pdf','desc'=>'export sheet in Portable Document Format');
		}

		return $map;
	}
	

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
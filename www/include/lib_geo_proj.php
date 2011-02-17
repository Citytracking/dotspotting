<?php

	#
	# $Id$
	#

	#################################################################

	# Hey look! Running code...

	function __sourcemap_proj_autoload($cls) {

		$parts = explode('_', strtolower($cls));

		if ($parts[0] != 'sourcemap'){
			return false;
		}

		$dirs = array_merge(array(
			DOTSPOTTING_INCLUDE_DIR . "pear",
		), $parts);

		$src = implode(DIRECTORY_SEPARATOR, $dirs) . '.php';

		if (! file_exists($src)){
		   return false;
		}

		include($src);
		return true;
	}

	spl_autoload_register('__sourcemap_proj_autoload');

	#################################################################

	function geo_proj_transform($pt, $from, $to){

		$src_pt = new Sourcemap_Proj_Point($pt['longitude'], $pt['latitude']);
		$new_pt = Sourcemap_Proj::transform($to, $from, $src_pt);

		return array(
			'latitude' => $new_pt->y,
			'longitude' => $new_pt->x,
		);
	}

	#################################################################
?>
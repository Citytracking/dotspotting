<?php

	#
	# $Id$
	#

	# THIS IS NOT FINISHED YET (2011011/straup)

	#################################################################

	function png_export_dots(&$dots, $fh){

		loadpear("modestmaps/ModestMaps");

		$template = 'http://tile.openstreetmap.org/{Z}/{X}/{Y}.png';
		$provider = new MMaps_Templated_Spherical_Mercator_Provider($template);

		$zoom = 14;

		$loc = new MMaps_Location(37.804969, -122.257662);
		$dims = new MMaps_Point(500, 500);

		$map = MMaps_mapByCenterZoom($provider, $loc, $zoom, $dims);
		$im = $map->draw();

		imagepng($im);
	}

	#################################################################
?>
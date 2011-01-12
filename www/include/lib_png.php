<?php

	#
	# $Id$
	#

	loadpear("modestmaps/ModestMaps");

	#################################################################

	function png_export_dots(&$dots, $fh){

		# See these? They are hard-coded. For now. Or at least
		# until it becomes necessary to do it otherwise...
		# (20110112/straup)

		$im_height = 768;
		$im_width = 1024;
		$dot_size = 20;

		#

		$swlat = null;
		$swlon = null;
		$nelat = null;
		$nelon = null;

		foreach ($dots as $dot){
			$swlat = (! isset($swlat)) ? $dot['latitude'] : min($swlat, $dot['latitude']);
			$swlon = (! isset($swlon)) ? $dot['longitude'] : min($swlon, $dot['longitude']);
			$nelat = (! isset($nelat)) ? $dot['latitude'] : max($nelat, $dot['latitude']);
			$nelon = (! isset($nelon)) ? $dot['longitude'] : max($nelon, $dot['longitude']);
		}

		$template = $GLOBALS['cfg']['maptiles_template_url'];

		# This really needs to happen in modestmaps/Providers.php
		# but we'll do it here for now (20110112/straup)

		$hosts = $GLOBALS['cfg']['maptiles_template_hosts'];
		shuffle($hosts);
		$template = str_replace("{S}", $hosts[0], $template);

		$provider = new MMaps_Templated_Spherical_Mercator_Provider($template);

		$sw = new MMaps_Location($swlat, $swlon);
		$ne = new MMaps_Location($nelat, $nelon);

		$dims = new MMaps_Point($im_width, $im_height);

		$map = MMaps_mapByExtent($provider, $sw, $ne, $dims);
		$im = $map->draw();

		$points = array();

		$fill = imagecolorallocatealpha($im, 0, 17, 45, 96);
		$stroke = imagecolorallocate($im, 153, 204, 0);

		foreach ($dots as $dot){

			$loc = new MMaps_Location($dot['latitude'], $dot['longitude']);
			$pt = $map->locationPoint($loc);

			imagefilledellipse($im, $pt->x, $pt->y, $dot_size, $dot_size, $fill);

			imagesetthickness($im, 3);
			imagearc($im, $pt->x, $pt->y, $dot_size, $dot_size, 0, 359.9, $stroke);
		}

		imagepng($im);
		imagedestroy($im);
	}

	#################################################################
?>
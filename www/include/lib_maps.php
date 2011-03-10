<?php

	#
	# $Id$
	#

	loadpear("modestmaps/ModestMaps");

	#################################################################

	function maps_png_for_dots(&$dots, $more=array()){

		list ($map, $gd_img) = maps_image_for_dots($dots, $more);

		return maps_gd_to_png($gd_img);
	}

	#################################################################

	# this returns a gd image handle for a bunch of dots

	function maps_image_for_dots(&$dots, $more=array()){

		$defaults = array(
			'width' => 1024,
			'height' => 768,
			'draw_dots' => 1,
			'dot_size' => 20,

			# possibilities:
			# dot_size_callback (to determine the size based on some dot value)

		);

		$more = array_merge($defaults, $more);

		#

		if (isset($more['template'])){
			$template = $more['template'];
		}

		else {

			$template = $GLOBALS['cfg']['maptiles_template_url'];

			# This really needs to happen in modestmaps/Providers.php
			# but we'll do it here for now (20110112/straup)

			$hosts = $GLOBALS['cfg']['maptiles_template_hosts'];
			shuffle($hosts);

			$template = str_replace("{S}", $hosts[0], $template);
		}

		# null island

		if (count($dots) == 0){

			$template = 'http://acetate.geoiq.com/tiles/acetate-hillshading/{Z}/{X}/{Y}.png';
			$provider = new MMaps_Templated_Spherical_Mercator_Provider($template);

			$centroid = new MMaps_Location(0, 0);
			$dims = new MMaps_Point($more['width'], $more['height']);

			$map = MMaps_mapByCenterZoom($provider, $centroid, 18, $dims);
			$img = $map->draw();
		}

		# center zoom

		else if (count($dots) == 1){

			$dot = $dots[0];

			$provider = new MMaps_Templated_Spherical_Mercator_Provider($template);

			$centroid = new MMaps_Location($dot['latitude'], $dot['longitude']);
			$dims = new MMaps_Point($more['width'], $more['height']);

			$map = MMaps_mapByCenterZoom($provider, $centroid, 17, $dims);
			$img = $map->draw();
		}

		# draw by extent

		else { 

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

			$provider = new MMaps_Templated_Spherical_Mercator_Provider($template);

			$sw = new MMaps_Location($swlat, $swlon);
			$ne = new MMaps_Location($nelat, $nelon);

			$dims = new MMaps_Point($more['width'], $more['height']);

			if (($swlat == $nelat) && ($swlon == $nelon)){
				$map = MMaps_mapByCenterZoom($provider, $sw, 17, $dims);
			}

			else {
				$map = MMaps_mapByExtent($provider, $sw, $ne, $dims);
			}

			$img = $map->draw();
		}

		#
		
		if ($more['draw_dots']){
			
			if(isset($GLOBALS['cfg']['dot_color_scheme'])){
				$red_fill_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['fill'][0]) ) ? $GLOBALS['cfg']['dot_color_scheme']['fill'][0] : 11;
				$green_fill_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['fill'][1]) ) ? $GLOBALS['cfg']['dot_color_scheme']['fill'][1] : 189;
				$blue_fill_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['fill'][2]) ) ? $GLOBALS['cfg']['dot_color_scheme']['fill'][2] : 255;
				
				# alpha value: convert alpha scale from 0,1 to 127,0
				$alpha_fill_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['fill'][3]) ) ? floor( abs(($GLOBALS['cfg']['dot_color_scheme']['fill'][3] * 127) - 127) ) : 96;
				
				$red_stroke_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['stroke'][0]) ) ? $GLOBALS['cfg']['dot_color_scheme']['stroke'][0] : 255;
				$green_stroke_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['stroke'][1]) ) ? $GLOBALS['cfg']['dot_color_scheme']['stroke'][1] : 255;
				$blue_stroke_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['stroke'][2]) ) ? $GLOBALS['cfg']['dot_color_scheme']['stroke'][2] : 255;
				
				$fill = imagecolorallocatealpha($img, $red_fill_val, $green_fill_val, $blue_fill_val, $alpha_fill_val);
				$stroke = imagecolorallocate($img, $red_stroke_val, $green_stroke_val, $blue_stroke_val);
			}else{
				$fill = imagecolorallocatealpha($img, 11, 189, 255, 96);
				$stroke = imagecolorallocate($img, 255, 255, 255);
			}
			

			foreach ($dots as $dot){

				$loc = new MMaps_Location($dot['latitude'], $dot['longitude']);
				$pt = $map->locationPoint($loc);

				imagefilledellipse($img, $pt->x, $pt->y, $more['dot_size'], $more['dot_size'], $fill);

				imagesetthickness($img, 3);
				imagearc($img, $pt->x, $pt->y, $more['dot_size'], $more['dot_size'], 0, 359.9, $stroke);
			}
		}

		# return (x, y) points also?

		return array($map, $img);
	}

	#################################################################

	function maps_gd_to_png($gd){

		$tmp = tempnam(sys_get_temp_dir(), "pdf") . ".png";

		imagepng($gd, $tmp);
		imagedestroy($gd);

		return $tmp;
	}

	#################################################################
?>
<?php

	#
	# $Id$
	#

	loadpear("modestmaps/ModestMaps");
	loadlib("wscompose");

	#################################################################

	function maps_png_for_dots_multi(&$dots, $more=array()){

		$prefix = ($more['img_prefix']) ? $more['img_prefix'] : 'nyan';

		$maps = maps_images_for_dots($dots, $more);
		$pngs = array();

		foreach ($maps as $data){
			list($ignore, $gd_img) = $data;
			$pngs[] = maps_gd_to_png($gd_img, $prefix);
		}

		return $pngs;
	}

	#################################################################

	function maps_png_for_dots(&$dots, $more=array()){

		list ($map, $gd_img) = maps_image_for_dots($dots, $more);

		$prefix = ($more['img_prefix']) ? $more['img_prefix'] : 'nyan';
		return maps_gd_to_png($gd_img, $prefix);
	}

	#################################################################

	# this returns GD image handle(s) for a bunch of dots

	# this is the old code that's called throughout the site

	function maps_image_for_dots(&$dots, $more=array()){

		$maps = array($dots);

		$rsp = maps_images_for_dots($maps, $more);
		return $rsp[0];
	}

	# this is the new code that allows us to do multigets
	# (assuming that wscompose is present)

	function maps_images_for_dots(&$bag_of_dots, $more=array()){

		$defaults = array(
			'width' => 1024,
			'height' => 768,
			'draw_dots' => 1,
			'dot_size' => 25,
		);

		$template = $GLOBALS['cfg']['maptiles_template_url'];

		# This really needs to happen in modestmaps/Providers.php
		# but we'll do it here for now (20110112/straup)

		$hosts = $GLOBALS['cfg']['maptiles_template_hosts'];
		shuffle($hosts);

		$defaults['template'] = str_replace("{S}", $hosts[0], $template);

		$more = array_merge($defaults, $more);

		# If $GLOBALS['cfg']['enable_feature_wscompose'] is true then the drawing of
		# actual raster maps will be delegated to a wscompose server running on the
		# host and port of your choosing. The advantage of doing it this way is that
		# the PHP port of ModestMaps fetches tiles (for a map image) one at a time
		# while the Python version fetches them in a thread. If you're going to use
		# the wscompose stuff you should probably run the WSGIComposeServer under a
		# not-single-threaded server like gunicorn. Note that we still use ModestMaps.php
		# to draw dots. For now. (20110726/straup)

		# See the $queue array, below? We're going to iterate over all the dots and for
		# each one create an array of ModestMap.php objects and wscompose argument hashes.
		# The first foreach loop is used to figure out the bounds of the map and zoom
		# levels. The second loop is what will actually draw the maps. If wscompose is
		# in the house then the code will see whether it should try to fetch all the maps
		# using a multiget, using wscompose sequentially or ModestMaps.php sequentially.
		# The third and final loop will iterate over $queue and $bag_of_dots in parallel
		# and use the latter to draw dots on the corresponding index (image) in the former.
		# (20110727/straup)

		$queue = array();

		foreach ($bag_of_dots as $dots){

			# null island

			if (count($dots) == 0){

				$template = 'http://acetate.geoiq.com/tiles/acetate-hillshading/{Z}/{X}/{Y}.png';
				$provider = new MMaps_Templated_Spherical_Mercator_Provider($template);

				$centroid = new MMaps_Location(0, 0);
				$dims = new MMaps_Point($more['width'], $more['height']);

				$map = MMaps_mapByCenterZoom($provider, $centroid, 18, $dims);

				$wscompose_args = array(
					'provider' => $template,
					'method' => 'center',
					'latitude' => 0,
					'longitude' => 0,
					'zoom' => 18,
					'height' => $more['height'],
					'width' => $more['width'],
				);

				$queue[] = array($map, $wscompose_args);
			}

			# center zoom

			else if (count($dots) == 1){

				$dot = $dots[0];

				$provider = new MMaps_Templated_Spherical_Mercator_Provider($template);

				$centroid = new MMaps_Location($dot['latitude'], $dot['longitude']);
				$dims = new MMaps_Point($more['width'], $more['height']);

				$map = MMaps_mapByCenterZoom($provider, $centroid, 17, $dims);

				$wscompose_args = array(
					'provider' => $template,
					'method' => 'center',
					'latitude' => $dot['latitude'],
					'longitude' => $dot['longitude'],
					'zoom' => 17,
					'height' => $more['height'],
					'width' => $more['width'],
				);

				$queue[] = array($map, $wscompose_args);
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

				$wscompose_args = array(
					'provider' => $template,
					'height' => $more['height'],
					'width' => $more['width'],
				);

				if (($swlat == $nelat) && ($swlon == $nelon)){

					$map = MMaps_mapByCenterZoom($provider, $sw, 17, $dims);

					$wscompose_args['method'] = 'center';
					$wscompose_args['latitude'] = $swlat;
					$wscompose_args['longitude'] = $swlon;
					$wscompose_args['zoom'] = 17;
				}

				else {
					$map = MMaps_mapByExtent($provider, $sw, $ne, $dims);

					$wscompose_args['method'] = 'extent';
					$wscompose_args['bbox'] = implode(",", array($swlat, $swlon, $nelat, $nelon));
				}

				$queue[] = array($map, $wscompose_args);
			}
		}

		# now we actually draw the images

		$count_queue = count($queue);

		# use wscompose and plow through all the images using a series of
		# batched multiget request (the multiget stuff happens in lib_http)

		if (($GLOBALS['cfg']['enable_feature_wscompose']) && ($GLOBALS['cfg']['wscompose_enable_multigets'])){

			$wscompose_args = array();

			foreach ($queue as $stuff){
				list($mm, $ws_args) = $stuff;
				$wscompose_args[] = $ws_args;
			}

			$rsp = array();
			$batch_size = 25;

			while (count($wscompose_args)){

				$slice = array_slice($wscompose_args, 0, $batch_size);
				$wscompose_args = array_slice($wscompose_args, $batch_size);

				$_rsp = wscompose_get_many($slice);
				$rsp = array_merge($rsp, $_rsp);
			}

			# merge the results back in to $queue

			for ($i=0; $i < $count_queue; $i++){

				list($mm, $ignore) = $queue[$i];
				$_rsp = $rsp[$i];
				$img = ($_rsp['ok']) ? $_rsp['image'] : null;
				$queue[$i] = array($mm, $img);
			}
		}

		# use wscompose, generate each image one at a time

		else if ($GLOBALS['cfg']['enable_feature_wscompose']){

			for ($i=0; $i < $count_queue; $i++){
				list($mm, $ws_args) = $queue[$i];
				$rsp = wscompose_get($ws_args);
				$img = ($rsp['ok']) ? $rsp['image'] : null;
				$queue[$i] = array($mm, $img);
			}
		}

		# fallback on ModestMaps.php

		else {

			for ($i=0; $i < $count_queue; $i++){
				list($mm, $ignore) = $queue[$i];
				$img = $mm->draw();
				$queue[$i] = array($mm, $img);
			}
		}

		# Not dots! Just return here and save ourselves the
		# very special Hell of another if/else block...

		if (! $more['draw_dots']){
			return $queue;
		}

		# Carry on and draw the dots using ModestMaps.php - at some point this
		# may be functionality that is also delegated to wscompose/pinwin but
		# for now it is not. (20110726/straup)

		$rsp = array();

		$fill = imagecolorallocatealpha($img, 11, 189, 255, 96);
		$stroke = imagecolorallocate($img, 255, 255, 255);

		if (isset($GLOBALS['cfg']['dot_color_scheme'])){
			$red_fill_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['fill'][0]) ) ? $GLOBALS['cfg']['dot_color_scheme']['fill'][0] : 11;
			$green_fill_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['fill'][1]) ) ? $GLOBALS['cfg']['dot_color_scheme']['fill'][1] : 189;
			$blue_fill_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['fill'][2]) ) ? $GLOBALS['cfg']['dot_color_scheme']['fill'][2] : 255;

			# alpha value: convert alpha scale from 0,1 to 127,0
			$alpha_fill_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['fill'][3]) ) ? floor( abs(($GLOBALS['cfg']['dot_color_scheme']['fill'][3] * 127) - 127) ) : 96;

			$alpha_fill_val = 255;

			$red_stroke_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['stroke'][0]) ) ? $GLOBALS['cfg']['dot_color_scheme']['stroke'][0] : 255;
			$green_stroke_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['stroke'][1]) ) ? $GLOBALS['cfg']['dot_color_scheme']['stroke'][1] : 255;
			$blue_stroke_val = ( isset($GLOBALS['cfg']['dot_color_scheme']['stroke'][2]) ) ? $GLOBALS['cfg']['dot_color_scheme']['stroke'][2] : 255;

			$fill = imagecolorallocatealpha($img, $red_fill_val, $green_fill_val, $blue_fill_val, $alpha_fill_val);
			$stroke = imagecolorallocate($img, $red_stroke_val, $green_stroke_val, $blue_stroke_val);
		}

		for ($i = 0; $i < $count_queue; $i++){

			list($mm, $img) = $queue[$i];
			$_dots = $bag_of_dots[$i];

			# because GD images get passed around as 'null' values...

			if ($img){

				foreach ($_dots as $dot){
					$loc = new MMaps_Location($dot['latitude'], $dot['longitude']);
					$pt = $mm->locationPoint($loc);
					imagefilledellipse($img, $pt->x, $pt->y, $more['dot_size'], $more['dot_size'], $fill);
					imagesetthickness($img, 3);
					imagearc($img, $pt->x, $pt->y, $more['dot_size'], $more['dot_size'], 0, 359.9, $stroke);
				}
			}

			$rsp[] = array($mm, $img);
		}

		return $rsp;
	}

	#################################################################

	function maps_gd_to_png($gd, $prefix='nyan'){

		if (! $gd){
			return null;
		}

		$tmp = tempnam(sys_get_temp_dir(), "export-{$prefix}-") . ".png";

		imagepng($gd, $tmp);
		imagedestroy($gd);

		return $tmp;
	}

	#################################################################
?>

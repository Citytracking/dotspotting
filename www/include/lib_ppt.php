<?php

	#
	# $Id$
	#

	# THIS IS SO NOT DONE YET.
	# (20110119/straup)

	loadpear("modestmaps/ModestMaps");

	loadpear("PHPPowerPoint");
	loadpear("PHPPowerPoint/IOFactory");

	#################################################################

	function ppt_export_dots(&$dots, &$more){

		$maps = array();

		$w = 1024;
		$h = 768;

		$ppt = new PHPPowerPoint();
		$ppt->getProperties()->setTitle("FIX ME");
		$ppt->getProperties()->setCreator("Dotspotting");

		# set title here
		# $slide = $ppt->getActiveSlide();

		$ppt->removeSlideByIndex(0);

		$dot_per_slide = 0;

		if (0){

			$map_img = _ppt_export_dots_map($dots, $w, $h);
			$maps[] = $map_img;
		}

		else {

			foreach ($dots as $dot){
				$map_img = _ppt_export_center_map($dot, $h, $h);
				$maps[] = $map_img;
			}

			$dot_per_slide = 1;
		}

		# now draw all the maps...

		foreach ($maps as $map_img){

			$slide = $ppt->createSlide();

			$shape = $slide->createDrawingShape();
			$shape->setName('map');
			$shape->setDescription('');
			$shape->setPath($map_img);
			$shape->setWidth($w);
			$shape->setHeight($h);
			$shape->setOffsetX(0);
			$shape->setOffsetY(0);

			if ($dot_per_slide){

				$text = $slide->createRichTextShape();
				$text->setHeight($h);
				$text->setWidth($w - $h);
				$text->setOffsetX($h);
				$text->setOffsetY(0);

				$align = $text->getAlignment();
				$align->setHorizontal( PHPPowerPoint_Style_Alignment::HORIZONTAL_LEFT );

				$body = $text->createTextRun('hello world foo bar');
				$body->getFont()->setSize(18);
			}
		}

		#

		$tmp = tempnam(sys_get_temp_dir(), "ppt") . ".ppt";

		$writer = PHPPowerPoint_IOFactory::createWriter($ppt, 'PowerPoint2007');
		$writer->save($tmp);

		$fh = fopen($tmp, 'r');

		fwrite($more['fh'], fread($fh, filesize($tmp)));

		fclose($fh);

		#

		unlink($tmp);

		foreach ($maps as $path){
			unlink($path);
		}
	}

	#################################################################

	# See this: It is basically a clone of what's happening in lib_png.
	# Soon it will be time to reconcile the two. But not yet.
	# (20110113/straup)

	function _ppt_export_dots_map(&$dots, $w, $h){

		$dot_size = 20;

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

		$hosts = $GLOBALS['cfg']['maptiles_template_hosts'];
		shuffle($hosts);
		$template = str_replace("{S}", $hosts[0], $template);

		$provider = new MMaps_Templated_Spherical_Mercator_Provider($template);

		$sw = new MMaps_Location($swlat, $swlon);
		$ne = new MMaps_Location($nelat, $nelon);

		$dims = new MMaps_Point($w, $h);

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

		$tmp = tempnam(sys_get_temp_dir(), "pdf") . ".png";

		imagepng($im, $tmp);
		imagedestroy($im);

		return $tmp;
	}

	#################################################################

	function _ppt_export_center_map(&$dot, $w, $h){

		$dot_size = 20;

		$template = $GLOBALS['cfg']['maptiles_template_url'];

		$hosts = $GLOBALS['cfg']['maptiles_template_hosts'];
		shuffle($hosts);
		$template = str_replace("{S}", $hosts[0], $template);

		$provider = new MMaps_Templated_Spherical_Mercator_Provider($template);

		$centroid = new MMaps_Location($dot['latitude'], $dot['longitude']);
		$dims = new MMaps_Point($w, $h);

		$map = MMaps_mapByCenterZoom($provider, $centroid, 30, $dims);
		$im = $map->draw();

		$points = array();

		# $fill = imagecolorallocatealpha($im, 0, 17, 45, 96);
		# $stroke = imagecolorallocate($im, 153, 204, 0);

		$fill = imagecolorallocatealpha($im, 153, 204, 0, 96);
		$stroke = imagecolorallocate($im, 0, 17, 45);

		$loc = new MMaps_Location($dot['latitude'], $dot['longitude']);
		$pt = $map->locationPoint($loc);

		imagefilledellipse($im, $pt->x, $pt->y, $dot_size, $dot_size, $fill);

		imagesetthickness($im, 3);
		imagearc($im, $pt->x, $pt->y, $dot_size, $dot_size, 0, 359.9, $stroke);

		$tmp = tempnam(sys_get_temp_dir(), "pdf") . ".png";

		imagepng($im, $tmp);
		imagedestroy($im);

		return $tmp;
	}

	#################################################################
?>
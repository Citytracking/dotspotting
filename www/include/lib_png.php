<?php

	#
	# $Id$
	#

	loadlib("maps");

	#################################################################

	function png_export_dots(&$dots, &$more){

		$map_more = array(
			'width' => 1024, 
			'height' => 768,
		);

		list($map, $img) = maps_image_for_dots($dots, $map_more);

		imagepng($img, $more['path']);
		imagedestroy($img);

		return $more['path'];
	}

	#################################################################
?>
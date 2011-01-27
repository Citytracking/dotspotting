<?php

	#
	# $Id$
	#

	loadlib("maps");

	#################################################################

	function png_export_dots(&$dots, &$more){

		$more = array(
			'width' => 1024, 
			'height' => 768,
		);

		list($map, $img) = maps_image_for_dots($dots, $more);

		imagepng($img);
		imagedestroy($img);
	}

	#################################################################
?>
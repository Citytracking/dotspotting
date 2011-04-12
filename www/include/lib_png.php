<?php

	#
	# $Id$
	#

	loadlib("maps");

	#################################################################

	function png_export_dots(&$dots, $more=array()){

		$defaults = array(
			'width' => 1024, 
			'height' => 768,
		);

		$more = array_merge($defaults, $more);

		list($map, $img) = maps_image_for_dots($dots, $more);

		if (! $img){
			return null;
		}

		error_log("[WTF] img {$img} / {$more['path']}");
		imagepng($img, $more['path']);
		imagedestroy($img);

		return $more['path'];
	}

	#################################################################
?>
<?php

	#
	# $Id$
	#

	#################################################################
	
	function kmeans_cluster(&$points, $k=2){

		$clusters = array();

		# Hey look! This is *not actually doing any clustering*
		# That's because I am just going to write my own version
		# because all the one's I've found are annoying. This is
		# just stub code to work through the interface with other
		# code for now. (20110120/straup)

		$offset = 0;
		$length = floor(count($points) / $k);

		for ($i =0; $i < $k; $i++){

			$clusters[] = array_slice($points, $offset, $length);
			$offset += $length;
		}

		return $clusters;
	}

	#################################################################
?>
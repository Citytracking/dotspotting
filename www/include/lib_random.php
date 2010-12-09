<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of flamework.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/flamework (20101208/straup)

	#################################################################

	function random_string($length=52){

		$parts = array();

		foreach (range(1, $length) as $i){
         		$randnum = mt_rand(0,61);

         		if ($randnum < 10){
            			$parts[] = chr($randnum+48);
         		}

			else if ($randnum < 36){
            			$parts[] = chr($randnum+55);
         		}

			else {
		               $parts[] = chr($randnum+61);
         		} 
		}

		shuffle($parts);

		return implode("", $parts);
	}

?>
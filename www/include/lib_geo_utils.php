<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of flamework.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/flamework

	#################################################################

	function geo_utils_prepare_coordinate($coord, $collapse=1){

		$coord = geo_utils_trim_coordinate($coord);

		if ($collapse){
			$coord = geo_utils_collapse_coordinate($coord);
		}

		return $coord;
	}

	#################################################################

	function geo_utils_expand_coordinate($coord, $multiplier=1000000){

		return $coord / $multiplier;
	}

	#################################################################

	function geo_utils_collapse_coordinate($coord, $multiplier=1000000){

		return $coord * $multiplier;
	}

	#################################################################

	function geo_utils_trim_coordinate($coord, $offset=6){

		$fmt = "%0{$offset}f";

		return sprintf($fmt, $coord);
	}

	#################################################################

	function geo_utils_is_valid_latitude($lat){

		if (! is_numeric($lat)){
			return 0;
		}

		$lat = floatval($lat);

		if (($lat < -90.) || ($lat > 90.)){
			return 0;
		}

		return 1;
	}

	#################################################################

	function geo_utils_is_valid_longitude($lon){

		if (! is_numeric($lon)){
			return 0;
		}

		$lon = floatval($lon);

		if (($lon < -180.) || ($lont > 180.)){
			return 0;
		}

		return 1;
	}

	#################################################################

	# http://stackoverflow.com/questions/1125144/how-do-i-find-the-lat-long-that-is-x-km-north-of-a-given-lat-long

	function geo_utils_point_for_distance_and_bearing($lat1, $lon1, $dist, $bearing=0){

		$lat1 = deg2rad($lat1);
		$lon1 = deg2rad($lon1);

		$dist_r = $dist / 6371;  // convert dist to angular distance in radians
		$bearing_r = deg2rad($bearing);

		$lat2 = asin(sin($lat1) * cos($dist_r) + cos($lat1) * sin($dist_r) * cos($bearing_r) );
		$lon2 = $lon1 + atan2((sin($bearing_r) * sin($dist_r) * cos($lat1) ), (cos($dist_r) - sin($lat1) * sin($lat2)));

		$lat2 = rad2deg($lat2);
		$lon2 = rad2deg($lon2);

		return array($lat2, $lon2);
	}

	#################################################################

	function geo_utils_nearby_bbox($lat, $lon, $offset=1.0){
		$sw = geo_utils_point_for_distance_and_bearing($lat, $lon, $offset, 225);
		$ne = geo_utils_point_for_distance_and_bearing($lat, $lon, $offset, 45);
		return array_merge($sw, $ne);
	}

	#################################################################
?>

<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of lib_enplacify.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/php-lib-enplacify
	# (20101213/straup)

	#################################################################

	function enplacify_dopplr_uri($uri){

		$parts = enplacify_dopplr_uri_to_id($uri);

		if (! $parts){

			return array(
				'ok' => 0,
				'error' => 'failed to recognize place id',
			);
		}

		list($place_type, $place_id) = $parts;

		$rsp = enplacify_dopplr_get_place($place_type, $place_id);

		if (! $rsp['ok']){
			return $rsp;
		}

		$place = $rsp['place'];

		$place['derived_from'] = 'dopplr';
		$place['derived_from_id'] = "{$place_type}:{$place_id}";

		return array(
			'ok' => 1,
			'place' => $place,
		);
	}

	######################################################

	function enplacify_dopplr_uri_to_id($uri){

		$place_type = null;
		$place_id = null;

		$uris = $GLOBALS['cfg']['enplacify']['dopplr']['uris'];

		foreach ($uris as $pattern){

			if (preg_match($pattern, $uri, $m)){
				$place_type = $m[1];
				$place_id = $m[2];
				break;
			}
		}

		if ($place_type && $place_id){
			return array($place_type, $place_id);
		}

		return null;
	}

	######################################################

	function enplacify_dopplr_get_place($place_type, $place_id){

		$cache_key = "enplacify_dopplr_{$place_type}_{$place_id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$url = "http://dplr.it/" . urlencode($place_type) . "/" . urlencode($place_id);

		$headers = array();
		$more = array('follow_redirects' => 1);

		$rsp = http_get($url, $headers, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		# https://github.com/mncaudill/flickr-machinetag-geo/blob/master/src/parsers/dopplr.php

		if (preg_match('#pointLat:(-?\d.*),#U', $rsp['body'], $m)){
			$latitude = floatval($m[1]);
		}

		if (preg_match('#pointLng:(-?\d.*),#U', $rsp['body'], $m)){
			$longitude = floatval($m[1]);
		}

		if ((! $latitude) || (! $longitude)){

			return array(
				'ok' => 0,
				'error' => 'failed to locate lat,lon data'
			);
		}

		if (preg_match('#<title>(.*)\|#U', $rsp['body'], $m)){
			$name = trim($m[1]);
		}

		$place = array(
			'latitude' => $latitude,
			'longitude' => $longitude,
			'url' => $url,
			'name' => $name,
		);

		$rsp = array(
			'ok' => 1,
			'place' => $place,
		);

		cache_set($cache_key, $rsp);
		return $rsp;
	}

	######################################################

?>

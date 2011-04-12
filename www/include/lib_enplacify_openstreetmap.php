<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of lib_enplacify.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/php-lib-enplacify
	# (20101213/straup)

	######################################################

	function enplacify_openstreetmap_uri($uri){

		$parts = enplacify_openstreetmap_uri_to_id($uri);

		if (! $parts){

			return array(
				'ok' => 0,
				'error' => 'failed to recognize place id',
			);
		}

		list($place_type, $place_id) = $parts;

		$osm_rsp = enplacify_openstreetmap_get_place($place_type, $place_id);

		if (! $osm_rsp['ok']){
			return $rsp;
		}

		$tags = $osm_rsp[$place_type];

		$place = array(
			'latitude' => $tags['latitude'],
			'longitude' => $tags['longitude'],
		);

		# http://wiki.openstreetmap.org/wiki/Key:addr

		$map = array(
			'name' => 'name',
			'addr:street' => 'address',
			'addr:city' => 'city',
			'phone' => 'phone',
		);

		foreach ($map as $theirs => $ours){

			if (! isset($tags[$theirs])){
				continue;
			}

			$place[ $ours ] = $tags[ $theirs ];
		}

		if ($tags['addr:housenumber'] && $place['address']){
			$place['address'] = "{$tags['addr:housenumber']} {$place['address']}";
		}

		$place['derived_from'] = 'openstreetmap';
		$place['derived_from_id'] = "{$place_type}:{$place_id}";

		$rsp = array(
			'ok' => 1,
			'place' => $place,
			'tags' => $tags,
		);

		var_dump($rsp);
		return $rsp;
	}

	######################################################

	function enplacify_openstreetmap_uri_to_id($uri){

		$place_type = null;
		$place_id = null;

		$uris = $GLOBALS['cfg']['enplacify']['openstreetmap']['uris'];

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

	function enplacify_openstreetmap_get_place($place_type, $place_id){

		$cache_key = "enplacify_openstreetmap_{$place_type}_{$place_id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$url = "www.openstreetmap.org/api/0.6/" . urlencode($place_type) . "/" . urlencode($place_id);

		$rsp = http_get($url);

		if (! $rsp['ok']){
			return $rsp;
		}

		$xml = new DOMDocument();
		$xml->preserveWhiteSpace = false;
		$ok = $xml->loadXML($rsp['body']);

		if (! $ok){

			return array(
				'ok' => 0,
				'error' => 'XML parse error'
			);
		}

		$ima = $xml->documentElement->firstChild;

		$lat = $ima->getAttribute('lat');
		$lon = $ima->getAttribute('lon');

		$tags = array();

		foreach ($ima->childNodes as $tag){
			$key = $tag->getAttribute('k');
			$value = $tag->getAttribute('v');
			$tags[ $key ] = $value;
		}

		$tags['latitude'] = $lat;
		$tags['longitude'] = $lon;

		$rsp = array(
			'ok' => 1,
		);

		$rsp[ $place_type ] = $tags;

		cache_set($cache_key, $rsp);
		return $rsp;
	}

	######################################################

?>
<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of lib_enplacify.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/php-lib-enplacify
	# (20101213/straup)

	######################################################

	loadlib("vcard");
	loadlib("geo_geocode");

	######################################################

	function enplacify_foodspotting_uri($uri){

		$place_id = enplacify_foodspotting_uri_to_id($uri);

		if (! $place_id){
			return array(
				'ok' => 0,
				'error' => 'failed to recognize places id',
				'url' => $url,
			);
		}

		$rsp = enplacify_foodspotting_get_place($place_id);

		if (! $rsp['ok']){
			return $rsp;
		}

		$place = array(
			'name' => $rsp['place']['fn org'],
			'derived_from' => 'foodspotting',
			'derived_from_id' => $rsp['place']['id'],
		);

		$others = array(
			'latitude' => 'latitude',
			'longitude' => 'longitude',
			'street-address' => 'address',
			'tel' => 'phone',
		);

		foreach ($others as $theirs => $ours){

			if (isset($rsp['place'][$theirs])){
				$place[$ours] = $rsp['place'][$theirs];
			}
		}

		return array(
			'ok' => 1,
			'place' => $place,
		);
	}

	######################################################

	function enplacify_foodspotting_uri_to_id($uri){

		return enplacify_service_uri_to_id('foodspotting', $uri);
	}

	######################################################

	function enplacify_foodspotting_get_place($place_id){

		$cache_key = "enplacify_foodspotting_place_{$place_id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$url = "http://www.foodspotting.com/places/" . urlencode($place_id);
		$http_rsp = http_get($url);

		if (! $http_rsp['ok']){
			return $http_rsp;
		}

		$rsp = vcard_parse_html($http_rsp['body']);

		if ($rsp['ok']){

			$place = $rsp['vcard'];
			$place['id'] = $place_id;

			# vcard has no specifics for latlon so just assume this is false and look for:
			# <input id="place_latitude" name="place[latitude]" type="hidden" value="35.6633801" />
			# <input id="place_longitude" name="place[longitude]" type="hidden" value="139.71029

			$has_latlon = 0;

			libxml_use_internal_errors(true);
			$doc = new DOMDocument();

			$html = mb_convert_encoding($http_rsp['body'], 'html-entities', 'utf-8');

			if ($doc->loadHTML($html)){

				foreach ($doc->getElementsByTagName('input') as $i){

					$id = $i->getAttribute('id');

					if (preg_match("/place_(latitude|longitude)/", $id, $m)){
						$place[ $m[1] ] = $i->getAttribute('value');
					}
				}

				$has_latlon = ($place['latitude'] && $place['longitude']) ? 1 : 0;
			}

			if ((! $has_latlon) && ($place['street-address'] && $place['locality'] && $place['region'])){

				$q = "{$place['street-address']}, {$place['locality']} {$place['region']}";

				$geo_rsp = geo_geocode_string($q);

				if ($geo_rsp['ok']){
					$place['latitude'] = $geo_rsp['latitude'];
					$place['longitude'] = $geo_rsp['longitude'];
				}
			}

			$rsp = array(
				'ok' => 1,
				'place' => $place,
			);
		}

		cache_set($cache_key, $rsp);
		return $rsp;
	}

	######################################################
?>
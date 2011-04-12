<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of lib_enplacify.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/php-lib-enplacify
	# (20101213/straup)

	######################################################

	# venue HTML pages also contain lots of crunchy good
	# og: properties (20101205/straup)

	######################################################

	function enplacify_foursquare_uri($uri){

		$venue_id = enplacify_foursquare_uri_to_id($uri);

		if (! $venue_id){
			return array(
				'ok' => 0,
				'error' => 'failed to recognize venue id',
			);
		}

		$rsp = enplacify_foursquare_get_venue($venue_id);

		if (! $rsp['ok']){
			return $rsp;
		}

		$address = $rsp['venue']['address'];

		if ($rsp['venue']['crossstreet']){
			$address .= " ({$rsp['venue']['crossstreet']})";
		}

		$place = array(
			'latitude' => $rsp['venue']['geolat'],
			'longitude' => $rsp['venue']['geolong'],
			'name' => $rsp['venue']['name'],
			'address' => $address,
			'derived_from' => 'foursquare',
			'derived_from_id' => $rsp['venue']['id'],
		);

		$others = array(
			'phone' => 'phone',
			'url' => 'url',
		);

		foreach ($others as $theirs => $ours){

			if (isset($rsp['venue'][$theirs])){
				$place[$ours] = $rsp['venue'][$theirs];
			}
		}

		return array(
			'ok' => 1,
			'place' => $place,
			'venue' => $rsp['venue'],
		);
	}

	######################################################

	function enplacify_foursquare_uri_to_id($uri){

		return enplacify_service_uri_to_id('foursquare', $uri);
	}

	######################################################

	function enplacify_foursquare_get_venue($venue_id){

		$cache_key = "enplacify_4sq_venue_{$venue_id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$url = "https://api.foursquare.com/v1/venue.json?vid={$venue_id}";

		$rsp = http_get($url);

		if (! $rsp['ok']){
			return $rsp;
		}

		$json = json_decode($rsp['body'], "fuck off php");

		$rsp = array(
			'ok' => 1,
			'venue' => $json['venue'],
		);

		cache_set($cache_key, $rsp);
		return $rsp;
	}

	######################################################

?>
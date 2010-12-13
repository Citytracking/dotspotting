<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of lib_enplacify.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/php-lib-enplacify
	# (20101213/straup)

	######################################################

	function enplacify_flickr_uri($uri){

		$photo_id = enplacify_flickr_uri_to_id($uri);

		if (! $photo_id){
			return array(
				'ok' => 0,
				'error' => 'failed to recognize photo id',
			);
		}

		$rsp = enplacify_flickr_get_photo($photo_id);

		if (! $rsp['ok']){
			return $rsp;
		}

		$place = array(
			'derived_from' => 'flickr',
			'derived_from_id' => $rsp['photo']['id'],
		);

		$has_loc = 0;

		if ($loc = $rsp['photo']['location']){

			if (isset($loc['locality'])){
				$place['city_id'] = $loc['locality']['woeid'];
			}

			$place['latitude'] = $loc['latitude'];
			$place['longitude'] = $loc['longitude'];
			$has_loc = 1;
		}

		$valid_machinetags = $GLOBALS['cfg']['enplacify']['flickr']['machinetags'];
		$mt_rsp = enplacify_machinetags($rsp['photo']['tags']['tag'], $valid_machinetags);

		if ($mt_rsp['ok']){
			$place = array_merge($mt_rsp['place'], $place);
			$has_loc = 1;
		}

		if (! $has_loc){

			return array(
				'ok' => 0,
				'error' => 'photo has no location data',
			);
		}

		# Check machine tags for extra metadata ?

		return array(
			'ok' => 1,
			'place' => $place,
			'photo' => $rsp['photo'],
		);
	}

	######################################################

	function enplacify_flickr_uri_to_id($uri){

		return enplacify_service_uri_to_id('flickr', $uri);
	}

	######################################################

	function enplacify_flickr_get_photo($photo_id){

		if (! $GLOBALS['cfg']['flickr_apikey']){
			return array( 'ok' => 0, 'error' => 'No Flickr API key' );
		}

		$cache_key = "enplacify_flickr_photo_{$photo_id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$url = "http://api.flickr.com/services/rest/?method=flickr.photos.getInfo";
		$url .= "&photo_id={$photo_id}";
		$url .= "&api_key={$GLOBALS['cfg']['flickr_apikey']}";
		$url .= "&format=json&nojsoncallback=1";

		$rsp = http_get($url);

		if (! $rsp['ok']){
			return $rsp;
		}

		$json = json_decode($rsp['body'], "fuck off php");

		if ($json['stat'] != 'ok'){

			return array(
				'ok' => 0,
				'error' => 'Flickr API error'
			);
		}

		$photo = $json['photo'];

		$rsp = array(
			'ok' => 1,
			'photo' => $json['photo'],
		);

		cache_set($cache_key, $rsp);
		return $rsp;
	}

	######################################################
?>

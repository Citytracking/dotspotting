<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of lib_enplacify.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/php-lib-enplacify
	# (20101213/straup)

	#################################################################

	loadlib("opengraph");
	loadlib("vcard");

	# http://www.chow.com/restaurants/919858/masala-cuisine

	######################################################

	function enplacify_chowhound_uri($uri){

		$restaurant_id = enplacify_chowhound_uri_to_id($uri);

		if (! $restaurant_id){

			return array(
				'ok' => 0,
				'error' => 'failed to recognize restaurant id',
			);
		}

		$rsp = enplacify_chowhound_get_restaurant($restaurant_id);

		if (! $rsp['ok']){
			return $rsp;
		}

		$title = $rsp['restaurant']['title'];

		if (! $title){
			$title = $rsp['restaurant']['fn org'];
		}

		$place = array(
			'latitude' => $rsp['restaurant']['latitude'],
			'longitude' => $rsp['restaurant']['longitude'],
			'name' => $title,
			'phone' => $rsp['restaurant']['tel'],
			'url' => $rsp['restaurant']['url'],
			'address' => $rsp['restaurant']['street-address'],
			'derived_from' => 'chowhound',
			'derived_from_id' => $restaurant_id,
		);

		return array(
			'ok' => 1,
			'place' => $place,
			'restaurant' => $rsp['restaurant'],
		);
	}

	######################################################

	function enplacify_chowhound_uri_to_id($uri){

		return enplacify_service_uri_to_id('chowhound', $uri);
	}

	######################################################

	function enplacify_chowhound_get_restaurant($restaurant_id){

		$cache_key = "enplacify_chowhound_restaurant_{$restaurant_id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$url = "http://www.chow.com/restaurants/" . urlencode($restaurant_id);

		$headers = array();
		$more = array('follow_redirects' => 1);

		$rsp = http_get($url, $headers, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$vcard_rsp = vcard_parse_html($rsp['body']);
		$graph_rsp = opengraph_parse_html($rsp['body']);

		if ((! $vcard_rsp['ok']) && (! $graph_rsp['ok'])){

			$rsp = array(
				'ok' => 0,
				'error' => 'Failed to parse restaurant'
			);
		}

		else {

			$restaurant = array_merge($vcard_rsp['vcard'], $graph_rsp['graph']);
			$restaurant['id'] = $restaurant_id;

			$rsp = array(
				'ok' => 1,
				'restaurant' => $restaurant
			);
		}

		cache_set($cache_key, $rsp);
		return $rsp;
	}

	######################################################

?>

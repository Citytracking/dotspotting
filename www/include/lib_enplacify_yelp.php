<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of lib_enplacify.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/php-lib-enplacify
	# (20101213/straup)

	######################################################

	loadlib("opengraph");
	loadlib("vcard");

	######################################################

	# http://www.yelp.com/biz/smitten-ice-cream-san-francisco

	#
	# thanks yous, facebook:
	#
	# <meta property="og:url" content="http://www.yelp.com/biz/mMiOVbGovegeBg-6YyqDxA">
  	# <meta property="og:longitude" content="-122.4194155">
	# <meta property="og:type" content="restaurant">
	# <meta property="og:description" content="">
	# <meta property="og:latitude" content="37.7749295">
 	# <meta property="og:title" content="Smitten Ice Cream">
      	# <meta property="fb:app_id" content="97534753161">
	# <meta property="og:image" content="http://media2.px.yelpcdn.com/bphoto/JE2uN6iNNqlm89Y5lmuVbw/m">

	# see also:
	# <div id="biz-vcard" class="item vcard">

	######################################################

	function enplacify_yelp_uri($uri){

		$listing_id = enplacify_yelp_uri_to_id($uri);

		if (! $listing_id){
			return array(
				'ok' => 0,
				'error' => 'failed to recognize listing id',
			);
		}

		$rsp = enplacify_yelp_get_listing($listing_id);

		if (! $rsp['ok']){
			return $rsp;
		}

		if (isset($rsp['listing']['url'])){

			if (preg_match("/yelp\.com\/biz\/([^\/]+)/", $rsp['listing']['url'], $m)){
				$listing_id = $m[1];
			}
		}

		$title = $rsp['listing']['title'];

		if (! $title){
			$title = $rsp['listing']['fn org'];
		}

		$place = array(
			'latitude' => $rsp['listing']['latitude'],
			'longitude' => $rsp['listing']['longitude'],
			'name' => $title,
			'phone' => $rsp['listing']['tel'],
			'url' => $rsp['listing']['url'],
			'address' => $rsp['listing']['street-address'],
			'derived_from' => 'yelp',
			'derived_from_id' => $listing_id,
		);

		return array(
			'ok' => 1,
			'place' => $place,
			'listing' => $rsp['listing'],
		);
	}

	######################################################

	function enplacify_yelp_uri_to_id($uri){

		return enplacify_service_uri_to_id('yelp', $uri);
	}

	######################################################

	function enplacify_yelp_get_listing($listing_id){

		$cache_key = "enplacify_yelp_listing_{$listing_id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$url = "http://www.yelp.com/biz/" . urlencode($listing_id);

		$headers = array();
		$more = array('follow_redirects' => 1);

		$rsp = http_get($url, $headers, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$vcard_rsp = vcard_parse_html($rsp['body']);
		$graph_rsp = opengraph_parse_html($rsp['body']);

		if ((! $vcard_rsp['ok']) && (! $graph_rsp['ok'])){

			$rsp = array( 'ok' => 0, 'error' => 'Failed to parse listing' );
		}

		else {

			$listing = array_merge($vcard_rsp['vcard'], $graph_rsp['graph']);
			$listing['id'] = $listing_id;

			$rsp = array( 'ok' => 1, 'listing' => $listing );
		}

		cache_set($cache_key, $rsp);
		return $rsp;
	}

	######################################################

?>
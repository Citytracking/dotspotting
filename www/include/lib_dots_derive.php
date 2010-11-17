<?php

	#
	# $Id$
	#

	#################################################################

	loadlib("geo_geocode");
	loadlib("geo_geohash");
	loadlib("geo_utils");

	#################################################################

	function dots_derive_derived_from_map($string_keys=0){

		if (! $string_keys){
			return $GLOBALS['cfg']['dots_derived_from'];
		}

		return array_flip($GLOBALS['cfg']['dots_derived_from']);
	}

	#################################################################

	#
	# Note we are not passing by ref
	#

	function dots_derive_location_data($data){

		$derived = array(
			'ok' => 0,
		);

		$rsp = array(
			'ok' => 0,
		);

		#

		if (is_numeric($data['latitude']) && is_numeric($data['longitude'])){
			# pass
		}

		else if (isset($data['address'])){

			$rsp = dots_derive_location_from_address($data);
		}

		else if (isset($data['geohash'])){

			$rsp = dots_derive_location_from_geohash($data);
		}

		else {}

		#

		if ($rsp['ok']){

			$derived['ok'] = 1;

			#
			# See this? We're not checking to see $derived[$key]
			# is already set...
			#

			foreach ($rsp['keys'] as $key => $from){
				$derived[$key] = $from;
			}
		}

		#
		# prepare the latitude and longitude create a geohash
		# unless we've already got one
		#

		if (is_numeric($data['latitude']) && is_numeric($data['longitude'])){

			$collapse = 0;	# do not int-ify the coords

			$data['latitude'] = geo_utils_prepare_coordinate($data['latitude'], $collapse);
			$data['longitude'] = geo_utils_prepare_coordinate($data['longitude'], $collapse);

			if (! isset($data['geohash'])){

				$data['geohash'] = geo_geohash_encode($data['latitude'], $data['longitude']);

				$derived_map = dots_derive_derived_from_map('string keys');

				$derived['ok'] = 1;
				$derived['geohash'] = $derived_map['dotspotting'];
			}
		}

		return array($data, $derived);
	}

	#################################################################

	function dots_derive_location_from_address(&$data){

		$geocode_rsp = geo_geocode_string($data['address']);
		
		if (! $geocode_rsp['ok']){

			return array(
			       'ok' => 0,
			);
		}

		# stuff to be marked as "derived_from"

		$derived_keys = array(
			'latitude',
			'longitude',
		);

		$data['latitude'] = $geocode_rsp['latitude'];
		$data['longitude'] = $geocode_rsp['longitude'];

		$geocoded_by = $geocode_rsp['service_id'];

		$geo_map = geo_geocode_service_map();
		$geocoder_name = $geo_map[ $geocoded_by ];

		$derived_map = dots_derive_derived_from_map('string keys');
		$derived_from = $derived_map[ "geocoded ({$geocoder_name})" ];

		foreach ($geocode_rsp['extras'] as $k => $v){

			$extra = "{$geocoder_name}:{$k}";
			$derived_keys[] = $extra;

			$data[$extra] = $v;
		}

		#
		# By default, 'location' is a free-form string assigned
		# by the user. If we don't already have and we have what
		# looks to be a valid WOE ID (and eventually others) then
		# automagically assign the 'location' column using the
		# SERVICE + ":" + UID syntax and then flag 'location' as
		# being a derived key (in this derived from $derived_from
		# which is sussed out above).
		#

		if ((! $data['location']) && ($data['yahoo:woeid'])){

			$data['location'] = "woeid:{$data['yahoo:woeid']}";
			$derived_keys[] = 'location';
		}

		# Happy happy

		$rsp = array(
			'ok' => 1,
			'keys' => array(),
		);

		foreach ($derived_keys as $key){
			$rsp['keys'][$key] = $derived_from;
		}

		return $rsp;
	}

	#################################################################

	function dots_derive_location_from_geohash(&$data){

		list($lat, $lon) = geo_geohash_encode($data['geohash']);

		if ((! $lat) || (! $lon)){

			return array(
				'ok' => 0,
			);
		}

		$data['latitude'] = $lat;
		$data['longitude'] = $lon;

		$derived_keys = array(
			'latitude',
			'longitude',
		);

		$map = dots_derive_derived_from_map('string keys');

		$rsp = array(
			'ok' => 1,
			'keys' => array(),
		);

		foreach ($derived_keys as $key){
			$rsp['keys'][$key] = $derived_from;
		}

		return $rsp;
	}

	#################################################################
?>
<?php

	#
	# $Id$
	#

	# And by 'json' we really mean 'geojson'. See also:
	# http://geojson.org/geojson-spec.html

	#################################################################

	function json_parse_fh($fh, $more=array()){

		$raw = fread($fh, filesize($more['file']['path']));
		$json = json_decode($raw, "as a hash");

		if (! isset($json['features'])){

			return array(
				'ok' => 0,
				'error' => 'Missing features',
			);
		}

		$data = array();
		$errors = array();

		$record = 1;

		foreach ($json['features'] as $f){

			$record ++;

			if (($more['max_records']) && ($record > $more['max_records'])){
				break;
			}

			if (! isset($f['geometry'])){

				$errors[] = array(
					'error' => 'missing geometry',
					'record' => $record,
				);

				continue;
			}

			if (! preg_match("/^(?:Multi)?Point$/", $f['geometry']['type'])){

				$errors[] = array(
					'error' => 'not a supported geometry',
					'record' => $record,
				);

				continue;
			}

			$tmp = array();

			if (isset($f['properties'])){

				foreach ($f['properties'] as $key => $value){
					$key = import_scrub($key);
					$value = import_scrub($value);
					$tmp[$key] = $value;
				}
			}

			# MultiPoints (get their own counter)

			if ($f['geometry']['type'] == 'MultiPoint'){

				$counter = ($record - 1);

				foreach ($f['geometry']['coordinates'] as $coords){

					$counter ++;

					if (($more['max_records']) && ($counter > $more['max_records'])){
						break;
					}

					list($lon, $lat) = $coords;
					list($lat, $lon) = import_ensure_valid_latlon($lat, $lon);

					if (! $lat){

						$errors[] = array(
							'error' => 'invalid latitude',
							'record' => $record,
							'column' => 'latitude',
						);
					}

					if (! $lon){

						$errors[] = array(
							'error' => 'longitude',
							'record' => $record,
							'column' => 'longitude',
						);
					}

					$tmp['latitude'] = $lat;
					$tmp['longitude'] = $lon;

					$data[] = $tmp;
				}

				continue;
			}

			# plain old Points

			list($lon, $lat) = $f['geometry']['coordinates'];
			list($lat, $lon) = import_ensure_valid_latlon($lat, $lon);

			if (! $lat){

				$errors[] = array(
					'error' => 'invalid latitude',
					'record' => $record,
					'column' => 'latitude',
				);

				continue;
			}

			if (! $lon){

				$errors[] = array(
					'error' => 'invalid longitude',
					'record' => $record,
					'column' => 'longitude',
				);

				continue;
			}

			$tmp['latitude'] = $lat;
			$tmp['longitude'] = $lon;

			$data[] = $tmp;
		}

		return array(
			'ok' => 1,
			'data' => &$data,
			'errors' => &$errors,
		);
	}

	#################################################################

	function json_export_dots(&$dots, $more=array()){
        
		$to_skip = array(
			'latitude',
			'longitude',
		);

		$json = array(
			'type' => 'FeatureCollection',
            'dotspotting:title' => $more['sheet_label'],
            'dotspotting:extent' => $more['sheet_extent'],
			'features' => array(),
		);

		foreach ($dots as $dot){

			$feature = array(
				'type' => 'Feature',
				'id' => $dot['id'],
				'geometry' => array(
					'type' => 'Point',
					'coordinates' => array((float)$dot['longitude'], (float)$dot['latitude']),
				),
				'properties' => array(),
			);

			foreach ($dot as $key => $value){

				if (in_array($key, $to_skip)){
					continue;
				}

				$feature['properties'][$key] = $value;
			}
            
          
			$json['features'][] = $feature;
		}

		$rsp = json_encode($json);

		if (isset($more['callback'])){
			$enc_cb = htmlspecialchars($more['callback']);
			$rsp = "{$enc_cb}({$rsp})";
		}

		$fh = fopen($more['path'], 'w');
		fwrite($fh, $rsp);
		fclose($fh);

		return $more['path'];
	}

	#################################################################
?>

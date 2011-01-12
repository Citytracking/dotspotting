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

			list($lon, $lat) = $f['geometry']['coordinates'];
			list($lat, $lon) = import_ensure_valid_latlon($lat, $lon);

			if (! $lat || ! $lon){

				$errors[] = array(
					'error' => 'invalid latitude or longitude',
					'record' => $record,
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

	function json_export_dots(&$dots, $fh){

		$json = array();

		fwrite($fh, json_encode($json));
	}
	
	#################################################################
?>
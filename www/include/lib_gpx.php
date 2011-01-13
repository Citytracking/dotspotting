<?php

	#
	# $Id$
	#

	#################################################################

	function gpx_parse_fh($fh, $more=array()){

		$data = fread($fh, filesize($more['file']['path']));
		fclose($fh);

		libxml_use_internal_errors(true);
		$gpx = simplexml_load_string($data);

		if (! $gpx){

			$errors = array();

			foreach (libxml_get_errors() as $error) {
				$errors[] = $error->message;
			}

			return array(
				'ok' => 0,
				'error' => 'failed to parse XML: ' . implode(";", $errors),
			);
		}

		$label = (string)$gpx->trk->name;
		$label = import_scrub($label);

		$data = array();
		$errors = array();

		$record = 1;

		# HOW TO DO SIMPLIFICATION AND PRESERVE OTHER ATTRIBUTES ?

		foreach ($gpx->trk->trkseg->trkpt as $pt){

			$record ++;

			if (($more['max_records']) && ($record > $more['max_records'])){
				break;
			}

			$attrs = $pt->attributes();
			$lat = (string)$attrs['lat'];
			$lon = (string)$attrs['lon'];

			list($lat, $lon) = import_ensure_valid_latlon($lat, $lon);

			if (! $lat || ! $lon){

				$errors[] = array(
					'record' => $record,
					'error' => 'invalid latlon',
				);

				continue;
			}

			$tmp = array();

			$tmp['latitude'] = $lat;
			$tmp['longitude'] = $lon;

			$created = (string)$pt->time;
			$elevation = (string)$pt->ele;

			$tmp['created'] = import_scrub($created);
			$tmp['elevation'] = import_scrub($elevation);

			$data[] = $tmp;
		}

		return array(
			'ok' => 1,
			'label' => $label,
			'data' => &$data,
			'errors' => &$errors,
		);

	}

	#################################################################

	function gpx_export_dots(&$dots, $fh){

		$ns_map = array(
			'' => 'http://www.topografix.com/GPX/1/1',
			'dotspotting' => 'x-urn:dotspotting#internal',
			'sheet' => 'x-urn:dotspotting#sheet',
		);

		$skip = array(
			'latitude',
			'longitude',
			'elevation',
			'created',
		);

		$doc = new DomDocument('1.0', 'UTF-8');

		$gpx = $doc->createElement('gpx');
		$gpx = $doc->appendChild($gpx);

		$_ver = $doc->createTextNode('1.1');
		$ver = $doc->createAttribute("version");
		$ver->appendChild($_ver);
		$gpx->appendChild($ver);

		foreach ($ns_map as $prefix => $uri){

			$xmlns = ($prefix) ? "xmlns:{$prefix}" : "xmlns";
			$attr = $doc->createAttribute($xmlns);

			$uri = $doc->createTextNode($uri);
			$attr->appendChild($uri);

			$gpx->appendChild($attr);
		}

		$name = $doc->createElement('name');
		# name goes here

		$gpx->appendChild($name);

		$trkseg = $doc->createElement('trkseg');
		$trkseg = $gpx->appendChild($trkseg);

		foreach ($dots as $dot){

			$trk = $doc->createElement('trk');

			$_lat = $doc->createTextNode($dot['latitude']);
			$_lon = $doc->createTextNode($dot['longitude']);

			$lat = $doc->createAttribute("lat");
			$lat->appendChild($_lat);

			$lon = $doc->createAttribute("lon");
			$lon->appendChild($_lon);

			$trk->appendChild($lat);
			$trk->appendChild($lon);

			$_time = $doc->createTextNode($dot['created']);

			$time = $doc->createElement('time');
			$time->appendChild($_time);
			$trk->appendChild($time);

			if (isset($dot['elevation'])){
				$_ele = $doc->createTextNode($dot['elevation']);

				$ele = $doc->createElement('ele');
				$ele->appendChild($_ele);
				$trk->appendChild($ele);
			}

			foreach ($dot as $key => $value){

				if (in_array($key, $skip)){
					continue;
				}

				$properties[] = "{$key}\t{$value}";

				if (! preg_match("/^dotspotting:/", $key)){
					$key = "sheet:{$key}";
				}

				$el = $doc->createElement($key);
				$text = $doc->createTextNode($value);

				$el->appendChild($text);
				$trk->appendChild($el);
			}
			
			$trkseg->appendChild($trk);
		}

		fwrite($fh, $doc->saveXML($gpx));
	}

	#################################################################
?>
<?php

	#
	# $Id$
	#

	# http://www.topografix.com/GPX/1/1/

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

	# For debugging:
	# xmllint --noout --schema http://www.topografix.com/GPX/1/1/gpx.xsd dotspotting-sheet-509-8.gpx 

	function gpx_export_dots(&$dots, &$more){

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

		$_creator = $doc->createTextNode('Dotspotting');
		$creator = $doc->createAttribute('creator');
		$creator->appendChild($_creator);
		$gpx->appendChild($creator);

		foreach ($ns_map as $prefix => $uri){

			$xmlns = ($prefix) ? "xmlns:{$prefix}" : "xmlns";
			$attr = $doc->createAttribute($xmlns);

			$uri = $doc->createTextNode($uri);
			$attr->appendChild($uri);

			$gpx->appendChild($attr);
		}

		$trk = $doc->createElement('trk');
		$trk = $gpx->appendChild($trk);

		$_dot = dots_get_dot($dots[0]['dotspotting:id']);
		$_name = ($_dot['sheet']['label']) ? "Dots from the sheet '{$_dot['sheet']['label']}'" : "Dots from sheet ID #{$_dot['sheet']['id']}";

		$_name = $doc->createTextNode(htmlspecialchars($_name));
		$name = $doc->createElement('name');
		$name->appendChild($_name);
		$trk->appendChild($name);

		$_desc = $doc->createTextNode("n/a");
		$desc = $doc->createElement('desc');
		$desc->appendChild($_desc);
		$trk->appendChild($desc);

		$trkseg = $doc->createElement('trkseg');
		$trkseg = $trk->appendChild($trkseg);

		foreach ($dots as $dot){

			$trkpt = $doc->createElement('trkpt');

			$_lat = $doc->createTextNode($dot['latitude']);
			$_lon = $doc->createTextNode($dot['longitude']);

			$lat = $doc->createAttribute("lat");
			$lat->appendChild($_lat);

			$lon = $doc->createAttribute("lon");
			$lon->appendChild($_lon);

			$trkpt->appendChild($lat);
			$trkpt->appendChild($lon);

			#

			$elevation = (isset($dot['elevation'])) ? $dot['elevation'] : '0.000000';

			$_ele = $doc->createTextNode($elevation);

			$ele = $doc->createElement('ele');
			$ele->appendChild($_ele);
			$trkpt->appendChild($ele);

			#

			$created = strtotime($dot['created']);
			$created = gmdate('Y-m-d\TH:m:s\Z', $created);

			$_time = $doc->createTextNode($created);

			$time = $doc->createElement('time');
			$time->appendChild($_time);
			$trkpt->appendChild($time);

			# non-standard elements makes tools by garmin cry...
			# (20110113/straup)

			$trkseg->appendChild($trkpt);
		}

		$doc->save($more['path']);
		return $more['path'];
	}

	#################################################################
?>
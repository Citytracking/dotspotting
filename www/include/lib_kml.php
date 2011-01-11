<?php

	#
	# $Id$
	#

	# THIS IS STILL BEING REWRITTEN (20110110/straup)

	#################################################################

	function kml_parse_fh($fh, $more=array()){

		$data = fread($fh, filesize($more['file']['path']));
		fclose($fh);

		$xml = new SimpleXMLElement($data);

		if ($nl = $xml->NetworkLink){

			if (! $GLOBALS['cfg']['import_kml_resolve_network_links']){
				return array(
					'ok' => 0,
					'error' => 'Network linked KML files are not currently supported.'
				);
			}

			$url = $nl->Url;
			$link = (string)$url->href;

			$rsp = http_get($link);

			if (! $rsp['ok']){
				return $rsp;
			}

			$xml = new SimpleXMLElement($rsp['body']);
		}

		$data = array();
		$errors = array();

		$record = 1;

		$ctx = ($xml->Document) ? $xml->Document : $xml->Folder;

		if (! $ctx){

			return array(
				'ok' => 0,
				'error' => 'Failed to locate any placemarks',
			);
		}

		$label = (string)$ctx->name;
		$label = import_scrub($label);

		foreach ($ctx->Placemark as $p){

			$record ++;

			if (($more['max_records']) && ($record > $more['max_records'])){
				break;
			}

			$tmp = array();

			# do everything but the geo bits first in case we run in to a big
			# bag of points like we might find in a KML file from Google's
			# mytracks (20110111/straup)

			$title = (string)$p->name;
			$desc = (string)$p->description;

			# foursquare-isms

			if ((preg_match("/^foursquare /", $label)) && ($a = $p->description->a)){
				$attrs = $a->attributes();
				$href = (string)$attrs['href'];

				if (preg_match("/venue\/(\d+)\/?$/", $href, $m)){
					$tmp['foursquare:venue'] = $m[1];
					$desc = (string)$a;
				}
			}

			# random other stuff

			if ($pub = $p->published){
				$pub = (string)$pub;
				$pub = import_scrub($pub);
				$tmp['created'] = $pub;
			}

			if ($vis = $p->visibility){

				if ($vis = (string)$vis){
					$tmp['perms'] = 'private';
				}
			}

			$title = import_scrub($title);
			$desc = import_scrub($desc);

			$tmp['title'] = $title;
			$tmp['description'] = $desc;

			# sigh...

			if ($coords = (string)$p->Point->coordinates){

				list($lon, $lat, $altitude) = explode(",", $coords, 3);
				list($lat, $lon) = import_ensure_valid_latlon($lat, $lon);

				if (! $lat || ! $lon){

					$errors[] = array(
						'record' => $record,
						'error' => 'Invalid latitude or longitude',
					);

					continue;
				}

				$tmp['latitude'] = $lat;
				$tmp['longitude'] = $lon;
				# $tmp['altitude'] = import_scrub($altitude);
			}

			else if ($coords = (string)$p->MultiGeometry->LineString->coordinates){

				# We're going to keep our own counter below
				$record --;

				$coords = explode(" ", $coords);

				# TO DO: simplify me please

				foreach ($coords as $coord){

					$record ++;

					if (($more['max_records']) && ($record > $more['max_records'])){
						break;
					}

					list($lon, $lat, $altitude) = explode(",", $coord, 3);
					list($lat, $lon) = import_ensure_valid_latlon($lat, $lon);

					if (! $lat || ! $lon){

						$errors[] = array(
							'record' => $record,
							'error' => 'Invalid latitude or longitude',
						);

						continue;
					}

					$tmp['latitude'] = $lat;
					$tmp['longitude'] = $lon;

					$tmp['altitude'] = import_scrub($altitude);
					$data[] = $tmp;
				}

				continue;
			}

			else {
				$errors[] = array(
					'record' => $record,
					'error' => 'Unable to determine location information',
				);

				continue;
			}

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

	function kml_export_dots(&$dots, $fh){

		$ns_map = array(
			'' => 'http://earth.google.com/kml/2.0',
			'dotspotting' => 'x-urn:dotspotting#internal',
			'sheet' => 'x-urn:dotspotting#sheet',
		);

		$skip = array(
			'latitude',
			'longitude',
			'altitude',
			'created',
			'title',
			'description',
			'dotspotting:perms',
		);

		$doc = new DomDocument('1.0', 'UTF-8');

		$kml = $doc->createElement('kml');
		$kml = $doc->appendChild($kml);

		foreach ($ns_map as $prefix => $uri){

			$xmlns = ($prefix) ? "xmlns:{$prefix}" : "xmlns";
			$attr = $doc->createAttribute($xmlns);

			$uri = $doc->createTextNode($uri);
			$attr->appendChild($uri);

			$kml->appendChild($attr);
		}

		$document = $doc->createElement('Document');
		$document = $kml->appendChild($document);

		foreach ($dots as $dot){

			$placemark = $doc->createElement('Placemark');

			$properties = array();

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
				$placemark->appendChild($el);
			}

			# title

			if (isset($dot['title'])){			
				$_name = $doc->createTextNode($dot['title']);

				$name = $doc->createElement("name");
				$name->appendChild($_name);
				$placemark->appendChild($name);
			}

			# description

			$_description = $doc->createTextNode(implode("\n", $properties));

			if (isset($dot['description'])){			
				$_description = $doc->createTextNode($dot['description']);
			}

			$description = $doc->createElement("description");
			$description->appendChild($_description);
			$placemark->appendChild($description);

			# pubdate

			$_published = $doc->createTextNode($dot['created']);

			$published = $doc->createElement("published");
			$published->appendChild($_published);
			$placemark->appendChild($published);

			# perms

			$perms = $doc->createTextNode(($dot['dotspotting:perms'] == 'private') ? 0 : 1);

			$visibility = $doc->createElement("visibility");
			$visibility->appendChild($perms);

			# geo

			$lat = $dot['latitude'];
			$lon = $dot['longitude'];

			$_coords = array($lon, $lat);

			if (isset($dot['altitude'])){
				$_coords[] = $dot['altitude'];
			}

			$point = $doc->createElement('Point');
			$coords = $doc->createElement('coordinates');
			$lonlat = $doc->createTextNode(implode(",", $_coords));

			$coords->appendChild($lonlat);
			$point->appendChild($coords);
			$placemark->appendChild($point);

			$document->appendChild($placemark);
		}

		fwrite($fh, $doc->saveXML($kml));
	}

	#################################################################

?>
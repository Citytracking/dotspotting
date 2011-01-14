<?php

	#
	# $Id$
	#

	#################################################################

	loadlib("geo_utils");

	#################################################################

	function rss_parse_fh($fh, $more=array()){

		include_once("magpie/rss_fetch.inc");

		$xml = fread($fh, filesize($more['file']['path']));
		fclose($fh);

		$rss = new MagpieRSS($xml, 'utf-8', 'utf-8', true );

		$data = array();
		$record = 1;
		
		foreach ($rss->items as $item){

			$record ++;

			if (($more['max_records']) && ($record > $more['max_records'])){
				break;
			}

			$has_latlon = 0;

			if ($geo = $item['geo']){

				list($lat, $lon) = import_ensure_valid_latlon($geo['lat'], $geo['lon']);

				$has_latlon = ($lat && $lon) ? 1 : 0;
			}

			if (! $has_latlon && $geo = $item['georss']){

				list($lat, $lon) = explode(" ", $geo['point'], 2);
				list($lat, $lon) = import_ensure_valid_latlon($lat, $lon);

				$has_latlon = ($lat && $lon) ? 1 : 0;
			}

			# What now? Maybe throw the description in to Placemaker ?

			if (! $has_latlon){

				$errors[] = array(
					'record' => $record,
					'error' => 'failed to locate any geo information!'
				);

				continue;
			}

			$tmp = array(
				'guid' => filter_strict(sanitize($item['guid'], 'str')),
				'title' => filter_strict(sanitize($item['title'], 'str')),
				'link' => filter_strict(sanitize($item['link'], 'str')),
				'created' => filter_strict(sanitize($item['pubdate'], 'str')),
				'author' => filter_strict(sanitize($item['author'], 'str')),

				'description' => filter_strict(sanitize($item['description'], 'str')),

				'latitude' => $lat,
				'longitude' => $lon,
			);

			# what to do about 'description' and other tags?

			if (preg_match("/^tag:flickr.com,2004:\/photo\/(\d+)$/", $tmp['guid'], $m)){

				# remove 'foo posted a photo:' stuff here

				$tmp['flickr:id'] = $m[1];

				# Why did we (Flickr) ever do this kind of thing...
				# (20101215/straup)

				$author = str_replace("nobody@flickr.com (", "", $tmp['author']);
				$author = rtrim($author, ")");

				$tmp['author'] = $author;

				if ($woe = $item['woe']){
					$tmp['yahoo:woeid'] = filter_strict(sanitize($woe['woeid'], 'str'));
				}

				if (isset($item['media']) && isset($item['media']['category'])){
					$tmp['tags'] = filter_strict(sanitize($item['media']['category'], 'str'));
				}
			}

			$data[] = $tmp;
		}

		return array(
			'ok' => 1,
			'data' => &$data,
			'errors' => &$errors,
		);
	}

	#################################################################

	function rss_export_dots(&$dots, $fh){

		$_dot = dots_get_dot($dots[0]['dotspotting:id']);
		
		$channel_data = array(
			'title' => "Dots from sheet ID {$_dot['sheet']['id']}",
			'link' => urls_url_for_sheet($_dot['sheet']),
			'description' => "Dots from sheet ID {$_dot['sheet']['id']}",
			'pubDate' => gmdate("c", time()),
			'lastBuildDate' => gmdate("c", time()),
			'generator' => 'Dotspotting',
		);

		$ns_map = array(
			'geo' => 'http://www.georss.org/georss',
			'dotspotting' => 'x-urn:dotspotting#internal',
			'sheet' => 'x-urn:dotspotting#sheet',
		);

		$skip = array(
			'latitude',
			'longitude',
			'altitude',
			'title',
			'description',
		);

		$doc = new DomDocument('1.0', 'UTF-8');

		$rss = $doc->createElement('rss');
		$rss = $doc->appendChild($rss);

		foreach ($ns_map as $prefix => $uri){

			$xmlns = ($prefix) ? "xmlns:{$prefix}" : "xmlns";
			$attr = $doc->createAttribute($xmlns);

			$uri = $doc->createTextNode($uri);
			$attr->appendChild($uri);

			$rss->appendChild($attr);
		}

		$channel = $doc->createElement('channel');
		$channel = $rss->appendChild($channel);

		foreach ($channel_data as $key => $value){

			$text = $doc->createTextNode($value);
			$el = $doc->createElement($key);
			$el->appendChild($text);
			$channel->appendChild($el);
		}

		foreach ($dots as $dot){

			$properties = array();

			$item = $doc->createElement('item');

			foreach ($dot as $key => $value){

				if (in_array($key, $skip)){
					continue;
				}

				$properties[] = implode("\t", array(
					htmlspecialchars($key),
					htmlspecialchars($value)
				));

				if (! preg_match("/^dotspotting:/", $key)){
					$key = "sheet:{$key}";
				}

				$el = $doc->createElement($key);
				$text = $doc->createTextNode($value);

				$el->appendChild($text);
				$item->appendChild($el);
			}

			$coords = array($dot['latitude'],$dot['longitude']);
			$_geo = $doc->createTextNode(implode(",", $coords));

			$geo = $doc->createElement('geo:point');
			$geo->appendChild($_geo);

			$_title = $doc->createTextNode("Dot #{$dot['dotspotting:id']}");

			if (isset($dot['title'])){
				$_title = $doc->createTextNode($dot['title']);
			}

			$title = $doc->createElement('title');
			$title->appendChild($_title);

			$_dot = dots_get_dot($dot['dotspotting:id']);
			$_link = $doc->createTextNode(urls_url_for_dot($_dot));

			$link = $doc->createElement('link');
			$link->appendChild($_link);

			$_description = $doc->createTextNode(implode("<br />", $properties));

			if (isset($dot['description'])){
				$_description = $doc->createTextNode($dot['description']);
			}

			$description = $doc->createElement('description');
			$description->appendChild($_description);

			$item->appendChild($title);
			$item->appendChild($link);
			$item->appendChild($description);
			$item->appendChild($geo);

			$channel->appendChild($item);
		}

		fwrite($fh, $doc->saveXML($rss));
	}

	#################################################################
?>
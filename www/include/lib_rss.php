<?php

	#
	# $Id$
	#

	# THIS SHOULD BE CONSIDERED BEYOND BLEEDING EDGE - if for no other
	# reason than that I haven't checked the magpie stuff in to core
	# yet (20101215/straup)

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

				$lat = filter_strict(sanitize($geo['lat'], 'str'));
				$lon = filter_strict(sanitize($geo['long'], 'str'));

				$lat = ($lat && geo_utils_is_valid_latitude($lat)) ? $lat : null;
				$lon = ($lon && geo_utils_is_valid_longitude($lon)) ? $lon : null;

				$has_latlon = ($lat && $lon) ? 1 : 0;
			}

			if (! $has_latlon && $geo = $item['georss']){

				list($lat, $lon) = explode(" ", $geo['point'], 2);

				$lat = ($lat && geo_utils_is_valid_latitude($lat)) ? $lat : null;
				$lon = ($lon && geo_utils_is_valid_longitude($lon)) ? $lon : null;

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

				'latitude' => $lat,
				'longitude' => $lon,
			);

			# what to do about 'description' and other tags?

			if (preg_match("/^tag:flickr.com,2004:\/photo\/(\d+)$/", $tmp['guid'], $m)){

				$tmp['flickr:id'] = $m[1];

				# Why did we (Flickr) ever do this kind of thing...
				# (20101215/straup)

				$author = str_replace("nobody@flickr.com (", "", $tmp['author']);
				$author = rtrim($author, ")");

				$tmp['author'] = $author;

				if ($woe = $item['woe']){
					$tmp['yahoo:woeid'] = filter_strict(sanitize($woe['woeid'], 'str'));
				}

				# TO DO: tags
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

?>
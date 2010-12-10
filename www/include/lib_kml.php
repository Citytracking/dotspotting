<?php

	#
	# $Id$
	#

	#################################################################

	function kml_parse_fh($fh, $more=array()){

		# Why is there no loadFH method?
		fclose($fh);

		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;

		$doc->load($more['file']['path']);

		$xpath = new DOMXpath($doc);

		$name = $xpath->query("/kml/Folder/name");
		$label = '';

		if ($name->length){
			$label = sanitize($name->item(0)->nodeValue, 'str');
		}

		# Grnn, why does this work in Perl and not in magic happy PHP ?
		#
		# 110 ->perl -MXML::XPath -e 'my $xp = XML::XPath->new("filename" => "flickr.kml"); my $n = 0; map { $n++ } $xp->findnodes("*//Placemark");';
		#
		# It turns out to be namespace nonsense... stopping here and
		# gearing up to just write a state parser... (20101209/straup)

		$nodes = $xpath->query("*//Placemark");

		if (! $nodes->length){
			return array( 'ok' => 0, 'error' => 'Unable to locate any places' );
		}

		$data = array();
		$record = 1;

		$labels_map = array(
			'name' => 'title',
			'published' => 'created',
			'visibility' => 'perms',
		);

		foreach ($nodes as $node){

			$record ++;

			if (($more['max_records']) && ($record > $more['max_records'])){
				break;
			}
			
			$theirs = array();
			$ours = array();

			# Hey look! This is not a general purpose KML parser!!
			# At the moment it is a super bare-bones parser designed
			# for sites like Foursquare and a few others. It will
			# undoubtedly need to be revisited as Dotspotting progresses
			# (20101209/straup)
			#
			# See also: http://blog.jclark.com/2010/11/xml-vs-web_24.html

			_kml_placemark2hash($node, $theirs);

			if (! count($theirs)){
				$errors[] = array( 'record' => $record, 'error' => 'failed to parse placemark' );
				continue;
			}

			foreach ($theirs as $key => $value){			

				if ($key == 'Point'){

					$coords = sanitize($value['coordinates']['#text'], 'str');
					list($lon, $lat) = explode(",", $coords, 2);

					$ours['latitude'] = $lat;
					$ours['longitude'] = $lon;

					foreach ($value as $_key => $_value){

						if ($_key == 'coordinates'){
							continue;
						}

						$rsp = _kml_sanitize($_key, $_value['#text']);

						if (! $rsp['ok']){
							$errors[] = array( 'record' => $record, 'error' => $rsp['error'] );
							continue;
						}

						$ours[ $rsp['key'] ] = $rsp['value'];
					}

					continue;
				}

				else if ($key == 'description'){

					if (isset($value['a']) && isset($value['a']['@href'])){

						if (preg_match("/\/venue\/(\d+)$/", $value['a']['@href'], $m)){
							$ours['foursquare:venue'] = $m[1];
							$value = $value['a']['#text'];
						}
					}

					else {
						$value = $value['#text'];
					}
				}

				else {
					$value = $value['#text'];
				}

				if (isset($labels_map[$key])){
					$key = $labels_map[$key];
				}

				$rsp = _kml_sanitize($key, $value);

				if (! $rsp['ok']){
					$errors[] = array( 'record' => $record, 'error' => $rsp['error'] );
					continue;
				}

				$key_clean = $rsp['key'];
				$value_clean = $rsp['value'];

				if ($key_clean == 'perms'){
					$value_clean = ($value_clean) ? "public" : "private";
				}

				$ours[ $key_clean ] = $value_clean;
			}

			if (isset($ours['foursquare:venue']) && ($ours['title'] == $ours['description'])){
				unset($ours['description']);
			}

			if (isset($ours['#text'])){
				unset($ours['#text']);
			}

			$data[] = $ours;
		}

		return array(
			'ok' => 1,
			'data' => &$data,
			'label' => $label,
			'errors' => &$errors,
		);
	}

	#################################################################

	function _kml_sanitize($key, $value){

		$key_clean = sanitize($key, 'str');
		$value_clean = sanitize($value, 'str');

		if (! $key_clean){
			return array( 'ok' => 0, 'error' => 'invalid key' );
		}

		if (($value) && (! $value_clean)){
			return array( 'ok' => 0, 'error' => 'invalid value' );
		}

		return array( 'ok' => 1, 'key' => $key_clean, 'value' => $value_clean );
	}

	#################################################################

	function _kml_placemark2hash($node, &$hash){

		foreach ($node->childNodes as $el){

			$name = $el->nodeName;
			$value = $el->nodeValue;

			if ($el->hasChildNodes()){

				$kids = array();
				_kml_placemark2hash($el, $kids);

				$hash[$name] = $kids;
				continue;
			}

			$hash[ $name ] = trim($value);
		}

		foreach ($node->attributes as $a){
			$hash[ '@' . $a->name ] = $a->value;
		}
	}

	#################################################################

?>
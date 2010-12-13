<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of lib_enplacify.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/php-lib-enplacify
	# (20101213/straup)

	######################################################

	$GLOBALS['vcard_valid_classes'] = array(
		'fn org',
		'tel',
		'street-address',
		'locality',
		'region',
	);

	######################################################

	function vcard_parse_html($html){

		$html = mb_convert_encoding($html, 'html-entities', 'utf-8');

		libxml_use_internal_errors(true);

		$doc = new DOMDocument();
		$ok = $doc->loadHTML($html);

		if (! $ok){
			return array( 'ok' => 0, 'error' => 'Failed to parse HTML' );
		}

		# Just use XPath hooks instead ?
		# Or just write a Expat parser which might be faster all the way around...

		$tags = $doc->getElementsByTagName('div');

		if (! $tags->length){
			return array( 'ok' => 0, 'error' => 'No div tags' );
		}

		$vcard = array();

		foreach ($tags as $tag){

			if (! $tag->hasAttribute("class")){
				continue;
			}

			$classes = explode(" ", $tag->getAttribute("class"));

			if (! in_array("vcard", $classes)){
				continue;
			}

			_vcard_parse_node($tag, $vcard);
			break;
		}

		if (! count($vcard)){
			return array( 'ok' => 0, 'error' => 'Failed to locate any vcard data' );
		}

		return array(
			'ok' => 1,
			'vcard' => $vcard,
		);
	}

	######################################################

	function _vcard_parse_node($node, &$vcard){

		foreach ($node->childNodes as $kid){

			if ($kid->nodeType != XML_ELEMENT_NODE){
				continue;
			}

			if ($kid->hasAttribute("class")){
			
				$class = $kid->getAttribute("class");

				if (in_array($class, $GLOBALS['vcard_valid_classes'])){
					$vcard[ $class ] = $kid->nodeValue;
				}
			}

			if ($kid->hasChildNodes()){
				_vcard_parse_node($kid, $vcard);
			}
		}

		# Note the pass by ref
	}

	######################################################
?>

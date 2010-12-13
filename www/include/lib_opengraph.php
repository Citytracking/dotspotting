<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of lib_enplacify.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/php-lib-enplacify
	# (20101213/straup)

	######################################################

	function opengraph_parse_html(&$html){

		# See also: https://github.com/scottmac/opengraph/

		libxml_use_internal_errors(true);

		$doc = new DOMDocument();
		$ok = $doc->loadHTML($html);

		if (! $ok){
			return array( 'ok' => 0, 'error' => 'Failed to parse HTML' );
		}

		$tags = $doc->getElementsByTagName('meta');

		if (! $tags->length){
			return array( 'ok' => 0, 'error' => 'No meta tags' );
		}

		$og = array();

		foreach ($tags as $tag){

			if (! $tag->hasAttribute('property')){
				continue;
			}

			$prop = $tag->getAttribute("property");

			if (! preg_match("/^og\:(.*)$/", $prop, $m)){
				continue;
			}

			$k = $m[1];
			$v = $tag->getAttribute("content");

			$og[$k] = $v;
		}

		if (! count($og)){
			return array( 'ok' => 0, 'error' => 'No opengraph tags' );
		}

		return array(
			'ok' => 1,
			'graph' => $og,
		);
	}

	######################################################
?>
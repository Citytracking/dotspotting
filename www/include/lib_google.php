<?php

	#
	# $Id$
	#

	loadlib("utils");

	#################################################################

	# http://maps.google.com/maps/ms?ie=UTF8&hl=en&msa=0&msid=106670048759200881360.0004565744030ff07d00e&z=11
	# http://maps.google.com/maps/ms?ie=UTF8&hl=en&msa=0&output=georss&msid=205571002759787943953.0004565744030ff07d00e

	function google_get_mymaps_georss_feed($url){

		$cache_key = "mymaps_georss_" . md5($url);
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$parts = utils_parse_url($url);

		if ($parts['host'] != 'maps.google.com'){
			return null;
		}

		if ($parts['path'] != '/maps/ms'){
			return null;
		}

		$query = array();

		foreach (explode("&", $parts['query']) as $q){

			list($key, $value) = explode("=", $q, 2);
			$query[$key] = $value;
		}

		if (! $query['msid']){
			return null;
		}

		$query['output'] = 'georss';

		$feed_url = "http://{$parts['host']}{$parts['path']}?" . http_build_query($query);

		cache_set($cache_key, $feed_url, "cache locally");
		return $feed_url;
	}

	#################################################################
?>
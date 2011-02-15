<?php

	#
	# $Id$
	#

	loadlib("utils");

	#################################################################

	function google_is_google_hostname($host, $subdomain=''){

		# There are, it turns out, many many valid TLDs for
		# Google. The rough pattern seems to be .com followed
		# sometimes by a 2-letter country code or .co always
		# followed by a country code or just the country code
		# all by itself.
		
		# See also:
		# http://www.thomasbindl.com/blog/?title=list_of_googel_tlds&more=1&c=1&tb=1&pb=1

		if (preg_match("/\.google\.com(?:\.[a-z][a-z])?$/", $host)){
			return 1;
		}

		if (preg_match("/\.google\.co\.[a-z][a-z]$/", $host)){
			return 1;
		}

		if (preg_match("/\.google\.[a-z][a-z]$/", $host)){
			return 1;
		}

		return 0;
	}

	#################################################################

	# I briefly considered calling this literally...
	# 	google_is_somefeature_hostname($feature)
	# ...but then decided against it (20110215/straup)

	function google_is_mymaps_hostname($host){

		if (! preg_match("/^mymaps\./", $host)){

			$host = "mymaps.{$host}";
		}

		return google_is_google_hostname($host);
	}

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

		if (! google_is_mymaps_hostname($parts['host'])){
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
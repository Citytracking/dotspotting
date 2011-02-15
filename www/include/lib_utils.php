<?php

	#
	# $Id$
	#

	#################################################################

	# http://www.php.net/manual/en/function.parse-url.php#90365

	function utils_parse_url($url){

		$r  = "(?:([a-z0-9+-._]+)://)?";
		$r .= "(?:";
		$r .=   "(?:((?:[a-z0-9-._~!$&'()*+,;=:]|%[0-9a-f]{2})*)@)?";
		$r .=   "(?:\[((?:[a-z0-9:])*)\])?";
		$r .=   "((?:[a-z0-9-._~!$&'()*+,;=]|%[0-9a-f]{2})*)";
		$r .=   "(?::(\d*))?";
		$r .=   "(/(?:[a-z0-9-._~!$&'()*+,;=:@/]|%[0-9a-f]{2})*)?";
		$r .=   "|";
		$r .=   "(/?";
		$r .=     "(?:[a-z0-9-._~!$&'()*+,;=:@]|%[0-9a-f]{2})+";
		$r .=     "(?:[a-z0-9-._~!$&'()*+,;=:@\/]|%[0-9a-f]{2})*";
		$r .=    ")?";
		$r .= ")";
		$r .= "(?:\?((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";
		$r .= "(?:#((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";

		if (! preg_match("`$r`i", $url, $match)){
			return array( 'ok' => 0 );
		}

		$parts = array(
			"ok" => 1,
			"scheme"=>'',
			"userinfo"=>'',
			"authority"=>'',
			"host"=> '',
			"port"=>'',
			"path"=>'',
			"query"=>'',
			"fragment"=>''
		);

		switch (count($match)){
			case 10: $parts['fragment'] = $match[9];
			case 9: $parts['query'] = $match[8];
			case 8: $parts['path'] =  $match[7];
			case 7: $parts['path'] =  $match[6] . $parts['path'];
			case 6: $parts['port'] =  $match[5];
			case 5: $parts['host'] =  $match[3]?"[".$match[3]."]":$match[4];
			case 4: $parts['userinfo'] =  $match[2];
			case 3: $parts['scheme'] =  $match[1];
		}

		$parts['authority'] = ($parts['userinfo']?$parts['userinfo']."@":"") .
		$parts['host'] .
		($parts['port'] ? ":" . $parts['port'] : "");

		return $parts;
	}

	#################################################################

	function utils_scrub_url($url){

		$parts = utils_parse_url($url);

		$query = array();

		foreach (explode("&", $parts['query']) as $q){

			list($key, $value) = explode("=", $q);
			$query[$key] = $value;
		}

		$url = "{$parts['scheme']}://{$parts['host']}{$parts['path']}?";
		$url .= http_build_query($query);

		return $url;
	}

	#################################################################
?>
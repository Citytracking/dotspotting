<?php

	#
	# $Id$
	#
	
	#################################################################
	
	loadlib("http");
	
	#################################################################
	
	
	function geo_geocode_string($string) {
		$service = $GLOBALS['cfg']['geocode']['service'];
		return call_user_func('geo_geocode_'.$service, $string);
	}
	
	function geo_geocode_yahoo($string) {
		$api_key = $GLOBALS['cfg']['geocode']['yahoo_key'];
		$url = 'http://where.yahooapis.com/geocode?q='.urlencode($string).'&flags=j&appid='.$api_key;
		
		$http_rsp = http_get($url);
		
		$rsp = array(
			'ok' => 0,
			'error' => 'unknown error'
		);
		
		if ($http_rsp['ok']) {
			
			# pass in a 1 to disable 'shit-mode'
			$geocode_response = json_decode($http_rsp['body'], 1);
			
			if ($geocode_response['ResultSet']['Found'] == 1) {
				
				$results = $geocode_response['ResultSet']['Results'][0];
				
				$rsp['ok'] = 1;
				$rsp['error'] = null;
				$rsp['latitude'] = (float)$results['latitude'];
				$rsp['longitude'] = (float)$results['longitude'];
				$rsp['extras']['woeid'] = (float)$results['woeid'];
			} else {
				$rsp['error'] = 'could not geocode';
			}
			
		}
		
		return $rsp;
	}
	
?>
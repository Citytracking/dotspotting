<?php

	#
	# $Id$
	#

	loadlib("http");

	#################################################################

	function flickr_lookup_group_id_by_url($url){
		return _flickr_lookup_id_by_url($url, 'group');
	}

	function flickr_lookup_user_id_by_url($url){
		return _flickr_lookup_id_by_url($url, 'user');
	}

	function _flickr_lookup_id_by_url($url, $type){

		$cache_key = "flickr_lookup_" . md5($url);
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$method = 'flickr.urls.lookup' . ucwords($type);
		$args = array('url' => $url);

		$_rsp = flickr_api_call($method, $args);

		if (! $_rsp['ok']){
			return null;
		}

		$id = $_rsp['rsp'][$type]['id'];
		cache_set($cache_key, $id);

		return $id;
	}

	#################################################################

	function flickr_api_call($method, $args=array()){

		$args['api_key'] = $GLOBALS['cfg']['flickr_apikey'];

		$args['method'] = $method;
		$args['format'] = 'json';
		$args['nojsoncallback'] = 1;

		if (isset($args['auth_token'])){
			$api_sig = _flickr_api_sign_args($args);
			$args['api_sig'] = $api_sig;
		}

		$url = "http://api.flickr.com/services/rest";

		$url = $url . "?" . http_build_query($args);
		#dumper($url);

		# The Flickr API is being slow (20110429/straup)

		$headers = array();
		$more = array('http_timeout' => 5);

		$rsp = http_get($url, $headers, $more);

		# At some point we may need to do POSTs but for
		# now it's not really an issue
		# $rsp = http_post($url, $args);

		if (! $rsp['ok']){
			return $rsp;
		}

		$json = json_decode($rsp['body'], 'as a hash');

		if (! $json){
			return array( 'ok' => 0, 'error' => 'failed to parse response' );
		}

		if ($json['stat'] != 'ok'){
			return array( 'ok' => 0, 'error' => $json['message']);
		}

		unset($json['stat']);
		return array( 'ok' => 1, 'rsp' => $json );
	}

	#################################################################

	function _flickr_api_sign_args($args){

		$parts = array(
			$GLOBALS['cfg']['flickr_apisecret']
		);

		$keys = array_keys($args);
		sort($keys);

		foreach ($keys as $k){
			$parts[] = $k . $args[$k];
		}

		$raw = implode("", $parts);
		return md5($raw);
	}

	#################################################################

	function flickr_get_georss_feed($url){

		$cache_key = "flickr_georss_" . md5($url);
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$http_rsp = http_get($url);

		$html = mb_convert_encoding($http_rsp['body'], 'html-entities', 'utf-8');

		libxml_use_internal_errors(true);

		$doc = new DOMDocument();
		$ok = $doc->loadHTML($html);

		if (! $ok){
			return null;
		}

		$feed_url = null;

		foreach ($doc->getElementsByTagName('link') as $link){

			if ($link->getAttribute('rel') != 'alternate'){
				continue;
			}

			if ($link->getAttribute('type') != 'application/rss+xml'){
				continue;
			}

			$href = $link->getAttribute('href');

			# For example (note how we ask for RSS 2.0 explicitly) :
			# http://api.flickr.com/services/feeds/geo/?id=35034348999@N01&amp;lang=en-us

			if (preg_match("/\/geo\//", $href)){
				$feed_url = $href . "&format=rss_200";
				break;
			}
		}

		cache_set($cache_key, $feed_url, "cache locally");
		return $feed_url;
	}

	#################################################################
?>

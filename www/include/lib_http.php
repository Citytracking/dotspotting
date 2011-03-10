<?
	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of flamework.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/flamework (straup/20101213)

	########################################################################

	$GLOBALS['timings']['http_count']	= 0;
	$GLOBALS['timings']['http_time']	= 0;
	$GLOBALS['timing_keys']['http'] = 'HTTP Requests';

	########################################################################

	function http_head($url, $headers=array()){
		return http_get($url, $headers, array('head' => 1));
	}

	########################################################################

	function http_get($url, $headers=array(), $more=array()){

		$headers_prepped = _http_prepare_outgoing_headers($headers);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_prepped);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $GLOBALS['cfg']['http_timeout']);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_HEADER, true);

		if ($more['head']){
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		}

		return _http_request($ch, $url, $more);
	}

	########################################################################

	function http_post($url, $post_fields, $headers=array(), $more=array()){

		$headers_prepped = _http_prepare_outgoing_headers($headers);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_prepped);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $GLOBALS['cfg']['http_timeout']);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_HEADER, true);

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

		return _http_request($ch, $url, $more);
	}

	########################################################################

	function _http_request($ch, $url, $more=array()){

		#
		# execute request
		#

		$start = microtime_ms();

		$raw = curl_exec($ch);
		$info = curl_getinfo($ch);

		$end = microtime_ms();

		curl_close($ch);

		$GLOBALS['timings']['http_count']++;
		$GLOBALS['timings']['http_time'] += $end-$start;


		#
		# parse request & response
		#

		list($head, $body) = explode("\r\n\r\n", $raw, 2);
		list($head_out, $body_out) = explode("\r\n\r\n", $info['request_header'], 2);
		unset($info['request_header']);

		$headers_in = http_parse_headers($head, '_status');
		$headers_out = http_parse_headers($head_out, '_request');

		preg_match("/^([A-Z]+)\s/", $headers_out['_request'], $m);
		$method = $m[1];

		log_notice("http", "{$method} {$url}", $end-$start);

		#
		# return
		#

		$status = $info['http_code'];

		if (in_array($method, array('GET', 'POST')) && $more['follow_redirects'] && ($status == 301 || $status == 302)){

			$more['follow_redirects'] ++;	# should we check to see that we're not trapped in a loop?

			if (preg_match("/^http\:\/\//", $headers_in['location'])){
				$redirect = $headers_in['location'];
			}

			else {
				$redirect = $headers_out['host'] . $headers_in['location'];
			}

			return http_get($redirect, $headers, $more);
		}

		# http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.2
		# http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#2xx_Success (note HTTP 207 WTF)

		if (($status < 200) || ($status > 299)){

			return array(
				'ok'		=> 0,
				'error'	=> 'http_failed',
				'code'		=> $info['http_code'],
				'url'		=> $url,
				'info'		=> $info,
				'req_headers'	=> $headers_out,
				'headers'	=> $headers_in,
				'body'		=> $body,
			);
		}

		return array(
			'ok'		=> 1,
			'url'		=> $url,
			'info'		=> $info,
			'req_headers'	=> $headers_out,
			'headers'	=> $headers_in,
			'body'		=> $body,
		);
	}

	########################################################################

	function http_parse_headers($raw, $first){

		#
		# first, deal with folded lines
		#

		$raw_lines = explode("\r\n", $raw);

		$lines = array();
		$lines[] = array_shift($raw_lines);

		foreach ($raw_lines as $line){
			if (preg_match("!^[ \t]!", $line)){
				$lines[count($lines)-1] .= ' '.trim($line);
			}else{
				$lines[] = trim($line);
			}
		}


		#
		# now split them out
		#

		$out = array(
			$first => array_shift($lines),
		);

		foreach ($lines as $line){
			list($k, $v) = explode(':', $line, 2);
			$out[StrToLower($k)] = trim($v);
		}

		return $out;
	}

	########################################################################

	function _http_prepare_outgoing_headers($headers=array()){

		$prepped = array();

		if (! isset($headers['Expect'])){
			$headers['Expect'] = '';	# Get around error 417
		}

		foreach ($headers as $key => $value){
			$prepped[] = "{$key}: {$value}";
		}

		return $prepped;
	}

	########################################################################
?>
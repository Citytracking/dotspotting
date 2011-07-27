<?php

	loadlib("http");

	########################################################################

	function wscompose_get(&$map){

		$url = _wscompose_request_url($map);

		$headers = array();

		$more = array(
			'http_port' => $GLOBALS['cfg']['wscompose_port'],
		);

		$rsp = http_get($url, $headers, $more);
		return _wscompose_parse_response($rsp);
	}

	########################################################################

	function wscompose_get_many(&$maps){

		$requests = array();

		$more = array(
			'http_port' => $GLOBALS['cfg']['wscompose_port'],
		);

		foreach ($maps as $map){
			$url = _wscompose_request_url($map);
			$requests[] = array('url' => $url, 'more' => $more);
		}

		$_responses = http_multi($requests);
		$responses = array();

		foreach ($_responses as $rsp){
			$responses[] = _wscompose_parse_response($rsp);
		}

		return $responses;
	}

	########################################################################

	function _wscompose_request_url($args){

		$defaults = array(
			'provider' => $GLOBALS['cfg']['maptiles_template_url'],
			'method' => 'center',
		);

		$args = array_merge($defaults, $args);
		$query = http_build_query($args);

		$url = $GLOBALS['cfg']['wscompose_host'] . "?" . $query;
		return $url;
	}

	########################################################################

	function _wscompose_parse_response($rsp){

		if (! $rsp['ok']){
			return $rsp;
		}

		$type = $rsp['headers']['content-type'];

		if (! preg_match("/^image\//", $type)){
			return array(
				'ok' => 0,
				'error' => "expected an image but got {$type} instead",
			);
		}

		$im = imagecreatefromstring($rsp['body']);

		if ($im === false){

			return array(
				'ok' => 0,
				'error' => 'failed to create image',
			);
		}

		$details = array();

		foreach ($rsp['headers'] as $k => $v){

			if (preg_match("/^x-wscompose-(.*)$/", $k, $m)){
				$details[$m[1]] = $v;
			}
		}

		$rsp['type'] = $type;
		$rsp['image'] = $im;
		$rsp['details'] = $details;

		unset($rsp['body']);

		return $rsp;
	}

	########################################################################

?>

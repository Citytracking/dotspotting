<?php

	loadlib("http");

	$GLOBALS['cfg']['wscompose_host'] = 'http://127.0.0.1/';
	$GLOBALS['cfg']['wscompose_port'] = '9999';

	########################################################################

	function wscompose_get($args){

		$defaults = array(
			'provider' => $GLOBALS['cfg']['maptiles_template_url'],
			'method' => 'center',
		);

		$headers = array();

		$more = array(
			'http_port' => $GLOBALS['cfg']['wscompose_port'],
		);

		$args = array_merge($defaults, $args);
		$query = http_build_query($args);

		$url = $GLOBALS['cfg']['wscompose_host'] . "?" . $query;

		$rsp = http_get($url, $headers, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$type = $rsp['headers']['content-type'];

		if (! preg_match("/^image\/", $type)){
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

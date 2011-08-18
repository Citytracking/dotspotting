<?php

	# A simple wrapper for http://github.com/wavded/ogre

	#################################################################

	loadlib("http");

	#################################################################

	function geo_ogre_convert_file($path){

		if (! file_exists($path)){
			return array(
				'ok' => 0,
				'error' => 'file does not exist',
			);
		}

		$url = $GLOBALS['cfg']['ogre_host'] . $GLOBALS['cfg']['ogre_convert_endpoint'];

		$post_data = array(
			'upload' => "@{$path}",
		);

		$headers = array();

		$more = array(
			'http_port' => $GLOBALS['cfg']['ogre_port'],
		);

		$rsp = http_post($url, $post_data, $headers, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$json = json_decode($rsp['body'], 'as a hash');

		if (! $json){
			return array(
				'ok' => 0,
				'error' => 'failed to decode JSON',
			);
		}

		if (isset($json['error'])){
			return array(
				'ok' => 0,
				'error' => $json['message'],
			);
		}

		return array(
			'ok' => 1,
			'data' => $json,
		);
	}

	#################################################################
?>

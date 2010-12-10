<?php

	#
	# $Id$
	#

	# I don't know why it's called Slim Jim. Ask Mike...
	# https://github.com/migurski/This-Tract/blob/master/slimjim.php

	include("include/init.php");
	loadlib("cache");

	# Check referers here?
	# dumper($_SERVER);

	$slimjim_map = array(

		'foursquare:venue' => array(
			'endpoint' => 'http://api.foursquare.com/v1/venue.json?vid=%s',
			'mimetype' => 'application/json',
		),
	);

	$what = get_str("what");
	list($service_name, $uid) = explode("=", $what, 2);

	if ((! $service_name) || (! isset($slimjim_map[$service_name]))){
		error_404();
	}

	if (! $uid){
		error_404();
	}

	$service = $slimjim_map[$service_name];

	$url = sprintf($service['endpoint'], urlencode($uid));

	$cache_key = "slimjim_{$service_name}_" . md5($url);
	$cache = cache_get($cache_key);

	if ($cache['ok']){
		$rsp = $cache['data'];
	}

	else {
		$rsp = http_get($url);

		if (! $rsp['ok']){
			error_500();
		}

		cache_set($cache_key, $rsp);
	}
	
	header('HTTP/1.1 200');
	header("Content-Type: {$rsp['headers']['content-type']}");

	echo $rsp['body'];
	exit();
?>
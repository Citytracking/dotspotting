<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of lib_enplacify.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/php-lib-enplacify
	# (20101213/straup)

	######################################################

	function enplacify_uri($uri){

		foreach ($GLOBALS['cfg']['enplacify'] as $service => $data){

			foreach ($data['uris'] as $pattern){

				if (! preg_match($pattern, $uri)){
					continue;
				}

				$service_lib = "enplacify_{$service}";
				$service_func = "enplacify_{$service}_uri";

				loadlib($service_lib);

				$rsp = call_user_func_array($service_func, array($uri));
				return $rsp;
			}
		}

		return array(
			'ok' => 0,
			'error' => 'failed to locate any valid services for URL',
		);
	}

	######################################################

	function enplacify_machinetags(&$tags, &$valid_machinetags){

		# return array( 'ok' => 0, 'error' => 'this does not work yet...' );

		# TODO: do machinetag vs. plaintag filtering outside
		# this function (20101211/straup)

		foreach ($tags as $tag){

			if (! $tag['machine_tag']){
				continue;
			}

			list($nspred, $value) = explode("=", $tag['raw'], 2);
			list($ns, $pred) = explode(":", $nspred, 2);

			if (! isset($valid_machinetags[$ns])){
				continue;
			}

			if (! in_array($pred, $valid_machinetags[$ns])){
				continue;
			}

			$rsp = enplacify_uri($tag['raw']);

			if ($rsp['ok']){
				return $rsp;
			}
		}

		return array( 'ok' => 0, 'error' => 'unable to enplacify machine tags' );
	}

	######################################################

	# This is just a generic wrapper because most services only
	# only need to have a single identifier teased out of a given
	# URI. It's meant to be called *inside* of a service specific
	# function. Or not. See also: enplacify_dopplr_uri_to_id()
	# (20101211/straup)

	function enplacify_service_uri_to_id($service, $uri){

		if (! isset($GLOBALS['cfg']['enplacify'][ $service ])){
			return null;
		}

		$service_id = null;

		$uris = $GLOBALS['cfg']['enplacify'][ $service ]['uris'];

		foreach ($uris as $pattern){

			if (preg_match($pattern, $uri, $m)){
				$service_id = $m[1];
				break;
			}
		}

		return $service_id;
	}

	######################################################
?>

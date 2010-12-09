<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of flamework.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/flamework

	#################################################################

	$GLOBALS['local_cache'] = array();

	#################################################################

	function cache_get($cache_key){

		$cache_key = _cache_prepare_cache_key($cache_key);
		log_notice("cache", "fetch cache key {$cache_key}");

		if (isset($GLOBALS['local_cache'][$cache_key])){

			return array(
				'ok' => 1,
				'cache' => 'local',
				'cache_key' => $cache_key,
				'data' => $GLOBALS['local_cache'][$cache_key],
			);
		}

		# remote cache?

		if ($engine = $GLOBALS['cfg']['remote_cache_engine']){

			$func = "cache_{$engine}_get";

			if (function_exists($func)){
				return call_user_func_array($func, array($cache_key));
			}
		}

		return array( 'ok' => 0 );
	}

	#################################################################

	function cache_set($cache_key, $data, $store_locally=0){

		$cache_key = _cache_prepare_cache_key($cache_key);
		log_notice("cache", "set cache key {$cache_key}");

		if ($store_locally){
			$GLOBALS['local_cache'][$cache_key] = $data;
		}

		# remote cache?

		if ($engine = $GLOBALS['cfg']['remote_cache_engine']){

			$func = "cache_{$engine}_set";

			if (function_exists($func)){
				$rsp = call_user_func_array($func, array($cache_key, $data));
			}
		}

		return array( 'ok' => 1 );
	}

	#################################################################

	function cache_unset($cache_key){

		$cache_key = _cache_prepare_cache_key($cache_key);
		log_notice("cache", "unset cache key {$cache_key}");

		if (isset($GLOBALS['local_cache'][$cache_key])){
			unset($GLOBALS['local_cache'][$cache_key]);
		}

		if ($engine = $GLOBALS['cfg']['remote_cache_engine']){

			$func = "cache_{$engine}_unset";

			if (function_exists($func)){
				$rsp = call_user_func_array($func, array($cache_key));
				}
		}

		return array( 'ok' => 1 );
	}

	#################################################################

	function _cache_prepare_cache_key($key){
		return $key;
	}

	#################################################################
?>

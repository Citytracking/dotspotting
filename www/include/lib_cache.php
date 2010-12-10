<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of flamework.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/flamework (20101210/straup)

	#################################################################

	$GLOBALS['local_cache'] = array();
	$GLOBALS['remote_cache_conns'] = array();

	#################################################################

	function cache_get($cache_key){

		if ($GLOBALS['cfg']['cache_force_refresh']){

			return array(
				'ok' => 0,
				'error' => 'force refresh'
			);
		}

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

		$remote_rsp = _cache_do_remote('get', $cache_key);

		return $remote_rsp;
	}

	#################################################################

	function cache_set($cache_key, $data, $store_locally=0){

		$cache_key = _cache_prepare_cache_key($cache_key);
		log_notice("cache", "set cache key {$cache_key}");

		if ($store_locally){
			$GLOBALS['local_cache'][$cache_key] = $data;
		}

		$remote_rsp = _cache_do_remote('set', $cache_key, $data);

		return array(
			'ok' => 1
		);
	}

	#################################################################

	function cache_unset($cache_key){

		$cache_key = _cache_prepare_cache_key($cache_key);
		log_notice("cache", "unset cache key {$cache_key}");

		if (isset($GLOBALS['local_cache'][$cache_key])){
			unset($GLOBALS['local_cache'][$cache_key]);
		}

		$remote_rsp = _cache_do_remote('unset', $cache_key);

		return array(
			'ok' => 1
		);
	}

	#################################################################

	function _cache_prepare_cache_key($key){

		if (! isset($GLOBALS['cfg']['cache_prefix'])){
			return $key;
		}

		return "{$GLOBALS['cfg']['cache_prefix']}_{$key}";
	}

	#################################################################

	function _cache_do_remote($method, $key, $data=null){

		$engine = $GLOBALS['cfg']['remote_cache_engine'];

		if (! $engine){
			return array( 'ok' => 0, 'error' => 'Remote caching is not enabled' );
		}

		$remote_lib = "cache_{$engine}";
		$remote_func = "cache_{$engine}_{$method}";

		$args = ($data) ? array($key, $data) : array($key);

		loadlib($remote_lib);
		$rsp = call_user_func_array($remote_func, $args);

		$rsp['cache_key'] = $key;
		$rsp['cache'] = $engine;

		return $rsp;
	}

	#################################################################
?>

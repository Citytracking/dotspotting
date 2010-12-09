<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of flamework.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/flamework

	#################################################################

	function cache_memcache_init($host, $port){

		$memcache = new Memcache();

		if (! $memcache->connect($host, $port)){
			$memcache = null;
		}

		return $memcache;
	}

	#################################################################

	function cache_memcache_get($cache_key){

		$memcache = $GLOBALS['cfg']['memcache_conn'];

		if (! $memcache){
			return array( 'ok' => 0 );
		}

		$rsp = $memcache->get($cache_key);

		if (! $rsp){
			return array( 'ok' => 0 );
		}

		return array(
			'ok' => 1,
			'data' => unserialize($rsp),
		);
	}

	#################################################################

	function cache_memcache_set($cache_key, $data){

		$memcache = $GLOBALS['cfg']['memcache_conn'];

		if (! $memcache){
			return array( 'ok' => 0 );
		}

		$ok = $memcache->set($cache_key, serialize($data));
		return array( 'ok' => $ok );
	}

	#################################################################

	function cache_memcache_unset($cache_key){

		$memcache = $GLOBALS['cfg']['memcache_conn'];

		if (! $memcache){
			return array( 'ok' => 0 );
		}

		$ok = $memcache->delete($cache_key);
		return array( 'ok' => $ok );
	}

	#################################################################
?>

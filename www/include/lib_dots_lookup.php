<?php

	#
	# $Id$
	#

	#################################################################

	function dots_lookup_dot($dot_id){

		$cache_key = "dots_lookup_{$dot_id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$enc_id = AddSlashes($dot_id);

		$sql = "SELECT * FROM DotsLookup WHERE dot_id='{$enc_id}'";
		$rsp = db_fetch($sql);

		if ($rsp['ok']){

			cache_set($cache_key, $rsp, 'cache locally');
		}

		return db_single($rsp);
	}

	#################################################################

	function dots_lookup_create(&$lookup){

		$hash = array();

		foreach ($lookup as $key => $value){
			$hash[$key] = AddSlashes($value);
		}

		return db_insert('DotsLookup', $hash);
	}

	#################################################################

	function dots_lookup_update(&$dot, &$update){

		$cache_key = "dots_lookup_{$dot['id']}";
		cache_unset($cache_key);

		$hash = array();

		foreach ($update as $key => $value){
			$hash[$key] = AddSlashes($value);
		}

		$enc_id = AddSlashes($dot['id']);
		$where = "dot_id={$enc_id}";

		return db_update('DotsLookup', $update, $where);
	}

	#################################################################

	function dots_lookup_add_lots_of_dots(&$dots, $add_offline=0){

		$_dots = array();

		foreach ($dots as $d){

			$hash = array();

			foreach ($d as $key => $value){
				$hash[$key] = AddSlashes($value);
			}

			$_dots[] = $hash;
		}

		return db_insert_many('DotsLookup', $_dots);
	}

	#################################################################

?>
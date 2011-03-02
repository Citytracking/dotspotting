<?php

	#
	# $Id$
	#

	#################################################################

	function dots_search_extras_add_lots_of_extras(&$extras, $add_offline=0){

		$_extras = array();

		foreach ($extras as $e){

			$hash = array();

			foreach ($e as $key => $value){
				$hash[$key] = AddSlashes($value);
			}

			$_extras[] = $hash;
		}

		$rsp = db_insert_many('DotsSearchExtras', $_extras);
		return $rsp;
	}

	#################################################################

	function dots_search_extras_create($data){

		# unique ID/key is (dot_id, name, value)

		$user = users_get_by_id($data['user_id']);

		$hash = array();

		foreach ($data as $_key => $_value){
			$hash[ $key ] = AddSlashes($value);
		}

		$rsp = db_insert('DotsSearchExtras', $hash);

		if ($rsp['ok']){
			$rsp['data'] = $data;

			dots_search_facets_add($data['name'], $data['value']);
		}

		return $rsp;
	}

	#################################################################

	function dots_search_extras_for_dot(&$dot){

		$cache_key = "dots_search_extras_{$dot['id']}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		#

		$enc_id = AddSlashes($dot['id']);

		$sql = "SELECT * FROM DotsSearchExtras WHERE dot_id='{$enc_id}'";
		$rsp = db_fetch($sql);

		if ($rsp['ok']){
			cache_set($cache_key, $rsp, 'cache locally');
		}

		return $rsp;
	}

	#################################################################

	function dots_search_extras_remove_sheet(&$sheet){

		# Unfortunately we need to fetch the list of all the dots up front
		# in order that we may invalidate the individual dot caches. There
		# is a reasonable argument to be made that purging those caches is
		# just pointless busy work since they'll never be fetched but let's
		# try to do the right thing, for now. (20110301/straup)

		$sql = "SELECT dot_id FROM DotsSearchExtras WHERE sheet_id='{$enc_id}'";
		$rsp = db_fetch($sql);

		$cache_keys = array();

		foreach ($rsp['rows'] as $row){
			$cache_keys[] = "dots_search_extras_{$row['dot_id']}";
		}

		#

		$enc_id = AddSlashes($sheet['id']);

		$sql = "DELETE FROM DotsSearchExtras WHERE sheet_id='{$enc_id}'";
		$rsp = db_write($sql);

		if ($rsp['ok']){
			
			foreach ($cache_keys as $key){
				cache_unset($key);
			}
		}

		return $rsp;
	}

	#################################################################

	function dots_search_extras_remove_dot(&$dot){

		$enc_id = AddSlashes($dot['id']);

		$sql = "DELETE FROM DotsSearchExtras WHERE dot_id='{$enc_id}'";
		$rsp = db_write($sql);

		if ($rsp['ok']){
			$cache_key = "dots_search_extras_{$dot['id']}";
			cache_unset($cache_key);
		}

		return $rsp;
	}

	#################################################################
?>
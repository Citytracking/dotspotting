<?php

	#
	# $Id$
	#

	#################################################################

	function sheets_lookup_sheet($sheet_id){

		$cache_key = "sheets_lookup_{$sheet_id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$enc_id = AddSlashes($sheet_id);

		$sql = "SELECT * FROM SheetsLookup WHERE sheet_id='{$enc_id}'";
		$rsp = db_fetch($sql);

		if ($rsp['ok']){

			cache_set($cache_key, $rsp, 'cache locally');
		}

		return db_single($rsp);
	}

	#################################################################

	function sheets_lookup_create(&$lookup){

		$hash = array();

		foreach ($lookup as $key => $value){
			$hash[$key] = AddSlashes($value);
		}

		return db_insert('SheetsLookup', $hash);
	}

	#################################################################

	function sheets_lookup_update(&$sheet, &$update){

		$cache_key = "sheets_lookup_{$sheet['id']}";
		cache_unset($cache_key);

		$hash = array();

		foreach ($update as $key => $value){
			$hash[$key] = AddSlashes($value);
		}

		$enc_id = AddSlashes($sheet['id']);
		$where = "sheet_id={$enc_id}";

		return db_update('SheetsLookup', $update, $where);
	}

	#################################################################
?>
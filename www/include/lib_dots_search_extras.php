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

		$enc_id = AddSlashes($dot['id']);

		$sql = "SELECT * FROM DotsSearchExtras WHERE dot_id='{$enc_id}'";
		$rsp = db_fetch($sql);

		return $rsp;
	}

	#################################################################

	function dots_search_extras_remove_dot(&$dot){

		$extras = dots_search_extras_for_dot($dot);

		foreach ($extras['rows'] as $row){
			dots_search_facets_remove($row['name'], $row['value'], 1);
		}

		#

		$user = users_get_by_id($dot['user_id']);

		$enc_id = AddSlashes($dot['id']);

		$sql = "DELETE FROM DotsSearchExtras WHERE dot_id='{$enc_id}'";
		$rsp = db_write($sql);
		return $rsp;
	}

	#################################################################
?>
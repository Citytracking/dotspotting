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

		return db_insert_many('DotsSearchExtras', $_extras);
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
		}

		return $rsp;
	}

	#################################################################

	function dots_search_extras_remove_dot(&$dot){

		$user = users_get_by_id($dot['user_id']);

		$enc_id = AddSlashes($dot['id']);

		$sql = "DELETE FROM DotsSearchExtras WHERE dot_id='{$enc_id}'";
		return db_write($sql);
	}

	#################################################################
?>
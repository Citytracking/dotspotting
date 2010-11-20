<?php

	#
	# $Id$
	#

	#################################################################

	function dots_search_add_dot(&$dot, $add_offline=0){

		$hash = array();

		foreach ($dot as $key => $value){
			$hash[$key] = AddSlashes($value);
		}

		return db_insert('DotsSearch', $hash);
	}

	#################################################################

	function dots_search_add_lots_of_dots(&$dots, $add_offline=0){

		$_dots = array();

		foreach ($dots as $d){

			$hash = array();

			foreach ($d as $key => $value){
				$hash[$key] = AddSlashes($value);
			}

			$_dots[] = $hash;
		}

		return db_insert_many('DotsSearch', $_dots);
	}

	#################################################################

	function dots_search_remove_dot(&$dot){

		$enc_id = AddSlashes($dot['id']);

		$sql = "DELETE FROM DotsSearch WHERE dot_id='{$enc_id}'";
		return db_write($sql);
	}

	#################################################################

	function dots_search_remove_sheet(&$sheet){

		$enc_id = AddSlashes($sheet['id']);

		$sql = "DELETE FROM DotsSearch WHERE sheet_id='{$enc_id}'";
		return db_write($sql);
	}

	#################################################################

	function dots_search_remove_user(&$user){

		$enc_id = AddSlashes($user['id']);

		$sql = "DELETE FROM DotsSearch WHERE user_id='{$enc_id}'";
		return db_write($sql);
	}

	#################################################################

?>
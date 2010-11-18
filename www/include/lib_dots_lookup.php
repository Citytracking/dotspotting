<?php

	#
	# $Id$
	#

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

		$hash = array();

		foreach ($update as $key => $value){
			$hash[$key] = AddSlashes($value);
		}

		$enc_id = AddSlashes($dot['id']);
		$where = "dot_id={$enc_id}";

		return db_update('DotsLookup', $update, $where);
	}

	#################################################################
?>
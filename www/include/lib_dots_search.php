<?php

	#
	# $Id$
	#

	#################################################################

	function dots_search_create(&$dot){

		$hash = array();

		foreach ($dot as $key => $value){
			$hash[$key] = AddSlashes($value);
		}

		return db_insert('DotsSearch', $hash);
	}

	#################################################################
?>